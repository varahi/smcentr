<?php

namespace App\Controller;

use App\Form\User\EditClientProfileFormType;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController
{
    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    /**
     * Time in seconds 3600 - one hour
     */
    public const CACHE_MAX_AGE = '3600';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private $security;

    private $twig;

    private $urlGenerator;

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/user", name="app_user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
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
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();

            $newOrders = $orderRepository->findByStatus(self::STATUS_NEW, $user);
            $activeOrders = $orderRepository->findByStatus(self::STATUS_ACTIVE, $user);
            $completedOrders = $orderRepository->findByStatus(self::STATUS_COMPLETED, $user);

            {
                $response = new Response($this->twig->render('user/lk-client.html.twig', [
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

            $activeOrders = $orderRepository->findPerfomedByStatus(self::STATUS_ACTIVE, $user);
            $completedOrders = $orderRepository->findPerfomedByStatus(self::STATUS_COMPLETED, $user);

            {
                $response = new Response($this->twig->render('user/lk-master.html.twig', [
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
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/notifications", name="app_notifications")
     */
    public function notifications(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();
            {

                $response = new Response($this->twig->render('user/lk-client.html.twig', [
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
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/edit-client-profile", name="app_edit_client_profile")
     */
    public function editProfile(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();
            $form = $this->createForm(EditClientProfileFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $post = $request->request->get('edit_client_profile_form');
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
                $response = new Response($this->twig->render('user/edit-client.html.twig', [
                    'user' => $user,
                    'registrationForm' => $form->createView(),
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
}
