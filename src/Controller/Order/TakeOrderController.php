<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Repository\UserRepository;
use App\Service\Mailer;
use App\Service\Order\GetTaxService;
use App\Service\Order\SetBalanceService;
use App\Service\PushNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TakeOrderController extends AbstractController
{
    use NotificationTrait;

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const NOTIFICATION_BALANCE_MINUS = '3';

    public const NOTIFICATION_CHANGE_STATUS = '1';

    private const CREATED_BY_COMPANY = '3';


    public function __construct(
        TranslatorInterface $translator,
        PushNotification $pushNotification,
        UserRepository $userRepository,
        Mailer $mailer,
        GetTaxService $getTaxService,
        SetBalanceService $setBalanceService
    ) {
        $this->translator = $translator;
        $this->pushNotification = $pushNotification;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->getTaxService = $getTaxService;
        $this->setBalanceService = $setBalanceService;
    }

    /**
     * @Route("/take-order/order-{id}", name="app_take_order")
     */
    public function takeOrder(
        Request $request,
        NotifierInterface $notifier,
        Order $order
    ): Response {
        if (!$this->security->isGranted(self::ROLE_MASTER)) {
            $message = $this->translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $user = $this->security->getUser();
        if ((int)$order->getStatus() !== (int)self::STATUS_NEW) {
            $message = $this->translator->trans('Order already in work', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_orders_list');
        }

        //Get Tax
        $tax = $this->getTaxService->getTax($order);
        if (!isset($tax)) {
            // Remove perfomer and status
            //$this->unsetOrderController->clearOrderPerfomer($order);
            $message = $this->translator->trans('No task defined', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_orders_list');
        }

        // Set balance for master, company and project
        $this->setBalanceService->setBalance($order);
        $orderTaxRate = 0;
        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $orderTaxRate = $order->getCustomTaxRate();
        }

        // Set performer and order status
        $order->setPerformer($user);
        $order->setStatus(self::STATUS_ACTIVE);
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();

        // Send push
        $fullTax = $tax + $orderTaxRate;
        $this->sendPushNotifications($order, $fullTax);

        // Save new order
        $entityManager->persist($order);
        $entityManager->flush();

        // Send mail
        $this->sendMailNotifications($order, $orderTaxRate, $fullTax);

        // Flash message
        if (isset($fullTax)) {
            $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $fullTax . ' руб. комиссии.';
        } else {
            $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
        }
        $notifier->send(new Notification($message, ['browser']));
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    private function sendMailNotifications($order, $orderTaxRate, $fullTax)
    {
        if ($order->getPerformer()->isGetNotifications() == 1) {
            // Mail to owner of the order
            if (isset($fullTax)) {
                $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $fullTax . ' руб. комиссии.';
            } else {
                $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
            }
            $this->mailer->sendUserEmail($order->getPerformer(), $subject, 'emails/order_taked_to_work.html.twig', $order);
        }

        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            // Send email to company
            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $subject = 'Мастер принял заявку в работу.  Вам начислено ' . $orderTaxRate . ' руб. комиссии.';
            $this->mailer->sendUserEmail($company, $subject, 'emails/order_taked_to_work.html.twig', $order);
        }
    }

    private function sendPushNotifications($order, $fullTax)
    {
        // Send notifications for masters
        $message1 = $this->translator->trans('Withdrawal from the balance', array(), 'messages');
        $messageStr1 = $message1 .' '.$fullTax.' руб.' .' за заявку';
        $messageStr2 = $this->translator->trans('You got an order', array(), 'messages');
        $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_BALANCE_MINUS, $messageStr1);
        $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_CHANGE_STATUS, $messageStr2);

        // Send push notification
        $this->pushNotification->sendCustomerPushNotification($message1, $messageStr1, 'https://smcentr.su/', $order->getPerformer());
        $this->pushNotification->sendCustomerPushNotification(
            $this->translator->trans('You accepted application', array(), 'flash'),
            $messageStr2,
            'https://smcentr.su/',
            $order->getPerformer()
        );

        // Send notifications for user
        $message3 = $this->translator->trans('Your order has been processed', array(), 'messages');
        $messageStr3 = $message3 .' '.$order->getPerformer()->getFullName().' - '.$order->getPerformer()->getEmail();
        $this->setNotification($order, $order->getUsers(), self::NOTIFICATION_CHANGE_STATUS, $messageStr3);

        // Send push notification
        $this->pushNotification->sendCustomerPushNotification($message3, $messageStr3, 'https://smcentr.su/', $order->getUsers());
    }
}
