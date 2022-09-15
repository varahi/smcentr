<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if ($user->isIsDisabled() == 1) {
            throw new CustomUserMessageAuthenticationException("Ваш аккунт заблокирован, пожалуйста свяжитесь с нашей службой поддержки для прояснения ситуации !");
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
    }
}
