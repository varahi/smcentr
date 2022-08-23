<?php

namespace App\Controller;

use App\Entity\Profession;
use App\Entity\User;
use App\Form\User\RegistrationFormType;
use App\Form\User\RegistrationMasterFormType;
use App\Repository\JobTypeRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    private $mailer;

    public function __construct(EmailVerifier $emailVerifier, VerifyEmailHelperInterface $helper, MailerInterface $mailer)
    {
        $this->emailVerifier = $emailVerifier;
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
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
     * @Route("/registration-client", name="app_registration_client")
     */
    public function registerClient(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        FileUploader $fileUploader
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
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
        FileUploader $fileUploader
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationMasterFormType::class, $user);
        $form->handleRequest($request);

        $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
        $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);

        if ($form->isSubmitted()) {
            // encode the plain password
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
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

            // generate a signed url and email it to the user
            /*
             Uncomment this block if need to send email
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('info@pimentrouge.fr', 'ConciergeAdmin'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            */
            // do anything else you need here, like send an email

            $message = $translator->trans('User registered', array(), 'flash');
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
        NotifierInterface $notifier
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

        // Mark your user as verified. e.g. switch a User::verified property to true
        $message = $translator->trans('Email verifyed', array(), 'flash');
        $notifier->send(new Notification($message, ['browser']));
        return $this->redirectToRoute("app_login");
    }
}
