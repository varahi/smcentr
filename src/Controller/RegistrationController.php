<?php

namespace App\Controller;

use App\Entity\Profession;
use App\Entity\User;
use App\Form\User\RegistrationAdminFormType;
use App\Form\User\RegistrationCompanyFormType;
use App\Form\User\RegistrationFormType;
use App\Form\User\RegistrationMasterFormType;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\FileUploader;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class RegistrationController extends AbstractController
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private $emailVerifier;

    private $mailer;

    private $adminEmail;

    public function __construct(
        EmailVerifier $emailVerifier,
        VerifyEmailHelperInterface $helper,
        MailerInterface $mailer,
        string $adminEmail
    ) {
        $this->emailVerifier = $emailVerifier;
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
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

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @Route("/registration-company", name="app_registration_company")
     */
    public function registerCompany(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader,
        ManagerRegistry $doctrine,
        Mailer $mailer
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR)) {
            $user = new User();
            $form = $this->createForm(RegistrationCompanyFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $post = $_POST['registration_company_form'];
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
                $user->setRoles(array('ROLE_COMPANY'));
                $user->setIsVerified('1');
                // Upload avatar file if exist
                $avatarFile = $form->get('avatar')->getData();
                if ($avatarFile) {
                    $avatarFileName = $fileUploader->upload($avatarFile);
                    $user->setAvatar($avatarFileName);
                }
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $subject = $translator->trans('New company registered', array(), 'messages');
                $mailer->sendNewCompanyEmail($user, $subject, 'emails/new_company_registration.html.twig', $plainPassword);

                $message = $translator->trans('Company registered', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            return $this->render('registration/register_company.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/registration-client", name="app_registration_client")
     */
    public function registerClient(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        FileUploader $fileUploader,
        UserRepository $userRepository
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // encode the plain password
            $post = $_POST['registration_form'];
            $existingUser = $userRepository->findOneBy(['email' => $post['email']]);

            // Check if user existing
            if (null !== $existingUser) {
                $message = $translator->trans('User existing', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            // Check if password mismatch
            if ($post['plainPassword']['first'] !=='' && $post['plainPassword']['second'] !=='') {
                if (strcmp($post['plainPassword']['first'], $post['plainPassword']['second']) == 0) {
                    // encode the plain password
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                    );
                } else {
                    $message = $translator->trans('Mismatch password', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    $referer = $request->headers->get('referer');
                    return new RedirectResponse($referer);
                }
            }

            $user->setUsername($form->get('email')->getData());
            $user->setRoles(array('ROLE_CLIENT'));
            // Upload avatar file if exist
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $avatarFileName = $fileUploader->upload($avatarFile);
                $user->setAvatar($avatarFileName);
            }
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // Verify email
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
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
                    ->subject('Пожалуйста подтвердите ваш пароль')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context([
                        'verifyUrl' => $signatureComponents->getSignedUrl()
                    ])
            );

            $message = $translator->trans('User registered', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_client.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/registration-master", name="app_registration_master")
     */
    public function registerMaster(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository,
        FileUploader $fileUploader,
        UserRepository $userRepository
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationMasterFormType::class, $user);
        $form->handleRequest($request);

        $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
        $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);

        if ($form->isSubmitted()) {
            $post = $_POST['registration_master_form'];
            $existingUser = $userRepository->findOneBy(['email' => $post['email']]);

            // Check if user existing
            if (null !== $existingUser) {
                $message = $translator->trans('User existing', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            // Check if password mismatch
            if ($post['plainPassword']['first'] !=='' && $post['plainPassword']['second'] !=='') {
                if (strcmp($post['plainPassword']['first'], $post['plainPassword']['second']) == 0) {
                    // encode the plain password
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                    );
                } else {
                    $message = $translator->trans('Mismatch password', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    $referer = $request->headers->get('referer');
                    return new RedirectResponse($referer);
                }
            }

            $user->setUsername($form->get('email')->getData());
            $user->setRoles(array('ROLE_MASTER'));

            // Upload avatar file if exist
            $avatarFile = $form->get('avatar')->getData();
            $doc1File = $form->get('doc1')->getData();
            $doc2File = $form->get('doc2')->getData();
            $doc3File = $form->get('doc3')->getData();
            if ($avatarFile) {
                $avatarFileName = $fileUploader->upload($avatarFile);
                $user->setAvatar($avatarFileName);
            }
            if ($doc1File) {
                $doc1FileName = $fileUploader->upload($doc1File);
                $user->setDoc1($doc1FileName);
            }
            if ($doc2File) {
                $doc2FileName = $fileUploader->upload($doc2File);
                $user->setDoc2($doc2FileName);
            }
            if ($doc3File) {
                $doc3FileName = $fileUploader->upload($doc3File);
                $user->setDoc3($doc3FileName);
            }

            $post = $request->request->get('registration_master_form');
            if (isset($post['professions']) && $post['professions'] !=='') {
                foreach ($post['professions'] as $professionId) {
                    $profession = $professionRepository->findOneBy(['id' => $professionId]);
                    $user->addProfession($profession);
                }
            }
            if (isset($post['jobTypes']) && $post['jobTypes'] !=='') {
                foreach ($post['jobTypes'] as $jobTypeId) {
                    $jobType = $jobTypeRepository->findOneBy(['id' => $jobTypeId]);
                    $user->addJobType($jobType);
                }
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // Verify email
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
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
                    ->to($this->adminEmail)
                    ->subject('В сервисе smcentr.su зарегистрировался новый мастер')
                    ->htmlTemplate('registration/confirmation_email_masster.html.twig')
                    ->context([
                        'verifyUrl' => $signatureComponents->getSignedUrl()
                    ])
            );

            $message = $translator->trans('User master registered', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_master.html.twig', [
            'professions' => $professions,
            'jobTypes' => $jobTypes,
            'registrationForm' => $form->createView(),
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
