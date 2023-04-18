<?php

namespace App\Controller\Registration;

use App\Entity\Profession;
use App\Entity\User;
use App\Form\User\RegistrationAdminFormType;
use App\Form\User\RegistrationCompanyFormType;
use App\Form\User\RegistrationFormType;
use App\Form\User\RegistrationMasterFormType;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\JobTypeRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\Mailer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\FileUploader;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class RegistrationAdminController extends AbstractController
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

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
     *
     * @Route("/registration-admin", name="app_registration_admin")
     */
    public function registerAdmin(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        ManagerRegistry $doctrine,
        UserRepository $userRepository,
        Mailer $mailer
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR)) {
            $user = new User();
            $form = $this->createForm(RegistrationAdminFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $post = $_POST['registration_admin_form'];
                $plainPassword = $post['plainPassword']['first'];

                // Check if user existing
                $existingUser = $userRepository->findOneBy(['email' => $post['email']]);
                if (null !== $existingUser) {
                    $message = $translator->trans('User existing', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    $referer = $request->headers->get('referer');
                    return new RedirectResponse($referer);
                }

                // encode the plain password
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                );

                $user->setUsername($form->get('email')->getData());

                // 1 - super admin, 2 - editor, 3 - support
                if ($post['role'] == 1) {
                    $user->setRoles(["ROLE_SUPER_ADMIN","ROLE_EDITOR","ROLE_SUPPORT"]);
                } elseif ($post['role'] == 2) {
                    $user->setRoles(["ROLE_EDITOR","ROLE_SUPPORT"]);
                } elseif ($post['role'] == 3) {
                    //$user->setRoles(array('ROLE_SUPPORT'));
                    $user->setRoles(["ROLE_SUPPORT"]);
                }

                $user->setIsVerified('1');

                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $subject = $translator->trans('New admin registered', array(), 'messages');
                $mailer->sendNewCompanyEmail($user, $subject, 'emails/new_admin_registration.html.twig', $plainPassword);

                $message = $translator->trans('Admin registered', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            return $this->render('registration/register_admin.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
