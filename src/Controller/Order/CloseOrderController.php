<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Repository\FirebaseRepository;
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

class CloseOrderController extends AbstractController
{
    use NotificationTrait;

    public const STATUS_COMPLETED = '9';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

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
        int $projectId,
        PushNotification $pushNotification,
        FirebaseRepository $firebaseRepository
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
        $this->pushNotification = $pushNotification;
        $this->firebaseRepository = $firebaseRepository;
    }

    /**
     * @Route("/close-order/order-{id}", name="app_close_order")
     */
    public function closeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        Mailer $mailer,
        PushNotification $pushNotification
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();

            // Redirect if order not owner
            if ($order->getPerformer()->getId() !== $user->getId()) {
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_login');
            }

            // Persist data
            $entityManager = $this->doctrine->getManager();
            $order->setStatus(self::STATUS_COMPLETED);
            $order->setClosed(new \DateTime());
            $entityManager->flush();

            // Mail to owner for close order
            if ($this->security->isGranted(self::ROLE_MASTER)) {
                if ($order->getUsers()->isGetNotifications() == 1) {
                    $subject = $translator->trans('Your order closed by perfomer', array(), 'messages');
                    $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_closed_by_performer.html.twig', $order);

                    // Set notification for master
                    $message = $translator->trans('Notification order closed', array(), 'messages');
                    $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_CHANGE_STATUS, $message);
                }
            }

            if ($this->security->isGranted(self::ROLE_CLIENT)) {
                if ($order->getUsers()->isGetNotifications() == 1) {
                    $subject = $translator->trans('Your order closed by client', array(), 'messages');
                    $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_closed_by_client.html.twig', $order);

                    // Send notification for user
                    $message2 = $translator->trans('Notification order closed', array(), 'messages');
                    $this->setNotification($order, $order->getUsers(), self::NOTIFICATION_CHANGE_STATUS, $message2);
                }
            }

            $context = [
                'title' => $translator->trans('Notification order closed', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];

            if ($order->getUsers()) {
                $ownerTokens = $this->firebaseRepository->findAllByUsers($order->getUsers()); // Tokens for owner
                $this->pushNotification->sendMQPushNotification($translator->trans('Order closed', array(), 'flash'), $context, $ownerTokens);
            }
            if ($order->getPerformer()) {
                $masterTokens = $this->firebaseRepository->findAllByUsers($order->getPerformer()); // Tokens for master
                $this->pushNotification->sendMQPushNotification($translator->trans('Order closed', array(), 'flash'), $context, $masterTokens);
            }

            $entityManager->flush();

            $message = $translator->trans('Order closed', array(), 'flash');
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
