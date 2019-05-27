<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Doctrine\DBAL\Connection;
use Phase\Wedding\DataStore;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;

class DefaultController extends Controller
{
    protected $csrfIntentionRsvp = 'contactRSVP';
    protected $csrfIntentionMenuChoices = 'menuChoices';
    protected $csrfIntentionGuestProfile = 'guestProfile';

    public function indexAction()
    {
        $params = [
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
            'validPages' => $this->getValidInfoPages(),
            'settings' => $this->getDataStore()->getSettings()
        ];
        return $this->render(
            'default/index.html.twig',
            $params
        );
    }

    public function contactsAction(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'API access requires login');

        $method = $request->getMethod();

        $errors = [];
        $dataStore = $this->getDataStore();


        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();

        if ($method == 'POST') {
            if (!$this->getDataStore()->getSetting('enableRsvp')) {
                throw new AccessDeniedHttpException('RSVPs closed');
            }

            $token = $request->request->get('token');

            $csrf = $this->get('security.csrf.token_manager');
            $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionRsvp, $token));
            if ($tokenValid) {
                $contactsJson = $request->request->get('data');

                $contactsArray = isset($contactsJson['otherGuests']) ? $contactsJson['otherGuests'] : [];
                array_unshift($contactsArray, $contactsJson['mainContact']);

                $stored = $dataStore->storeContactsArrayForUser($contactsArray, $userId, $errors);

                if ($stored) {
                    $result = $this->getUserContactsJson($dataStore, $user);
                } else {
                    $errors[] = 'Error storing RSVP';
                    $result = ['errors' => $errors];
                }
            } else {
                $errors[] = 'Token error; please refresh page and retry';
                $result = ['errors' => $errors];
            }
            return JsonResponse::create($result, $errors ? 500 : 200);
        } else {
            if ($user) {
                $contacts = $this->getUserContactsJson($dataStore, $user);
            } else {
                $contacts = null;
                $errors = ['Not logged in']; // should be impossible
            }
            return JsonResponse::create($contacts, $errors ? 500 : 200);
        }
    }

    public function userAction()
    {
        return JsonResponse::create($this->getUser());
    }

    public function infoPageAction($page)
    {
        $this->denyAccessUnlessGranted('ROLE_OBSERVER', null, 'Access requires verified login');

        $validPages = $this->getValidInfoPages();

        $subtitle = null;

        if (isset($validPages[$page])) {
            $subtitle = $validPages[$page];
        } else {
            throw new NotFoundHttpException('No such page');
        }

        $dataStore = $this->getDataStore();
        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();
        $guests = $dataStore->getContactRecordsByUserId($userId);

        $guestNames = [];
        foreach ($guests as $guest) {
            $guestNames[] = $guest['name'];
        }

        return $this->render(
            'infoPages/' . $page . '.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'infoPage' => $page,
                'subtitle' => $subtitle,
                'validPages' => $validPages,
                'guestNames' => $guestNames
            ]
        );
    }

    public function kioskAction($page)
    {
        return $this->render(
            'kiosk/index.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'kioskPage' => $page,
                'subtitle' => ucfirst($page),
                'kioskMode' => 1
            ]
        );
    }

    public function menuAction()
    {
        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'Menu access restricted to guests');

        /**
         * @var Connection $conn
         */
        $dataStore = $this->getDataStore();

        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();
        $guests = $dataStore->getContactRecordsByUserId($userId);
        $validPages = $this->getValidInfoPages();


        $mealTypes = [
            'Adult' => [
                'Adult' => 'Adult',
                'Teen' => 'Teenager (12-17)'
            ],
            'Child' => [
                'Child' => 'Child (4-11)',
                'Under4' => 'Child (0-3)',
                'NoMeal' => 'Infant (No meal needed)'
            ]
        ];

        $adultMenu = [
            'Starter' => [
                'quinoa' => 'Char grilled vegetable and quinoa salad (Vegetarian + Vegan)',
                'pate' => 'Chicken liver pate, toasted ciabatta bread & rustic apple chutney',
                'salmon' => 'Smoked salmon, melba toast & lemon mayonnaise, petit herb salad'
            ],
            'Main' => [
                'moussaka' => 'Uncovered mediterranean vegetable Moussaka served with a Lebanese cucumber salad' .
                    ' (Vegetarian + Vegan)',
                'hake' => 'Herb crusted hake fillet on a bed of basil pesto creamed tagliatelle',
                'sirloin' => 'Pan seared Sirloin of beef, dauphinoise potatoes, blistered cherry tomato compote,' .
                    ' buttered spinach & rich red wine jus',
            ],
            'Dessert' => [
                'crumble' => 'Rhubarb crumble with mango sorbet (Vegetarian + Vegan)',
                'posset' => 'Lemon posset, fresh berry compote & short bread biscuit',
                'chocolatetart' => 'Chocolate Ganache tart with raspberry & mango sorbet'
            ]
        ];

        $childMenu = [
            'Starter' => [
                'nachos' => 'Nachos with cheese and tomato',
                'melon' => 'Fresh melon with ham',
                'soup' => 'Fresh seasonal soup with crusty bread',
                'pita' => 'Grilled pita bread with vegetable sticks and creamy hummus'
            ],
            'Main' => [
                'sausages' => 'Roasted juicy pork sausages and mash with baked beans or peas and gravy',
                'pasta' => 'Pasta with sweet peppers, tomato and Parmesan',
                'chicken' => 'Roast chicken with gravy, vegetables and mash',
                'cheeseburger' => 'Cheeseburger and chips',
                'fishfingers' => 'Fish fingers and chips'
            ],
            'Dessert' => [
                'icecream' => 'Selection of ice cream',
                'fruitsalad' => 'Fresh fruit salad',
                'bananasplit' => 'Banana split',
                'stickytoffee' => 'Sticky toffee pudding with warm custard'
            ]
        ];

        return $this->render(
            ':default:menuChoices.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'subtitle' => 'Dining choices',
                'validPages' => $validPages,
                'guests' => $guests,
                'mealTypes' => $mealTypes,
                'adultMenu' => $adultMenu,
                'childMenu' => $childMenu,
                'settings' => $this->getDataStore()->getSettings()
            ]
        );
    }

    public function menuChoiceApiAction(Request $request)
    {
        $code = 200;

        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'API access requires login');

        $method = $request->getMethod();

        /**
         * @var Connection $conn
         */
        $dataStore = $this->getDataStore();

        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();

        if ($method == 'POST') {
            if (!$this->getDataStore()->getSetting('enableMenuResponses')) {
                throw new AccessDeniedHttpException('Menu submissions closed');
            }

            $csrf = $this->get('security.csrf.token_manager');
            $token = $request->request->get('token');

            $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionMenuChoices, $token));
            if ($tokenValid) {
                $choices = $request->get('choices');
                $saved = $dataStore->storeMenuChoicesForUser($choices, $userId);
                if ($saved) {
                    $response = $dataStore->getMenuChoicesByUserId($userId);
                } else {
                    $response = ['errors' => 'Failed to save'];
                    $code = 500;
                }
            } else {
                $response = ['errors' => 'Token invalid'];
                $code = 500;
            }
        } else {
            $response = $dataStore->getMenuChoicesByUserId($userId);
        }
        return JsonResponse::create($response, $code);
    }

    public function photoPageAction()
    {
        $this->denyAccessUnlessGranted('ROLE_OBSERVER', null, 'Page requires login');

        $photos = [
            //TODO add some photos to share with your guests here
        ];

        $description = 'Here are a few of our favourite pictures.';

        return $this->render(
            'default/photos.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'subtitle' => 'Photos',
                'validPages' => $this->getValidInfoPages(),
                'photos' => $photos,
                'description' => $description
            ]
        );
    }

    public function profileAction($guestId)
    {
        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'Page requires login');

        $params = [
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
            'subtitle' => 'Guests',
            'validPages' => $this->getValidInfoPages()
        ];

        if ($guestId === 'index') {
            $guestId = null;
        }

        if ($guestId && !is_numeric($guestId)) {
            throw new NotFoundHttpException();
        }


        $dataStore = $this->getDataStore();
        if ($guestId) {
            $params['guestId'] = $guestId;
            $params['guestData'] = $dataStore->fetchGuestData($guestId);
        } else {
            $params['guests'] = $dataStore->fetchAllContactsNaturalOrder();
        }


        return $this->render(
            $guestId ? 'default/guestProfile.html.twig' : 'default/profileIndex.html.twig',
            $params
        );
    }

    public function profileApiAction($guestId)
    {
        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'Page requires login');
        $dataStore = $this->getDataStore();
        $profile = $dataStore->fetchGuestData($guestId);
        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();
        $profileUserId = (int)$profile['userId'];
        if ($profileUserId !== $userId) {
            throw $this->createAccessDeniedException('Own records only');
        }
        return JsonResponse::create($profile, $profile ? 200 : 404);
    }

    public function profileSaveApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_WEDDING_GUEST', null, 'Page requires login');
        $code = 200;
        $user = $this->getUser();
        /** @var User $user */
        $userId = $user->getId();

        $dataStore = $this->getDataStore();

        $csrf = $this->get('security.csrf.token_manager');
        $token = $request->request->get('token');
        $submittedProfile = $request->request->get('profile');
        $submittedGuestId = $submittedProfile['guestId'] ? (int)$submittedProfile['guestId'] : null;
        $existingProfile = $dataStore->fetchGuestData($submittedGuestId); // always present, contains userId, guestId
        $existingGuestId = $existingProfile ? (int)$existingProfile['userId'] : null;

        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionGuestProfile, $token));
        if ($tokenValid) {
            //Lookup user from guestId, make sure it matches
            if ($existingGuestId === $userId) {
                $dataStore->storeGuestProfile($submittedProfile);
                $response = $dataStore->fetchGuestData($submittedGuestId);
            } else {
                $response = ['errors' => 'Cannot update this guest'];
                $code = 500;
            }
        } else {
            $response = ['errors' => 'Token invalid'];
            $code = 500;
        }
        return JsonResponse::create($response, $code);
    }

    public function staticFileAction($directory, $name = ' - ')
    {
        $this->denyAccessUnlessGranted('ROLE_OBSERVER', null, 'Page requires login');

        $response = null;
        $knownTypes = ['jpg' => 'image/jpeg'];

        $baseDir = $this->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . 'static';

        $directory = preg_replace('/\W/', '', $directory);
        $name = preg_replace('/[^\w\.]/', '', $name);

        $filePath = $baseDir . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $name;

        if (file_exists($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (array_key_exists($extension, $knownTypes)) {
                $response = new BinaryFileResponse($filePath);
            }
        } else {
            throw $this->createNotFoundException('No such file');
        }

        return $response;
    }

    /**
     * @param $dataStore
     * @param $user
     * @return array|null
     */
    protected function getUserContactsJson(DataStore $dataStore, User $user)
    {
        $uid = $user->getId();
        return $dataStore->getUserContactsPacked($uid);
    }

    public function getValidInfoPages()
    {
        $validInfoPages = [];

        if ($this->isGranted('ROLE_OBSERVER')) {
            $validInfoPages = [
                'index' => 'Information',
                'aboutUs' => 'About Us',
                'venue' => 'Venue',
                'schedule' => 'Schedule',
                'ceremony' => 'Ceremony',
                'photography' => 'Photography',
                'dining' => 'Dining',
                'hotels' => 'Nearby Hotels',
                'attractions' => 'Attractions',
            ];

            if ($this->isGranted('ROLE_WEDDING_GUEST')) {
                $validInfoPages['registry'] = 'Gift Registry';
            }

            //if ($this->isGranted('ROLE_PREVIEW_GUEST')) { // TODO make available for guests above
            //    $validInfoPages['dining'] = 'Dining';
            //}

            //keep this one last
            $validInfoPages['planning'] = 'Planning';
        }
        return $validInfoPages;
    }

    /**
     * @return DataStore
     */
    protected function getDataStore()
    {
        /**
         * @var Connection $conn
         */
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        return $dataStore;
    }
}
