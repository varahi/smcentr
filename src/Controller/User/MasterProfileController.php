<?php

namespace App\Controller\User;

use App\Form\User\MasterProfileFormType;
use App\ImageOptimizer;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\FirebaseRepository;
use App\Repository\JobTypeRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FileUploader;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;
use App\Service\PhoneNumberService;

class MasterProfileController extends AbstractController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param ImageOptimizer $imageOptimizer
     * @param string $targetDirectory
     * @param VerifyEmailHelperInterface $helper
     * @param EmailVerifier $emailVerifier
     * @param string $defaultDomain
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        ImageOptimizer $imageOptimizer,
        string $targetDirectory,
        VerifyEmailHelperInterface $helper,
        EmailVerifier $emailVerifier,
        string $defaultDomain,
        PhoneNumberService $phoneNumberService
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->imageOptimizer = $imageOptimizer;
        $this->targetDirectory = $targetDirectory;
        $this->verifyEmailHelper = $helper;
        $this->emailVerifier = $emailVerifier;
        $this->defaultDomain = $defaultDomain;
        $this->phoneNumberService = $phoneNumberService;
    }

    /**
     * @Route("/master-balance", name="app_master_balance")
     */
    public function masterBalance(
        TranslatorInterface $translator,
        NotifierInterface $notifier
    ): Response {
        $user = $this->security->getUser();
        if ($user->isIsDisabled() == 1) {
            $message = $translator->trans('Please verify you profile', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_logout");
        }

        return $this->render('user/master/master-balance.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @IsGranted("ROLE_MASTER")
     * @Route("/user/lk-master", name="app_master_profile")
     */
    public function masterProfile(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository
    ): Response {
        if (!$this->isGranted(self::ROLE_MASTER)) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        $user = $this->security->getUser();
        if ($user->isIsDisabled() == 1) {
            $message = $translator->trans('Please verify you profile', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_logout");
        }

        // Resize image if exist
        if ($user->getAvatar()) {
            $this->imageOptimizer->resize($this->targetDirectory.'/'.$user->getAvatar());
        }

        $activeOrders = $orderRepository->findPerfomedByStatus(self::STATUS_ACTIVE, $user, 'created', 'DESC', '999');
        $completedOrders = $orderRepository->findPerfomedByStatus(self::STATUS_COMPLETED, $user, 'closed', 'DESC', '999');
        $entityManager = $this->doctrine->getManager();

        {
            $response = new Response($this->twig->render('user/master/lk-master.html.twig', [
                'user' => $user,
                'activeOrders' => $orderRepository->findPerfomedByStatus(self::STATUS_ACTIVE, $user, 'created', 'DESC', '999'),
                'completedOrders' => $orderRepository->findPerfomedByStatus(self::STATUS_COMPLETED, $user, 'closed', 'DESC', '999')
            ]));

            // Check and set phone numbers for instructors
            if (count($activeOrders) > 0) {
                foreach ($activeOrders as $item) {
                    if ($item->getPhone()) {
                        $item->setPhone($this->phoneNumberService->formatPhoneNumber($item->getPhone()));
                        $entityManager->flush();
                    }
                }
            }

            if (count($completedOrders) > 0) {
                foreach ($activeOrders as $item) {
                    if ($item->getPhone()) {
                        $item->setPhone($this->phoneNumberService->formatPhoneNumber($item->getPhone()));
                        $entityManager->flush();
                    }
                }
            }

            //$response->setSharedMaxAge(self::CACHE_MAX_AGE);
            return $response;
        }
    }

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @Route("/user/edit-master-profile", name="app_edit_master_profile")
     */
    public function editMasterProfile(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader,
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository,
        FirebaseRepository $firebaseRepository,
        PushNotification $firebase
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            if ($user->isIsDisabled() == 1) {
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_logout");
            }

            $form = $this->createForm(MasterProfileFormType::class, $user);
            $form->handleRequest($request);

            $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
            $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);
            $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
            $districts = $districtRepository->findAllOrder(['name' => 'ASC']);
            $entityManager = $this->doctrine->getManager();

            if ($form->isSubmitted()) {
                $post = $request->request->get('master_profile_form');

                // Set new password if changed
                if ($post['plainPassword']['first'] !=='' && $post['plainPassword']['second'] !=='') {
                    if (strcmp($post['plainPassword']['first'], $post['plainPassword']['second']) == 0) {
                        // encode the plain password
                        $user->setPassword(
                            $passwordHasher->hashPassword(
                                $user,
                                $post['plainPassword']['first']
                            )
                        );
                    } else {
                        $message = $translator->trans('Mismatch password', array(), 'flash');
                        $notifier->send(new Notification($message, ['browser']));
                        return $this->redirectToRoute("app_edit_client_profile");
                    }
                }

                // Set job types
                if (isset($post['jobTypes']) && $post['jobTypes'] !== '') {
                    // Clear all jobtypes from curent user
                    foreach ($jobTypes as $jobType) {
                        $jobType->removeUser($user);
                        $entityManager->persist($jobType);
                        $entityManager->flush();
                    }
                    foreach ($post['jobTypes'] as $jobTypeId) {
                        $jobType = $jobTypeRepository->findOneBy(['id' => $jobTypeId]);
                        $user->addJobType($jobType);
                    }
                }

                // Set professions
                if (isset($post['professions']) && $post['professions'] !== '') {
                    // Clear all professions from curent user
                    foreach ($professions as $profession) {
                        $profession->removeUser($user);
                        $entityManager->persist($profession);
                        $entityManager->flush();
                    }
                    foreach ($post['professions'] as $professionId) {
                        $profession = $professionRepository->findOneBy(['id' => $professionId]);
                        $user->addProfession($profession);
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

                // Files upload
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

                $entityManager->persist($user);
                $entityManager->flush();

                // Send push notification start
                /*$notification = [
                    'title' => 'Some title',
                    'body' => sprintf('Some action updated at %s.', date('H:i')),
                    'icon' => $this->defaultDomain . '/assets/images/logo.svg',
                    'click_action' => 'https://smcentr.localhost/',
                ];

                $tokens = $firebaseRepository->findAll();
                if (count($tokens) > 0) {
                    foreach ($tokens as $key => $token) {
                        $firebase->sendSimplePushNotification($token->getToken(), $notification);
                    }
                }*/
                // Send push notification end

                $message = $translator->trans('Profile updated', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_master_profile');
            }

            {
                $response = new Response($this->twig->render('user/master/edit-master.html.twig', [
                    'user' => $user,
                    'professions' => $professions,
                    'jobTypes' => $jobTypes,
                    'cities' => $cities,
                    'districts' => $districts,
                    'form' => $form->createView(),
                ]));

                //$response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
