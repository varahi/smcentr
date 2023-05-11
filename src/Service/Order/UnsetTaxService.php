<?php

namespace App\Service\Order;

use App\Repository\ProjectRepository;
use App\Repository\TaxRateRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class UnsetTaxService
{
    private const CREATED_BY_CLIENT = '1';

    private const CREATED_BY_COMPANY = '3';

    private $projectId;

    public function __construct(
        TaxRateRepository $taxRateRepository,
        NotifierInterface $notifier,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        RouterInterface $router,
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        int $projectId
    ) {
        $this->taxRateRepository = $taxRateRepository;
        $this->notifier = $notifier;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->userRepository = $userRepository;
        $this->projectRepository = $projectRepository;
        $this->projectId = $projectId;
    }
    public function unsetClientTax($order)
    {
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT) {
            $taxRate = $this->taxRateRepository->findByCityAndProfession($order->getCity(), $order->getProfession()) ?? null;
            if (!$taxRate) {
                $message = $this->translator->trans('No task defined', array(), 'flash');
                $this->notifier->send(new Notification($message, ['browser']));
                return new RedirectResponse($this->router->generate('app_orders_list'));
            }
            $tax = $order->getPrice() * $taxRate->getPercent(); // For example 2880 * 0.0
            $user = $order->getPerformer();
            if ($user) {
                $newMasterBalance = $user->getBalance() + $tax;
                $user->setBalance($newMasterBalance);
            }
            $entityManager = $this->doctrine->getManager();
            $entityManager->flush($order);
            $entityManager->flush($user);
        }

        return 0;
    }

    public function unsetCompanyTax($order)
    {
        $orderTaxRate = 0;
        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $orderTaxRate = $order->getCustomTaxRate(); // roubles
            $tax = $order->getPrice() * $company->getServiceTaxRate(); // percents

            $user = $order->getPerformer();
            $newMasterBalance = $user->getBalance() + $tax + $orderTaxRate;
            $currentCompanyBalance = (float)$company->getBalance();
            $newCompanyBalance = $currentCompanyBalance - $orderTaxRate;
            $company->setBalance($newCompanyBalance);
            $user->setBalance($newMasterBalance);

            $entityManager = $this->doctrine->getManager();
            $entityManager->flush($order);
            $entityManager->flush($user);
        }
    }

    public function unsetSystemTax($order)
    {
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT) {
            $taxRate = $this->taxRateRepository->findByCityAndProfession($order->getCity(), $order->getProfession()) ?? null;
            $tax = $order->getPrice() * $taxRate->getPercent(); // For example 2880 * 0.0
        }

        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $tax = $order->getPrice() * $company->getServiceTaxRate(); // percents
        }

        if (isset($tax)) {
            $project = $this->projectRepository->findOneBy(['id' => $this->projectId]);
            $currentProjectBalance = (float)$project->getBalance();
            $newProjectBalance = $currentProjectBalance - $tax;
            $project->setBalance($newProjectBalance);

            $entityManager = $this->doctrine->getManager();
            $entityManager->flush($project);
        }
    }
}
