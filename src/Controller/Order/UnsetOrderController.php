<?php

namespace App\Controller\Order;

use App\Entity\Order;
use App\Service\Order\UnsetTaxService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnsetOrderController extends AbstractController
{
    public const STATUS_NEW = '0';

    private const CREATED_BY_COMPANY = '3';

    public function __construct(
        ManagerRegistry $doctrine,
        UnsetTaxService $unsetTaxService,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->unsetTaxService = $unsetTaxService;
        $this->translator = $translator;
    }

    /**
     * @Route("/unset-order/order-{id}", name="app_unset_order")
     */
    public function unsetOrder(
        Request $request,
        NotifierInterface $notifier,
        Order $order
    ): Response {
        if ($order->getTypeCreated() !== self::CREATED_BY_COMPANY) {
            $message = $this->translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $this->clearOrderPerfomer($order);

        $message = 'Заказ снят с мастера';
        $notifier->send(new Notification($message, ['browser']));
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /*
    * This method should be public because it uses not only this class
    */
    public function clearOrderPerfomer($order)
    {
        $this->unsetTaxService->unsetClientTax($order);
        $this->unsetTaxService->unsetCompanyTax($order);
        $this->unsetTaxService->unsetSystemTax($order);

        $order->setPerformer(null);
        $order->setClearOrder(false);
        $order->setStatus(self::STATUS_NEW);
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush($order);
    }
}
