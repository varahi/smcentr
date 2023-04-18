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

class RegistrationClientController extends AbstractController
{
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
     * @Route("/registration-client", name="app_registration_client")
     */
    public function registerClient(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        FileUploader $fileUploader,
        UserRepository $userRepository,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
        $districts = $districtRepository->findAllOrder(['name' => 'ASC']);

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

            // Set city and district
            if ($post['city'] !=='') {
                $city = $cityRepository->findOneBy(['id' => $post['city']]);
                if ($city) {
                    $user->setCity($city);
                }
            }
            if ($post['district'] !=='') {
                $district = $districtRepository->findOneBy(['id' => $post['district']]);
                if ($district) {
                    $user->setDistrict($district);
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

            // Set company assigment if it compnay
            if ($this->security->getUser()) {
                $currentUser = $this->security->getUser();
                if ($currentUser != null && in_array(self::ROLE_COMPANY, $currentUser->getRoles())) {
                    $currentUser->setClient($user);
                    $user->setIsVerified(true);
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
            // Don't send email if created by company
            if (!$this->security->getUser()) {
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
            }

            $message = $translator->trans('User registered', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_client.html.twig', [
            'cities' => $cities,
            'districts' => $districts,
            'currentUser' => $this->security->getUser(),
            'registrationForm' => $form->createView(),
        ]);
    }
}
