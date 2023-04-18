<?php

namespace App\Controller\Registration;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    private $emailVerifier;

    private $mailer;

    private $adminEmail;

    private $security;

    public function __construct(
        EmailVerifier $emailVerifier,
        VerifyEmailHelperInterface $helper,
        MailerInterface $mailer,
        string $adminEmail,
        Security $security
    ) {
        $this->emailVerifier = $emailVerifier;
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->security = $security;
    }

    /**
     * @Route("/registration", name="app_registration")
     */
    public function index(): Response
    {
        return $this->render('registration/index.html.twig', [
        ]);
    }


    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Mailer $mailer
    ): Response {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        //$user = $this->getUser();

        $id = $request->get('id'); // retrieve the user id from the url

        // Verify the user id exists and is not null
        if (null === $id) {
            $message = $translator->trans('Something wrong', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $message = $translator->trans('Something wrong', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        // Do not get the User's Id or Email Address from the Request object
        try {
            //$this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            //$this->addFlash('verify_email_error', $e->getReason());

            $message = $translator->trans('Something wrong', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        if ($user != null && in_array(self::ROLE_MASTER, $user->getRoles())) {
            $message = $translator->trans('Email for master verifyed', array(), 'flash');
            $subject = $translator->trans('Master account verified', array(), 'messages');
            $mailer->sendMasterVerifedEmail($user, $subject, 'emails/master_verified.html.twig');
        } else {
            // Mark your user as verified. e.g. switch a User::verified property to true
            $message = $translator->trans('Email verifyed', array(), 'flash');
        }

        $notifier->send(new Notification($message, ['browser']));
        return $this->redirectToRoute("app_login");
    }
}
