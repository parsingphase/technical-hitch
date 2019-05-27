<?php
/**
 * Created by PhpStorm.
 * User: wechsler
 * Date: 01/01/2016
 * Time: 16:48
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Doctrine\DBAL\Connection;
use Phase\Wedding\DataStore;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;

class AdminController extends Controller
{
    protected $csrfIntentionUserAdmin = 'userAdmin';
    protected $csrfIntentionMailer = 'mailer';
    protected $csrfIntentionInviteUser = 'inviteUser';

    public function indexAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        return $this->render(
            'admin/index.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'subtitle' => 'Admin'
            ]
        );
    }

    public function inviteUserAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $intent = $this->csrfIntentionInviteUser;

        $sent = false;
        $viewParams = [
            'subtitle' => 'Invite',
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
            'tokenIntent' => $intent
        ];

        if ($request->getMethod() === 'POST') {
            $csrfToken = $request->request->get('token');

            $csrf = $this->get('security.csrf.token_manager');
            $tokenValid = $csrf->isTokenValid(new CsrfToken($intent, $csrfToken));
            if (!$tokenValid) {
                throw new AccessDeniedException('Bad token');
            }

            $role = $request->get('userRole');
            $validRoles = ['ROLE_WEDDING_GUEST', 'ROLE_OBSERVER'];
            if (!in_array($role, $validRoles)) {
                throw new \RuntimeException('Invalid role: ' . $role);
            }

            $sent = true;
            $userManager = $this->container->get('fos_user.user_manager');
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $resetToken = $tokenGenerator->generateToken();

            $user = $userManager->createUser();
            $user->setEmail($request->get('newUserEmail'));
            $user->setUsername($request->get('newUserName'));
            $user->setConfirmationToken($resetToken);
            $user->setPlainPassword(uniqid('', true)); // URL sent will be password reset link
            $user->setEnabled(true);
            $user->addRole($role);
            $user->setPasswordRequestedAt(new \DateTime());

            $mailer = $this->container->get('swiftmailer.mailer.default');

            $from = $this->container->getParameter('admin_notification_from_email');
            $cc = $this->container->getParameter('admin_notification_to_email');
            $subject = $request->get('subject');

            $userEmail = $user->getEmail();
            $userName = $user->getUsername();

            $url = $this->generateUrl(
                'fos_user_resetting_reset',
                ['token' => $resetToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $body = $request->get('messageBefore') .
                "\n\n$url\n\n" .
                $request->get('messageAfter');

            $message = new \Swift_Message($subject, $body);
            $message->setFrom($from, 'The Bride and Groom');
            $message->setTo($userEmail, $userName);
            $message->setBcc($cc);
            $failedRecipients = [];
            $result = $mailer->send($message, $failedRecipients);

            $userManager->updateUser($user);
            $viewParams['user'] = $user;
            $viewParams['bcc'] = $cc;
            $viewParams['message'] = $message;
            $viewParams['result'] = $result;
            $viewParams['failedRecipients'] = $failedRecipients;
        } else {
            $sampleUrl = $this->generateUrl(
                'fos_user_resetting_reset',
                ['token' => str_repeat('x', 43)],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $viewParams['sampleUrl'] = $sampleUrl;
        }

        $viewParams['sent'] = $sent;

        return $this->render(
            'admin/inviteUser.html.twig',
            $viewParams
        );
    }

    public function usersAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render(
            'admin/users.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'users' => $users,
                'subtitle' => 'User admin',
            ]
        );
    }

    public function userListApiAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Api access requires admin');

        $userManager = $this->container->get('fos_user.user_manager');
        /**
         * @var User[] $users
         */
        $users = $userManager->findUsers();


        $apiData = [];
        foreach ($users as $user) {
            $apiData[] = $this->createUserAdminRecord($user);
        }

        return JsonResponse::create($apiData);
    }

    public function updateUserRoleApiAction(Request $request)
    {
        $result = 200;
        $response = null;

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Api access requires admin');
        $token = $request->request->get('token');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionUserAdmin, $token));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }

        $userId = $request->get('userId');
        $role = $request->get('role');
        $userManager = $this->container->get('fos_user.user_manager');

        $logger = $this->container->get('logger');
        $logger->info('API request to alter user roles', ['userId' => $userId, 'role' => $role]);

        $user = $userManager->findUserBy(['id' => $userId]);
        /** @var User $user */
        if ($user) {
            if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                throw new \LogicException('Cannot remove ROLE_SUPER_ADMIN');
            }
            $user->setRoles([]);
            $user->addRole($role);
            $userManager->updateUser($user);

            $logger->info('Persisted user with new role', ['userId' => $userId, 'role' => $role]);

            $response = $this->createUserAdminRecord($user);

            if ($role === 'ROLE_WEDDING_GUEST') {
                $siteUrl = $this->get('router')->generate('wedding_homepage', array(), true);
                $subject = "You're on the guest list";
                $name = $user->getUsername();
                $body = [
                    "Dear $name",
                    "We've verified your name against the guest list and upgraded your account so that you can see " .
                    "all the private information and send us your RSVPs.",
                    "You may need to log out of the site and back in to see the new information.",
                    "You can take a look now at $siteUrl",
                    "Many thanks, The Bride and Groom"
                ];

                $body = join("\n\n", $body);
                $notificationResult =
                    $this->sendUserNotificationOnce(
                        $user,
                        null,
                        null,
                        DataStore::MESSAGE_IDENTIFIER_GUESTLIST,
                        $subject,
                        $body
                    );
                $logger->info(
                    'Sent new role notification',
                    ['userId' => $userId, 'role' => $role, 'result' => $notificationResult]
                );
            }
        } else {
            $result = 500;
            $response = ['error' => 'No such user'];
        }

        return JsonResponse::create($response, $result);
    }

    public function mailerAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render(
            'admin/mailer.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
                'users' => $users
            ]
        );
    }

    public function settingsAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Access requires admin');

        return $this->render(
            'admin/settings.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
            ]
        );
    }

    public function seatingAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $viewParams = [
            'subtitle' => 'Seating',
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
            'tokenIntent' => $this->csrfIntentionUserAdmin
        ];
        return $this->render(
            'admin/seating.html.twig',
            $viewParams
        );
    }

    public function seatingApiAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        $seating = $dataStore->getSeatingGrid(true);
        return JsonResponse::create($seating);
    }

    public function seatingSaveApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $partySeating = $request->request->get('partySeating');
        $token = $request->request->get('token');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionUserAdmin, $token));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $dataStore->savePartySeating($partySeating);

        return JsonResponse::create(['result' => 'ok']);
    }

    public function siteSettingsApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        if ($request->getMethod() === 'POST') {
            $token = $request->request->get('token');
            $csrf = $this->get('security.csrf.token_manager');
            $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionUserAdmin, $token));
            if (!$tokenValid) {
                throw new AccessDeniedException('Bad token');
            }
            $data = $request->request->get('data');
            $dataStore->updateSettingValues($data);
        }

        // respond with latest settings in call cases
        return JsonResponse::create($dataStore->getSettings(true));
    }

    public function emailAddressesApiAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $addresses = $dataStore->getEmailAddressList();
        return JsonResponse::create($addresses);
    }

    public function emailSendApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $token = $request->request->get('token');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionMailer, $token));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }

        $userManager = $this->container->get('fos_user.user_manager');
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        $template = $request->get('template');
        $recipients = $request->get('recipients');
        $response = [];
        $error = '';

        $templateId = $template['id'];
        $templateExisting = $dataStore->getEmailTemplateById($templateId);

        if ($templateExisting) {
            if (($templateExisting['identifier'] === $template['identifier']) &&
                ($templateExisting['subject'] === $template['subject']) &&
                ($templateExisting['body'] === $template['body'])
            ) {
                foreach ($recipients as $recipient) {
                    // get user by email if possible
                    //$this->sendUserNotificationOnce();
                    //contactName contactEmail
                    $email = $recipient['contactEmail'];
                    $name = $recipient['contactName'];
                    $user = $userManager->findUserByEmail($email);

                    $sent = $this->sendUserNotificationOnce(
                        $user,
                        $email,
                        $name,
                        $template['identifier'],
                        $template['subject'],
                        $template['body']
                    );

                    $response[$email] = $sent;
                }
            } else {
                $error = 'Template sent does not match stored state';
            }
        } else {
            $error = 'Template not stored';
        }

        if ($error) {
            $response['error'] = $error;
        }

        return JsonResponse::create($response, $error ? 500 : 200);
    }

    public function emailSentStatusApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $token = $request->request->get('token');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionMailer, $token));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $userManager = $this->container->get('fos_user.user_manager');

        $recipients = $request->get('recipients');
        $response = [];

        $template = $request->get('template');
        $templateIdent = $template['identifier'];

        foreach ($recipients as $recipient) {
            $email = $recipient['contactEmail'];
            /** @var User $user */
            $user = $userManager->findUserByEmail($email);
            $sent = $dataStore->notificationAlreadySent(
                $user ? $user->getId() : null,
                $email,
                $templateIdent
            );
            $response[$email] = $sent;
        }

        return JsonResponse::create($response);
    }

    public function emailTemplateApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $token = $request->request->get('token');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionMailer, $token));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }

        $message = $request->get('data');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $response = $dataStore->storeMailerTemplate($message);

        return JsonResponse::create($response);
    }

    public function emailTemplatesApiAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        $templates = $dataStore->getEmailTemplates();
        return JsonResponse::create($templates);
    }

    public function menuChoicesApiAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        $templates = $dataStore->getAllMenuChoices();
        return JsonResponse::create($templates);
    }

    public function seatLayoutSaveApiAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Admin access required');
        $csrfToken = $request->request->get('token');
        $layout = $request->request->get('layout');

        $csrf = $this->get('security.csrf.token_manager');
        $tokenValid = $csrf->isTokenValid(new CsrfToken($this->csrfIntentionUserAdmin, $csrfToken));
        if (!$tokenValid) {
            throw new AccessDeniedException('Bad token');
        }

        //now actually save it
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $dataStore->saveSeatingLayout($layout);
        $seating = $dataStore->getSeatingGrid(true);
        return JsonResponse::create($seating);
    }

    /**
     * @param $user
     * @return array
     */
    protected function createUserAdminRecord(User $user)
    {
        /**
         * @var Connection $conn
         */
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);

        $contactData = $dataStore->getContactRecordsByUserId($user->getId());
        $userArray = $user->jsonSerialize();
        $userArray['roles'] = $user->getRoles();
        $userArray['enabled'] = $user->isEnabled();
        $apiRecord = [
            'user' => $userArray,
            'contactData' => $contactData
        ];
        return $apiRecord;
    }

    protected function sendUserNotificationOnce(User $user = null, $email, $name, $messageIdentifier, $subject, $body)
    {
        if ($user) {
            $userId = $user->getId();
            if (is_null($email)) {
                $email = $user->getEmail();
            }
            if (is_null($name)) {
                $name = $user->getUsername();
            }
        } else {
            $userId = null;
        }
        //TODO IF no email address, throw error

        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        if ($dataStore->notificationAlreadySent($userId, $email, $messageIdentifier)) {
            return false;
        } else {
            $mailer = $this->container->get('swiftmailer.mailer.default');
            $from = $this->container->getParameter('admin_notification_from_email');

            $message = new \Swift_Message($subject, $body);
            $message->setFrom($from, 'The Bride and Groom');
            $message->setTo($email, $name);

            $sent = $mailer->send($message);
            if ($sent) {
                $dataStore->markNotificationSent($userId, $email, $messageIdentifier);
            }
            return $sent;
        }
    }

    public function attendingPartiesApiAction()
    {
        //cf userListApi
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Api access requires admin');

        /**
         * @var Connection $conn
         */
        $conn = $this->get('database_connection');
        $dataStore = new DataStore($conn);
        $parties = $dataStore->getParties();

        return JsonResponse::create($parties);
    }
}
