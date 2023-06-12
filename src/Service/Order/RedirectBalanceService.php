<?php

namespace App\Service\Order;

use App\Service\Order\GetTaxService;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectBalanceService
{
    private const CREATED_BY_COMPANY = '3';

    private const CREATED_BY_CLIENT = '1';

    public function __construct(
        NotifierInterface $notifier,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        GetTaxService $getTaxService
    ) {
        $this->notifier = $notifier;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->getTaxService = $getTaxService;
    }

    public function redirectByBalance($order)
    {
        $tax = $this->getTaxService->getTax($order);

        // Redirect user to top up balance
        if ($order->getTypeCreated() == self::CREATED_BY_CLIENT) {
            $performer = $order->getPerformer();
            if ($performer->getBalance() <= $tax) {
                $message = $this->translator->trans('Please top up balance', array(), 'flash');
                $this->notifier->send(new Notification($message, ['browser']));
                return new RedirectResponse($this->urlGenerator->generate('app_top_up_balance'));
            }
        }

        if ($order->getTypeCreated() == self::CREATED_BY_COMPANY) {
            $orderTaxRate = $order->getCustomTaxRate(); // roubles
            if ($performer->getBalance() <= $tax + $orderTaxRate) {
                $message = $this->translator->trans('Please top up balance', array(), 'flash');
                $this->notifier->send(new Notification($message, ['browser']));
                return new RedirectResponse($this->urlGenerator->generate('app_top_up_balance'));
            }
        }

        return null;
    }
}
