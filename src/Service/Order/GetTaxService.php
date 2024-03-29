<?php

namespace App\Service\Order;

use App\Repository\TaxRateRepository;
use App\Repository\UserRepository;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class GetTaxService
{
    private const CREATED_BY_CLIENT = '1';

    private const CREATED_BY_COMPANY = '3';

    private const CREATED_BY_ADMIN = '10';

    public function __construct(
        TaxRateRepository $taxRateRepository,
        NotifierInterface $notifier,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        Security $security,
        RouterInterface $router
    ) {
        $this->taxRateRepository = $taxRateRepository;
        $this->notifier = $notifier;
        $this->userRepository = $userRepository;
        $this->translator = $translator;
        $this->security = $security;
        $this->router = $router;
    }

    public function getTax($order)
    {
        // Tax from order created by client
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT) {
            $taxRate = $this->taxRateRepository->findByCityAndProfession($order->getCity(), $order->getProfession()) ?? null;
            if (!$taxRate) {
                $message = $this->translator->trans('No task defined', array(), 'flash');
                $this->notifier->send(new Notification($message, ['browser']));
                return new RedirectResponse($this->router->generate('app_orders_list'));
            }
            $tax = $order->getPrice() * $taxRate->getPercent(); // For example 2880 * 0.0
        }

        if ($order->getTypeCreated() == self::CREATED_BY_ADMIN) {
            $tax = $order->getCustomTaxRate();
        }

        // Tax from order created by company
        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            // Client logick
            $company = $this->userRepository->findOneBy(['id' => $order->getUsers()->getId()]);
            $tax = $order->getPrice() * $company->getServiceTaxRate(); // percents
        }

        return $tax;
    }
}
