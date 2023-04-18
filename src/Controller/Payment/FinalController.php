<?php

namespace App\Controller\Payment;

use App\Repository\PaymentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinalController extends AbstractController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public const STATUS_PAID = '1';

    public const STATUS_ERROR = '9';

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
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ) {
        $this->security = $security;
        $this->merchantName = $merchantName;
        $this->merchantId = $merchantId;
        $this->terminalId = $terminalId;
        $this->terminalPass = $terminalPass;
        $this->translator = $translator;
        $this->notifier = $notifier;
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/payment-success", name="app_payment_success")
     * @return Response
     */
    public function getSuccessResponse(
        Request $request,
        PaymentRepository $paymentRepository
    ): Response {

        // Redirect if user not master
        if (!$this->isGranted(self::ROLE_MASTER)) {
            $message = $this->translator->trans('Please login', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        $user = $this->security->getUser();
        $params = $request->query->all();
        if ($params['Success'] == true) {
            $payment = $paymentRepository->findOneBy(['id' => $params['OrderId']]);

            if (!$payment) {
                $message = $this->translator->trans('Payment Error', array(), 'flash');
                $this->notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_master_profile");
            }

            $payment->setStatus(self::STATUS_PAID);
            $entityManager = $this->doctrine->getManager();
            $user->setBalance($user->getBalance() + $params['Amount']/100);
            $entityManager->flush();

            $message = $this->translator->trans('Payment Success', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
        } else {
            $message = $this->translator->trans('Payment Error', array(), 'flash');
            $this->notifier->send(new Notification($message, ['browser']));
        }

        return $this->redirectToRoute("app_master_profile");
    }

    /**
     * @Route("/payment-error", name="app_payment_error")
     * @return Response
     */
    public function getErrorResponse(): Response {
        $message = $this->translator->trans('Payment Error', array(), 'flash');
        $this->notifier->send(new Notification($message, ['browser']));
        return $this->redirectToRoute("app_master_profile");
    }
}
