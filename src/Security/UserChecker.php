<?php

namespace App\Security;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class UserChecker implements UserCheckerInterface
{
    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private EmailVerifier $emailVerifier;

    private $translator;

    public function __construct(
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator,
        VerifyEmailHelperInterface $helper
    ) {
        $this->emailVerifier = $emailVerifier;
        $this->translator = $translator;
        $this->verifyEmailHelper = $helper;
    }

    public function checkPreAuth(UserInterface $user)
    {
        //($user != null && in_array(self::ROLE_MASTER, $user->getRoles()))
        if ($user->isIsDisabled() == 1) {
            $message = $this->translator->trans('Please verify you profile', [], 'flash');
            throw new CustomUserMessageAuthenticationException($message);
            //throw new CustomUserMessageAuthenticationException("Ваш аккунт заблокирован, пожалуйста свяжитесь с нашей службой поддержки для прояснения ситуации !");
        }

        if ($user->isIsVerified() == 0) {
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()] // add the user's id as an extra query param
            );

            // Send verification email only for client
            if (in_array(self::ROLE_CLIENT, $user->getRoles())) {
                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('noreply@smcentr.su', 'Admin'))
                        ->to($user->getEmail())
                        ->subject($this->translator->trans('Please Confirm your Email', [], 'message'))
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                        ->context([
                            'verifyUrl' => $signatureComponents->getSignedUrl()
                        ])
                );
            }

            if (in_array(self::ROLE_CLIENT, $user->getRoles())) {
                $message = $this->translator->trans('Please verify you profile', [], 'flash');
            }
            if (in_array(self::ROLE_MASTER, $user->getRoles())) {
                $message = $this->translator->trans('Your account has not been activated yet', [], 'flash');
            }

            throw new CustomUserMessageAuthenticationException($message);
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if ($user->isIsDisabled() == 1) {
            $message = $this->translator->trans('Please verify you profile', [], 'flash');
            throw new CustomUserMessageAuthenticationException($message);
        }
    }
}
