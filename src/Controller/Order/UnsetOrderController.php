<?php

namespace App\Controller\Order;

use App\Service\Order\UnsetTaxService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UnsetOrderController extends AbstractController
{
    public const STATUS_NEW = '0';

    public function __construct(
        ManagerRegistry $doctrine,
        UnsetTaxService $unsetTaxService
    ) {
        $this->doctrine = $doctrine;
        $this->unsetTaxService = $unsetTaxService;
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
