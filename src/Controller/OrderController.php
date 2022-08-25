<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Twig\Environment;
use App\Form\Order\OrderFormType;

class OrderController extends AbstractController
{
    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private $doctrine;

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
     * @Route("/order", name="app_orders_list")
     */
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            $newOrders = $orderRepository->findAllByStatus(self::STATUS_NEW);


            return $this->render('order/orders_list.html.twig', [
                'user' => $user,
                'orders' => $newOrders
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/order/new", name="app_order_new")
     */
    public function newOrder(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();

            $order = new Order();
            $form = $this->createForm(OrderFormType::class, $order);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $order->setStatus(self::STATUS_NEW);
                $order->setUsers($user);
                $entityManager = $doctrine->getManager();
                $entityManager->persist($order);
                $entityManager->flush();

                $message = $translator->trans('Order created', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_client_profile');
            }

            return $this->render('order/new.html.twig', [
                'user' => $user,
                'orderForm' => $form->createView()
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/history", name="app_history")
     */
    public function history(): Response
    {
        $user = $this->security->getUser();
        return $this->render('order/order-history.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/close-order/order-{id}", name="app_close_order")
     */
    public function closeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository,
        Order $order
    ): Response {
        if ($this->security->isGranted(self::ROLE_CLIENT) || $this->security->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            if ($user->getId() == $order->getUsers()->getId() || $user->getId() == $order->getPerformer()->getId()) {
                $entityManager = $this->doctrine->getManager();
                $order->setStatus(self::STATUS_COMPLETED);
                $entityManager->flush();

                $message = $translator->trans('Order closed', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            } else {
                // Redirect if order or performer not owner
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_login');
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/take-order/order-{id}", name="app_take_order")
     */
    public function takeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository,
        Order $order
    ): Response {
        if ($this->security->isGranted(self::ROLE_MASTER)) {
            $entityManager = $this->doctrine->getManager();
            $user = $this->security->getUser();
            $order->setPerformer($user);
            $order->setStatus(self::STATUS_ACTIVE);
            $entityManager->flush();

            $message = $translator->trans('Order taked', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }
    }
}
