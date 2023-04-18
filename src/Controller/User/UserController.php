<?php

namespace App\Controller\User;

use App\Entity\Notification as UserNotification;
use App\Entity\Request as UserRequest;
use App\Entity\User;
use App\Form\Request\RequestFormType;
use App\Form\User\ClientProfileFormType;
use App\Form\User\CompanyProfileFormType;
use App\Form\User\MasterProfileFormType;
use App\ImageOptimizer;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\FirebaseRepository;
use App\Repository\JobTypeRepository;
use App\Repository\NotificationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FileUploader;
use App\Service\Mailer;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

class UserController extends AbstractController
{
    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const REQUEST_STATUS_NEW = '0';

    public const REQUEST_STATUS_ACTIVE = '1';

    public const REQUEST_STATUS_COMPLETED  = '9';

    public const LIMIT_PER_PAGE = '3';

    private $security;

    private $twig;

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
        string $defaultDomain
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->imageOptimizer = $imageOptimizer;
        $this->targetDirectory = $targetDirectory;
        $this->verifyEmailHelper = $helper;
        $this->emailVerifier = $emailVerifier;
        $this->defaultDomain = $defaultDomain;
    }

    /**
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
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_MASTER) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            if ($user->isIsDisabled() == 1) {
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_logout");
            }
            {
                $newNotifications = $notificationRepository->findNewByUser($user->getId());
                $viewedNotifications = $notificationRepository->findViewedByUser($user->getId());

                $response = new Response($this->twig->render('user/notifications.html.twig', [
                    'user' => $user,
                    'newNotifications' => $newNotifications,
                    'viewedNotifications' => $viewedNotifications
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

    /**
     *
     * @Route("/user/notification/mark/id-{id}", name="app_mark_as_read")
     */
    public function markAsRead(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        NotificationRepository $notificationRepository,
        UserNotification $userNotification
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_MASTER) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            if ($user->getId() == $userNotification->getUser()->getId()) {
                $userNotification->setIsRead('1');
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($userNotification);
                $entityManager->flush();

                $message = $translator->trans('Notification mark as read', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            } else {
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_login");
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
     * @Route("/user/top-up-balancer", name="app_top_up_balance")
     */
    public function topUpBalance(
        TranslatorInterface $translator,
        NotifierInterface $notifier
    ) {
        if ($this->isGranted(self::ROLE_MASTER) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            if ($user->isIsDisabled() == 1) {
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_logout");
            }
            {
                $response = new Response($this->twig->render('user/master/top_up_balance.html.twig', [
                    'user' => $user,
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

    /**
     * Require ROLE_MASTER for *every* controller method in this class.
     *
     * @IsGranted("ROLE_COMPANY")
     * @Route("/user/withdrawal-request", name="app_withdrawal_request")
     */
    public function withdrawalRequest(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Mailer $mailer
    ): Response {
        if ($this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();

            if ($user->getBalance() <= 0) {
                $message = $translator->trans('Your balance is zero', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_company_profile");
            }

            $userRequest = new UserRequest();
            $form = $this->createForm(RequestFormType::class, $userRequest);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $post = $request->request->get('request_form');
                if ($post['amount'] <= 0) {
                    $message = $translator->trans('Your balance is zero', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    return $this->redirectToRoute("app_company_profile");
                }

                $entityManager = $this->doctrine->getManager();
                $userRequest->setUser($user);
                $userRequest->setAmount($post['amount']);
                $userRequest->setStatus(self::REQUEST_STATUS_NEW);
                $userRequest->setName('Request');

                // Save request to get id for set name
                $entityManager->persist($userRequest);
                $entityManager->flush();
                $userRequest->setName($userRequest->getId() . ' Request from ' . $user->getUsername() . ' on ' . $post['amount']);

                // Finally save request
                $entityManager->persist($userRequest);
                $entityManager->flush();

                // Send an email
                $subject = $translator->trans('Withdrawal request', array(), 'messages');
                $mailer->sendWithdrawalRequestEmail($user, $subject, 'emails/new_withdrawal_request.html.twig', $userRequest);

                $message = $translator->trans('Request send to admin', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_company_profile");
            }

            {
                $response = new Response($this->twig->render('user/company/withdrawal_request.html.twig', [
                    'user' => $user,
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
