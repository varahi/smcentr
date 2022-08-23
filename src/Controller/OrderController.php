<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use App\Form\Order\OrderFormType;

class OrderController extends AbstractController
{
    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    /**
     * @param Security $security
     * @param Environment $twig
     */
    public function __construct(
        Security $security,
        Environment $twig
    ) {
        $this->security = $security;
        $this->twig = $twig;
    }

    /**
     * @Route("/order", name="app_order")
     */
    public function index(): Response
    {
        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
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
                $order->setStatus('1');
                $order->setUsers($user);
                $entityManager = $doctrine->getManager();
                $entityManager->persist($order);
                $entityManager->flush();

                $message = $translator->trans('Order created', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_order_new');
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
     * @Route("/support", name="app_support")
     */
    public function support(): Response
    {
        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }

    /**
     * @Route("/history", name="app_history")
     */
    public function history(): Response
    {
        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }
}
