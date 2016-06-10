<?php
/**
 * Created by PhpStorm.
 * User: wechsler
 * Date: 02/01/2016
 * Time: 18:09
 */

namespace NotifyingUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;


class NotifyingUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}