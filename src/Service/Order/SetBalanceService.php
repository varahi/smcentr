<?php

namespace App\Service\Order;

use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;

class SetBalanceService
{
    private const CREATED_BY_CLIENT = '1';

    private const CREATED_BY_COMPANY = '3';

    private $projectId;

    public function __construct(
        UserRepository $userRepository,
        GetTaxService $getTaxService,
        ManagerRegistry $doctrine,
        ProjectRepository $projectRepository,
        int $projectId
    ) {
        $this->userRepository = $userRepository;
        $this->getTaxService = $getTaxService;
        $this->doctrine = $doctrine;
        $this->projectRepository = $projectRepository;
        $this->projectId = $projectId;
    }

    public function setBalance($order)
    {
        $user = $order->getPerformer();
        $tax = $this->getTaxService->getTax($order);

        // If order created by client
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT) {
            $newMasterBalance = $user->getBalance() - $tax;
        }

        // If order created by company
        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $orderTaxRate = $order->getCustomTaxRate();
            $newMasterBalance = $user->getBalance() - $tax - $orderTaxRate;

            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $currentCompanyBalance = (float)$company->getBalance();
            $newCompanyBalance = $currentCompanyBalance + $orderTaxRate;
            $company->setBalance($newCompanyBalance);
        }

        $project = $this->projectRepository->findOneBy(['id' => $this->projectId]);
        $currentProjectBalance = (float)$project->getBalance();
        $newProjectBalance = $currentProjectBalance + $tax;

        $user->setBalance($newMasterBalance);
        $project->setBalance($newProjectBalance);

        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();
    }
}
