<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\User\ClientProfileFormType;
use App\Form\User\MasterProfileFormType;
use App\Form\User\RegistrationFormType;
use App\Repository\JobTypeRepository;
use App\Repository\NotificationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\ImageOptimizer;
#use App\Controller\Traits\EmailVerifyTrait;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Knp\Component\Pager\PaginatorInterface;

class UserController extends AbstractController
{
    //use EmailVerifyTrait;

    /**
     * Time in seconds 3600 - one hour
     */
    public const CACHE_MAX_AGE = '3600';

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private $security;

    private $twig;

    private $urlGenerator;

    private $targetDirectory;

    private $emailVerifier;

    public const LIMIT_PER_PAGE = '3';

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param ImageOptimizer $imageOptimizer
     * @param string $targetDirectory
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        ImageOptimizer $imageOptimizer,
        string $targetDirectory,
        VerifyEmailHelperInterface $helper,
        EmailVerifier $emailVerifier
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->imageOptimizer = $imageOptimizer;
        $this->targetDirectory = $targetDirectory;
        $this->verifyEmailHelper = $helper;
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/master-balance", name="app_master_balance")
     */
    public function masterBalance(): Response
    {
        $user = $this->security->getUser();

        return $this->render('user/master/master-balance.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/lk-client", name="app_client_profile")
     */
    public function clinetProfile(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository,
        MailerInterface $mailer,
        PaginatorInterface $paginator
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();

            if ($user->isIsVerified() == 0) {
                // Send a new email link to verify email
                $this->verifyEmail($user);
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_login");
            }

            $queryOrders = $orderRepository->findByStatus(self::STATUS_NEW, $user);
            $queryOrders2 = $orderRepository->findByStatus(self::STATUS_ACTIVE, $user);

            // Pagination
            /*$newOrders = $paginator->paginate(
                $queryOrders,
                $request->query->getInt('page', 1),
                self::LIMIT_PER_PAGE
            );*/

            $newOrders = $orderRepository->findByStatus(self::STATUS_NEW, $user);
            $activeOrders = $orderRepository->findByStatus(self::STATUS_ACTIVE, $user);
            $completedOrders = $orderRepository->findByStatus(self::STATUS_COMPLETED, $user);

            // Resize image if exist
            if ($user->getAvatar()) {
                $this->imageOptimizer->resize($this->targetDirectory.'/'.$user->getAvatar());
            }

            {
                $response = new Response($this->twig->render('user/client/lk-client.html.twig', [
                    'user' => $user,
                    'newOrders' => $newOrders,
                    'activeOrders' => $activeOrders,
                    'completedOrders' => $completedOrders
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
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
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();

            if ($user->isIsVerified() == 0) {
                // Send a new email link to verify email
                $this->verifyEmail($user);
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_login");
            }

            $activeOrders = $orderRepository->findPerfomedByStatus(self::STATUS_ACTIVE, $user);
            $completedOrders = $orderRepository->findPerfomedByStatus(self::STATUS_COMPLETED, $user);

            // Resize image if exist
            if ($user->getAvatar()) {
                $this->imageOptimizer->resize($this->targetDirectory.'/'.$user->getAvatar());
            }

            {
                $response = new Response($this->twig->render('user/master/lk-master.html.twig', [
                    'user' => $user,
                    'activeOrders' => $activeOrders,
                    'completedOrders' => $completedOrders
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @Route("/user/notifications", name="app_notifications")
     */
    public function notifications(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        NotificationRepository $notificationRepository
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            {
                $newNotifications = $notificationRepository->findNewByUser($user->getId());
                $viewedNotifications = $notificationRepository->findViewedByUser($user->getId());
                $response = new Response($this->twig->render('user/notifications.html.twig', [
                    'user' => $user,
                    'newNotifications' => $newNotifications,
                    'viewedNotifications' => $viewedNotifications
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }


    /**
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/edit-client-profile", name="app_edit_client_profile")
     */
    public function editClientProfile(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();
            $form = $this->createForm(ClientProfileFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $post = $request->request->get('client_profile_form');
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

                // File upload
                $avatarFile = $form->get('avatar')->getData();
                if ($avatarFile) {
                    $avatarFileName = $fileUploader->upload($avatarFile);
                    $user->setAvatar($avatarFileName);
                }

                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $message = $translator->trans('Profile updated', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            {
                $response = new Response($this->twig->render('user/client/edit-client.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @IsGranted("ROLE_MASTER")
     * @Route("/user/edit-master-profile", name="app_edit_master_profile")
     */
    public function editMasterProfile(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader,
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            $form = $this->createForm(MasterProfileFormType::class, $user);
            $form->handleRequest($request);

            $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
            $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);
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

                $message = $translator->trans('Profile updated', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            {
                $response = new Response($this->twig->render('user/master/edit-master.html.twig', [
                    'user' => $user,
                    'professions' => $professions,
                    'jobTypes' => $jobTypes,
                    'form' => $form->createView(),
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @IsGranted("ROLE_MASTER")
     * @Route("/user/top-up-balancer", name="app_master_top_up_balance")
     */
    public function topUpBalance(
        TranslatorInterface $translator,
        NotifierInterface $notifier
    ) {
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();

            {
                $response = new Response($this->twig->render('user/master/top_up_balance.html.twig', [
                    'user' => $user,
                ]));

                $response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @param User $user
     * @return void
     */
    private function verifyEmail(
        User $user
    ) {

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
    }
}
