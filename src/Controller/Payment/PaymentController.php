<?php

namespace App\Controller\Payment;

use App\Entity\Payment;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Pada\Tinkoff\Payment\PaymentClient;
use function Pada\Tinkoff\Payment\Functions\newPayment;
use function Pada\Tinkoff\Payment\Functions\newReceipt;
use function Pada\Tinkoff\Payment\Functions\newReceiptItem;

class PaymentController extends AbstractController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public const STATUS_NEW = '0';

    public const STATUS_ERROR = '9';

    private $terminalId;

    private $terminalPass;

    private $minAmount;

    /**
     * @param Security $security
     */
    public function __construct(
        Security $security,
        string $terminalId,
        string $terminalPass,
        string $minAmount,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ) {
        $this->security = $security;
        $this->terminalId = $terminalId;
        $this->terminalPass = $terminalPass;
        $this->translator = $translator;
        $this->notifier = $notifier;
        $this->doctrine = $doctrine;
        $this->minAmount = $minAmount;
    }

    /**
     * @Route("/payment", name="app_payment")
     * @return Response
     */
    public function newPayment(): Response
    {

        // Redirect if user not master
        if (!$this->isGranted(self::ROLE_MASTER)) {
            $message = $this->translator->trans('Please login', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        // Redicrect if payment so small
        // счет ведется в копейках
        $minAmount = $this->minAmount * 100;
        $amount = $_POST['payment']['amount']*100;
        if ($amount < $minAmount) {
            $message = $this->translator->trans('Amount of payment', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_top_up_balance");
        }

        // Create payment and save into database
        $user = $this->security->getUser();
        $payment = new Payment();
        $payment->setTitle('Payment from ' . $user->getFullname());
        $payment->setUser($user);
        $payment->setAmount($amount);
        $payment->setStatus(self::STATUS_NEW);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($payment);
        $entityManager->flush();

        $result = $this->paymentProcess($payment);

        if ($result->isSuccess()) {
            return $this->redirect($result->getPaymentURL());
        } else {
            $message = $this->translator->trans('Payment Error', array(), 'flash');
            $message .= ' '.$result->getMessage();
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_master_profile");
        }
    }

    private function paymentProcess($payment)
    {
        // Create payment client
        $paymentClient = PaymentClient::create($this->terminalId, $this->terminalPass);

        // Create payment object
        $payment = newPayment()
            ->orderId($payment->getId())
            ->oneStep()
            ->receipt(newReceipt()
                ->email($payment->getUser()->getEmail())
                ->taxationOSN()
                ->addItem(newReceiptItem()
                    ->name($payment->getTitle())
                    ->price($payment->getAmount())
                    ->quantity(1)
                    ->taxNone()
                    ->build())
                ->build())
            ->build();

        // Call API
        $result = $paymentClient->init($payment);
        return $result;
    }
}
