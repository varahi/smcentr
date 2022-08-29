<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 *
 */
trait EmailVerifyTrait
{
    /**
     * @var EmailVerifier
     */
    private $emailVerifier;

    /**
     * @var VerifyEmailHelperInterface
     */
    private $helper;

    /**
     * @param EmailVerifier $emailVerifier
     * @param VerifyEmailHelperInterface $helper
     */
    public function __construct(
        EmailVerifier $emailVerifier,
        VerifyEmailHelperInterface $helper
    ) {
        $this->emailVerifier = $emailVerifier;
        $this->verifyEmailHelper = $helper;
    }


    /**
     * @param User $user
     * @return void
     */
    public function verifyEmail(
        User $user
    ) {

        // Verify email
        /*$signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()] // add the user's id as an extra query param
        );

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@smcentr.su', 'Admin'))
                ->to($user->getEmail())
                ->subject('Please confirm your password')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'verifyUrl' => $signatureComponents->getSignedUrl()
                ])
        );*/
    }
}
