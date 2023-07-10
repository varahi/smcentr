<?php

namespace App\Service\Order;

use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SetBalanceService
{
    private const CREATED_BY_CLIENT = '1';

    private const CREATED_BY_COMPANY = '3';

    private const CREATED_BY_ADMIN = 10;

    private $projectId;

    public function __construct(
        UserRepository $userRepository,
        GetTaxService $getTaxService,
        ManagerRegistry $doctrine,
        ProjectRepository $projectRepository,
        int $projectId,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        RouterInterface $router
    ) {
        $this->userRepository = $userRepository;
        $this->getTaxService = $getTaxService;
        $this->doctrine = $doctrine;
        $this->projectRepository = $projectRepository;
        $this->projectId = $projectId;
        $this->translator = $translator;
        $this->notifier = $notifier;
        $this->router = $router;
    }

    public function setBalance($order)
    {
        $performer = $order->getPerformer();
        $tax = $this->getTaxService->getTax($order);
        $orderTaxRate = $order->getCustomTaxRate();

        // Redirect if performer balance not enought
        if ($performer->getBalance() <= ($tax + $orderTaxRate)) {
            $message = $this->translator->trans('Not enought balance', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
            return new RedirectResponse($this->router->generate('app_top_up_balance'));
        }

        // If order created by client or created by admin from backend
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT || $order->getTypeCreated() == self::CREATED_BY_ADMIN) {
            $newMasterBalance = $performer->getBalance() - $tax;
        }

        // If order created by company
        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $newMasterBalance = $performer->getBalance() - $tax - $orderTaxRate;
            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $currentCompanyBalance = (float)$company->getBalance();
            $newCompanyBalance = $currentCompanyBalance + $orderTaxRate;
            $company->setBalance($newCompanyBalance);
        }

        $project = $this->projectRepository->findOneBy(['id' => $this->projectId]);
        $currentProjectBalance = (float)$project->getBalance();
        $newProjectBalance = $currentProjectBalance + $tax;

        $performer->setBalance($newMasterBalance);
        $project->setBalance($newProjectBalance);

        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();

        return 0;
    }
}
