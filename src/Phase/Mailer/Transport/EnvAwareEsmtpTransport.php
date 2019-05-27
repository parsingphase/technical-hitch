<?php
/**
 * Created by PhpStorm.
 * User: wechsler
 * Date: 11/02/2016
 * Time: 20:19
 */

namespace Phase\Mailer\Transport;

use Swift_Events_EventDispatcher;
use Swift_Transport_IoBuffer;

class EnvAwareEsmtpTransport extends \Swift_Transport_EsmtpTransport
{
    public function __construct(
        Swift_Transport_IoBuffer $buf,
        $extensionHandlers,
        Swift_Events_EventDispatcher $dispatcher
    ) {
        parent::__construct($buf, $extensionHandlers, $dispatcher);
        $helo = trim(getenv('MAILER_HELO'));
        if ($helo) {
            $this->_domain = $helo;
            $_SERVER['SERVER_NAME'] = $helo; // used by swiftmailer internally too
        }
    }
}
