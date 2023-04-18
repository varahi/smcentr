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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pada\Tinkoff\Payment\PaymentClient;
use function Pada\Tinkoff\Payment\Functions\newPayment;
use function Pada\Tinkoff\Payment\Functions\newReceipt;
use function Pada\Tinkoff\Payment\Functions\newReceiptItem;

class TinkoffController extends AbstractController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    private $terminalId;

    private $terminalPass;

    /**
     * @param Security $security
     */
    public function __construct(
        Security $security,
        string $merchantName,
        string $merchantId,
        string $terminalId,
        string $terminalPass,
        TranslatorInterface $translator,
        NotifierInterface $notifier
    ) {
        $this->security = $security;
        $this->merchantName = $merchantName;
        $this->merchantId = $merchantId;
        $this->terminalId = $terminalId;
        $this->terminalPass = $terminalPass;
        $this->translator = $translator;
        $this->notifier = $notifier;
    }

    /**
     * @Route("/payment", name="app_payment")
     * @return Response
     */
    public function newPayment(
        ManagerRegistry $doctrine
    ): Response {

        // Redirect if user not master
        if (!$this->isGranted(self::ROLE_MASTER)) {
            $message = $this->translator->trans('Please login', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        // Redicrect if payment so small
        // счет ведется в копейках
        $amount = $_POST['payment']['amount']*100;
        if ($amount < 1) {
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

        $entityManager = $doctrine->getManager();
        $entityManager->persist($payment);
        $entityManager->flush();

        // 4300 0000 0000 0777 11/22 111
        $result = $this->paymentProcess($payment);

        if ($result->isSuccess()) {
            header('Location: '.$result->getPaymentURL());
        } else {
            $message = $this->translator->trans('Payment Error', array(), 'flash');
            $message .= 'Error: '.$result->getMessage();
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_master_profile");
        }


        return new Response();
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
                ->email('info@smcentr.su')
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

    /**
     * @Route("/payment-success", name="app_payment_success")
     * @return Response
     */
    public function getSuccessResponse(
        Request $request,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        file_put_contents('success.txt', $data);
        dd($data);
    }

    /**
     * @Route("/payment-error", name="app_payment_error")
     * @return Response
     */
    public function getErrorResponse(
        Request $request,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        file_put_contents('error.txt', $data);
        dd($data);
    }
}
