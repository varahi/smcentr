<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Repository\OrderRepository;
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

class ListOrderController extends AbstractController
{
    use NotificationTrait;

    public const STATUS_NEW = '0';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    private $doctrine;

    private $projectId;

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
     * @Route("/orders-list", name="app_orders_list")
     */
    public function list(
        Request $request,
        OrderRepository $orderRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();

            if ($user->getProfessions() && count($user->getProfessions()) > 0) {
                foreach ($user->getProfessions() as $profession) {
                    $professionIds[] = $profession->getId();
                }
            } else {
                $professionIds = [];
            }

            if ($user->getJobTypes() && count($user->getJobTypes()) > 0) {
                foreach ($user->getJobTypes() as $jobType) {
                    $jobTypeIds[] = $jobType->getId();
                }
            } else {
                $jobTypeIds = [];
            }

            if ($user->getCity()->getId()) {
                $cityId = $user->getCity()->getId();
            } else {
                $cityId = null;
            }

            $newOrders = $orderRepository->findAllByStatusProfessionJobTypesAndCity(self::STATUS_NEW, $professionIds, $jobTypeIds, $cityId, $user);

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
     * @Route("/history", name="app_history")
     */
    public function history(): Response
    {
        $user = $this->security->getUser();
        return $this->render('order/order-history.html.twig', [
            'user' => $user,
        ]);
    }
}
