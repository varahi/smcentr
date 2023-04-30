<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Repository\ProjectRepository;
use App\Service\Mailer;
use App\Service\PushNotification;
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
use Twig\Environment;

class TakeOrderController extends AbstractController
{
    use NotificationTrait;

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const NOTIFICATION_BALANCE_MINUS = '3';

    public const NOTIFICATION_CHANGE_STATUS = '1';

    private $projectId;

    private $doctrine;

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        int $projectId
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
    }

    /**
     * @Route("/take-order/order-{id}", name="app_take_order")
     */
    public function takeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        Mailer $mailer,
        PushNotification $pushNotification,
        ProjectRepository $projectRepository
    ): Response {
        if (!$this->security->isGranted(self::ROLE_MASTER)) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $entityManager = $this->doctrine->getManager();
        $user = $this->security->getUser();

        if ((int)$order->getStatus() !== (int)self::STATUS_NEW) {
            $message = $translator->trans('Order already in work', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_orders_list');
        }

        // First we should add user as performer and save it
        // Set performer and order status
        $order->setPerformer($user);
        $order->setStatus(self::STATUS_ACTIVE);
        $entityManager->flush();

        // Set balance for master
        $masterBalance = (float)$order->getPerformer()->getBalance();
        if ($masterBalance == null || $masterBalance == 0) {

            // Remove perfomer and status
            $this->clearOrderPerfomer($order);

            // Redirect if order or performer not owner
            $message = $translator->trans('Please top up balance', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_top_up_balance');
        }

        // If order has custom tax from company
        if ($order->getCustomTaxRate()) {
            $tax = $order->getCustomTaxRate();

        // If company has tax rate (Комиссия компании)
        } elseif ($order->getUsers()->getTaxRate()) {
            $tax = $order->getPrice() * $order->getUsers()->getTaxRate();

        // If company has service Tax Rate (Комиссия сервиса)
        } elseif ($order->getUsers()->getServiceTaxRate()) {
            $tax = $order->getPrice() * $order->getUsers()->getServiceTaxRate();

        // Calculate tax rate depends on city and profession
        } else {
            if (count($order->getCity()->getTaxRates()) > 0) {
                foreach ($order->getCity()->getTaxRates() as $taxRate) {
                    if ($taxRate->getProfession()->getId() == $order->getProfession()->getId()) {
                        $tax = $order->getPrice() * $taxRate->getPercent(); // For example 2880 * 0.05
                        $newMasterBalance = $order->getPerformer()->getBalance() - $tax;
                        if ($order->getPerformer()->getBalance() <= $tax) {
                            // Remove perfomer and status
                            $this->clearOrderPerfomer($order);

                            // Redirect if order or performer not owner
                            $message = $translator->trans('Please top up balance', array(), 'flash');
                            $notifier->send(new Notification($message, ['browser']));
                            return $this->redirectToRoute('app_top_up_balance');
                        }
                    }
                }
            }
        }

        // Set new master balance
        if (!isset($tax)) {
            // Remove perfomer and status
            $this->clearOrderPerfomer($order);
            $message = $translator->trans('No task defined', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_orders_list');
        }

        $newMasterBalance = $order->getPerformer()->getBalance() - $tax;
        $project = $projectRepository->findOneBy(['id' => $this->projectId]);
        $currentProjectBalance = (float)$project->getBalance();
        $newProjectBalance = $currentProjectBalance + $tax;

        $user->setBalance($newMasterBalance);
        $project->setBalance($newProjectBalance);

        $entityManager->persist($user);
        $entityManager->persist($project);
        $entityManager->flush();

        // Send notifications for masters
        $message1 = $translator->trans('Withdrawal from the balance', array(), 'messages');
        $messageStr1 = $message1 .' '.$tax.' руб.' .' за заявку';
        $messageStr2 = $translator->trans('You got an order', array(), 'messages');
        $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_BALANCE_MINUS, $messageStr1);
        $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_CHANGE_STATUS, $messageStr2);

        // Send push notification
        $pushNotification->sendCustomerPushNotification($message1, $messageStr1, 'https://smcentr.su/', $order->getPerformer());
        $pushNotification->sendCustomerPushNotification(
            $translator->trans('You accepted application', array(), 'flash'),
            $messageStr2,
            'https://smcentr.su/',
            $order->getPerformer()
        );

        // Send notifications for user
        $message3 = $translator->trans('Your order has been processed', array(), 'messages');
        $messageStr3 = $message3 .' '.$order->getPerformer()->getFullName().' - '.$order->getPerformer()->getEmail();
        $this->setNotification($order, $order->getUsers(), self::NOTIFICATION_CHANGE_STATUS, $messageStr3);

        // Send push notification
        $pushNotification->sendCustomerPushNotification($message3, $messageStr3, 'https://smcentr.su/', $order->getUsers());

        // Set new order
        $order->getPerformer()->setBalance($newMasterBalance);
        $entityManager->persist($order);

        $entityManager->flush();

        if ($order->getUsers()->isGetNotifications() == 1) {
            // Mail to owner of the order
            //$subject = $translator->trans('Your order taked to work', array(), 'messages');
            if (isset($tax)) {
                $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $tax . ' руб. комиссии.';
            } else {
                $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
            }
            $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_taked_to_work.html.twig', $order);
        }

        //$message = $translator->trans('Order taked', array(), 'flash');
        if (isset($tax)) {
            $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $tax . ' руб. комиссии.';
        } else {
            $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
        }

        $notifier->send(new Notification($message, ['browser']));
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    private function clearOrderPerfomer($order)
    {
        $order->setPerformer(null);
        $order->setStatus(self::STATUS_NEW);
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();
    }
}
