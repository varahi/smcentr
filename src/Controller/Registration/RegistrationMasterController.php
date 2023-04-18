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

class RegistrationMasterController extends AbstractController
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
        UserRepository $userRepository,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationMasterFormType::class, $user);
        $form->handleRequest($request);

        $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
        $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);
        $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
        $districts = $districtRepository->findAllOrder(['name' => 'ASC']);

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

            // Set company assigment if it compnay
            if ($this->security->getUser()) {
                $currentUser = $this->security->getUser();
                if ($currentUser != null && in_array(self::ROLE_COMPANY, $currentUser->getRoles())) {
                    $currentUser->setMaster($user);
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
                        ->to($this->adminEmail)
                        ->subject('В сервисе smcentr.su зарегистрировался новый мастер')
                        ->htmlTemplate('registration/confirmation_email_masster.html.twig')
                        ->context([
                            'verifyUrl' => $signatureComponents->getSignedUrl()
                        ])
                );
            }

            $message = $translator->trans('User master registered', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_master.html.twig', [
            'professions' => $professions,
            'jobTypes' => $jobTypes,
            'cities' => $cities,
            'districts' => $districts,
            'currentUser' => $this->security->getUser(),
            'registrationForm' => $form->createView(),
        ]);
    }
}
