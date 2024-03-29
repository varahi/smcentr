<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Repository\UserRepository;
use App\Service\Order\GetTaxService;
use App\Service\Order\SetBalanceService;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AssignMasterController extends AbstractController
{
    use NotificationTrait;

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const STATUS_ACTIVE = '1';

    public const NOTIFICATION_CHANGE_STATUS = '1';

    private const CREATED_BY_COMPANY = '3';

    private const CREATED_BY_CLIENT = '1';

    private $projectId;

    private $doctrine;

    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        GetTaxService $getTaxService,
        SetBalanceService $setBalanceService,
        int $projectId,
        NotifierInterface $notifier,
        TranslatorInterface $translator
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
        $this->getTaxService = $getTaxService;
        $this->setBalanceService = $setBalanceService;
        $this->notifier = $notifier;
        $this->translator = $translator;
    }

    /**
     * @Route("/assign-master/order-{id}", name="app_assign_master")
     */
    public function assignMaster(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        UserRepository $userRepository,
        PushNotification $pushNotification
    ): Response {
        if (!$this->security->isGranted(self::ROLE_COMPANY)) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        $user = $this->security->getUser();
        if ($user->getId() !== $order->getUsers()->getId()) {
            // Redirect if order and client is not owner
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($_POST['assign_master1'] == "" && $_POST['assign_master2'] == "") {
                $message = $translator->trans('Choose master', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }
            if ($_POST['assign_master1']) {
                $master = $userRepository->findOneBy(['id' => $_POST['assign_master1']]);
            }
            if ($_POST['assign_master2']) {
                $master = $userRepository->findOneBy(['id' => $_POST['assign_master2']]);
            }

            if (isset($master)) {
                // Set order to master
                $order->setPerformer($master);
                $order->setStatus(self::STATUS_ACTIVE);
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($order);


                $tax = $this->getTaxService->getTax($order);

                //$this->redirectBalanceService->redirectByBalance($order); // ToDO: try to set redirect via service
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
                    $performer = $order->getPerformer();
                    if ($performer->getBalance() <= $tax + $orderTaxRate) {
                        $message = $this->translator->trans('Please top up balance', array(), 'flash');
                        $this->notifier->send(new Notification($message, ['browser']));
                        return new RedirectResponse($this->urlGenerator->generate('app_top_up_balance'));
                    }
                }

                if (!isset($tax)) {
                    $message = $translator->trans('No task defined', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    $referer = $request->headers->get('referer');
                    return new RedirectResponse($referer);
                }
                $this->setBalanceService->setBalance($order);

                $entityManager->flush();

                // Send notification to master
                $message = $translator->trans('The company has assigned you a task', array(), 'messages');
                $this->setNotification($order, $master, self::NOTIFICATION_CHANGE_STATUS, $message);

                // Send push notification
                $pushNotification->sendCustomerPushNotification(
                    $translator->trans('The company has assigned you a task', array(), 'flash'),
                    $message,
                    'https://smcentr.su/',
                    $master
                );

                $entityManager->flush();

                // Flash and redirect
                $message = $translator->trans('Master assigned to order', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_company_profile');
            }
        }

        $response = new Response($this->twig->render('user/company/assign_master.html.twig', [
            'user' => $user,
            'order' => $order,
            'companyMasters' => $userRepository->findByCompany(self::ROLE_MASTER, $user),
            'allMasters' => $userRepository->findByProfessionAndJobType(self::ROLE_MASTER, $order->getProfession(), $order->getJobType())
        ]));

        return $response;
    }
}
