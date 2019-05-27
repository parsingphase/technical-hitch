<?php
/**
 * Created by PhpStorm.
 * User: wechsler
 * Date: 02/01/2016
 * Time: 18:12
 */

namespace NotifyingUserBundle\Controller;

use \FOS\UserBundle\Controller\RegistrationController as FOSRegistrationController;

use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

class RegistrationController extends FOSRegistrationController
{
    public function registerAction()
    {
        // Copied from parent class
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($confirmationEnabled);
        if ($process) {
            $user = $form->getData();

            $authUser = false;
            if ($confirmationEnabled) {
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';
            } else {
                $authUser = true;
                $route = 'fos_user_registration_confirmed';
            }

            $this->notifyAdministratorOfUserChange($user, 'Account created'); //ADDED
            $this->setFlash('fos_user_success', 'registration.flash.user_created');
            $url = $this->container->get('router')->generate($route);
            $response = new RedirectResponse($url);

            if ($authUser) {
                $this->authenticateUser($user, $response);
            }

            return $response;
        }

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Registration:register.html.' . $this->getEngine(),
            array(
                'form' => $form->createView(),
            )
        );
    }

    public function confirmedAction()
    {
        // Copied from parent class
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $this->notifyAdministratorOfUserChange($user, 'Account confirmed'); //ADDED

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Registration:confirmed.html.' . $this->getEngine(),
            array(
                'user' => $user,
            )
        );
    }


    protected function notifyAdministratorOfUserChange(UserInterface $user, $message)
    {
        $mailer = $this->container->get('swiftmailer.mailer.default');

        $from = $this->container->getParameter('admin_notification_from_email');
        $to = $this->container->getParameter('admin_notification_to_email');

        $userEmail = $user->getEmail();
        $userName = $user->getUsername();
        $body = $message . "\n\n$userName ($userEmail)\n";

        $message = new Swift_Message('User update from wedding admin', $body);

        $message->setFrom($from, 'Wedding site');
        $message->setTo($to, 'Wedding admin');

        $mailer->send($message);
    }
}
