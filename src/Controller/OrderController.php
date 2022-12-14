<?php

namespace App\Controller;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Entity\Notification as UserNotification;
use App\Form\Order\OrderFormCompanyType;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\JobTypeRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
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
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Twig\Environment;
use App\Form\Order\OrderFormType;
use App\Service\Mailer;
use App\Service\PushNotification;

class OrderController extends AbstractController
{
    use NotificationTrait;

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    public const CLIENT_CREATED = '1';

    public const MASTER_CREATED = '2';

    public const COMPANY_CREATED = '3';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const NOTIFICATION_CHANGE_STATUS = '1';

    public const NOTIFICATION_BALANCE_PLUS = '2';

    public const NOTIFICATION_BALANCE_MINUS = '3';

    public const NOTIFICATION_NEW_ORDER = '4';

    private $doctrine;

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/orders-list", name="app_orders_list")
     */
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();

            if ($user->getProfessions() && count($user->getProfessions()) > 0) {
                foreach ($user->getProfessions() as $profession) {
                    $professionIds[] = $profession->getId();
                }
            } else {
                $professionIds = [];
            }

            if ($user->getJobTypes() && count($user->getJobTypes()) > 0) {
                foreach ($user->getJobTypes() as $jobType) {
                    $jobTypeIds[] = $jobType->getId();
                }
            } else {
                $jobTypeIds = [];
            }

            if ($user->getCity()->getId()) {
                $cityId = $user->getCity()->getId();
            } else {
                $cityId = null;
            }

            $newOrders = $orderRepository->findAllByStatusProfessionJobTypesAndCity(self::STATUS_NEW, $professionIds, $jobTypeIds, $cityId);

            return $this->render('order/orders_list.html.twig', [
                'user' => $user,
                'orders' => $newOrders
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/order/new", name="app_order_new")
     */
    public function newOrder(
        Request $request,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        Mailer $mailer,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository,
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository,
        PushNotification $pushNotification
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            $masters = $userRepository->findByRole(self::ROLE_MASTER);
            $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
            $districts = $districtRepository->findAllOrder(['name' => 'ASC']);
            $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
            $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);

            $order = new Order();

            if ($this->isGranted(self::ROLE_CLIENT)) {
                $form = $this->createForm(OrderFormType::class, $order);
            }
            if ($this->isGranted(self::ROLE_COMPANY)) {
                $form = $this->createForm(OrderFormCompanyType::class, $order);
            }

            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $post = $request->request->get('order_form');
                if ($post['profession'] !=='') {
                    $profession = $professionRepository->findOneBy(['id' => $post['profession']]);
                    if ($profession) {
                        $order->setProfession($profession);
                    }
                }
                if ($post['jobType'] !=='') {
                    $jobType = $jobTypeRepository->findOneBy(['id' => $post['jobType']]);
                    if ($jobType) {
                        $order->setJobType($jobType);
                    }
                }
                if ($post['city'] !=='') {
                    $city = $cityRepository->findOneBy(['id' => $post['city']]);
                    if ($city) {
                        $order->setCity($city);
                    }
                }
                if ($post['district'] !=='') {
                    $district = $districtRepository->findOneBy(['id' => $post['district']]);
                    if ($district) {
                        $order->setDistrict($district);
                    }
                }

                if (isset($_POST['order_form_company']['sendOwnMasters']) && $_POST['order_form_company']['sendOwnMasters'] == 1) {
                    $masterNotification = new UserNotification();
                    if (count($user->getCompanyMasters()) > 0) {
                        // send notification to own masters
                        /*foreach ($user->getCompanyMasters() as $companyMaster) {
                            $masterNotification->setUser($companyMaster);
                            $message = $translator->trans('Notification new order for master', array(), 'messages');
                            $masterNotification->setMessage($message);
                            $masterNotification->setApplication($order);
                            $masterNotification->setType(self::NOTIFICATION_NEW_ORDER);
                            $masterNotification->setIsRead('0');
                            $entityManager->persist($masterNotification);
                        }*/
                    }
                }

                if (isset($_POST['order_form_company']['sendAllMasters']) && $_POST['order_form_company']['sendAllMasters'] == 1) {
                    // ToDo: send notification to all masters
                }

                if ($user != null && in_array(self::ROLE_CLIENT, $user->getRoles())) {
                    $order->setTypeCreated(self::CLIENT_CREATED);
                }

                if ($user != null && in_array(self::ROLE_COMPANY, $user->getRoles())) {
                    $order->setTypeCreated(self::COMPANY_CREATED);
                }

                $order->setStatus(self::STATUS_NEW);
                $order->setUsers($user);
                $order->setLevel('3');

                $entityManager = $doctrine->getManager();
                $entityManager->persist($order);
                $entityManager->flush();

                // Mails to master about a new order
                if (count($masters) > 0) {
                    foreach ($masters as $master) {
                        if (count($master->getProfessions()) > 0 && count($master->getJobTypes()) > 0) {
                            if ($master->getProfessions() && count($master->getProfessions()) > 0) {
                                foreach ($master->getProfessions() as $profession) {
                                    $professionIds[] = $profession->getId();
                                }
                            } else {
                                $professionIds = [];
                            }

                            if ($master->getJobTypes() && count($master->getJobTypes()) > 0) {
                                foreach ($master->getJobTypes() as $jobType) {
                                    $jobTypeIds[] = $jobType->getId();
                                }
                            } else {
                                $jobTypeIds = [];
                            }
                        } else {
                            $jobTypeIds = [];
                            $professionIds = [];
                        }

                        /*if ($master->isGetNotifications() == 1 &&
                            $order->getCity()->getId() == $master->getCity()->getId() &&
                            in_array($order->getJobType()->getId(), $jobTypeIds) &&
                            in_array($order->getProfession()->getId(), $professionIds)
                        ) {
                            $subject = $translator->trans('New order available', array(), 'messages');
                            $mailer->sendUserEmail($master, $subject, 'emails/new_order_to_master.html.twig', $order);
                        }*/

                        // Send notifications for masters
                        if ($master->isGetNotifications() == 1 &&
                            $order->getCity()->getId() == $master->getCity()->getId() &&
                            in_array($order->getJobType()->getId(), $jobTypeIds) &&
                            in_array($order->getProfession()->getId(), $professionIds)
                        ) {
                            $subject = $translator->trans('New order available', array(), 'messages');
                            $mailer->sendUserEmail($master, $subject, 'emails/new_order_to_master.html.twig', $order);

                            // Send notifications for master
                            $message = $translator->trans('Notification new order for master', array(), 'messages');
                            $this->setNotification($order, $master, self::NOTIFICATION_NEW_ORDER, $message);

                            // Send push notification
                            $pushNotification->sendPushNotification($translator->trans('New order on site', array(), 'flash'), $message, 'https://smcentr.su/');

                            $entityManager->flush();
                        }
                    }
                }

                // Send notifications for user
                $message = $translator->trans('Notification new order for user', array(), 'messages');
                $this->setNotification($order, $user, self::NOTIFICATION_NEW_ORDER, $message);

                // Send push notification
                $pushNotification->sendPushNotification($translator->trans('New order on site', array(), 'flash'), $message, 'https://smcentr.su/');

                $entityManager->flush();

                $message = $translator->trans('Order created', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            return $this->render('order/new.html.twig', [
                'user' => $user,
                'cities' => $cities,
                'districts' => $districts,
                'professions' => $professions,
                'jobTypes' => $jobTypes,
                'orderForm' => $form->createView()
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/history", name="app_history")
     */
    public function history(): Response
    {
        $user = $this->security->getUser();
        return $this->render('order/order-history.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/close-order/order-{id}", name="app_close_order")
     */
    public function closeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        Mailer $mailer,
        PushNotification $pushNotification
    ): Response {
        if ($this->security->isGranted(self::ROLE_CLIENT) || $this->security->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            if ($user->getId() == $order->getUsers()->getId() || $user->getId() == $order->getPerformer()->getId()) {

                // Persist data
                $entityManager = $this->doctrine->getManager();
                $order->setStatus(self::STATUS_COMPLETED);
                $order->setClosed(new \DateTime());
                $entityManager->flush();

                // Mail to owner for close order
                if ($this->security->isGranted(self::ROLE_MASTER)) {
                    if ($order->getUsers()->isGetNotifications() == 1) {
                        $subject = $translator->trans('Your order closed by perfomer', array(), 'messages');
                        $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_closed_by_performer.html.twig', $order);

                        // Send notification for master
                        $message = $translator->trans('Notification order closed', array(), 'messages');
                        $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_CHANGE_STATUS, $message);

                        // Send push notification
                        $pushNotification->sendPushNotification($translator->trans('Order closed', array(), 'flash'), $message, 'https://smcentr.su/');
                    }
                }

                if ($this->security->isGranted(self::ROLE_CLIENT)) {
                    if ($order->getUsers()->isGetNotifications() == 1) {
                        $subject = $translator->trans('Your order closed by client', array(), 'messages');
                        $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_closed_by_client.html.twig', $order);

                        // Send notification for user
                        $message2 = $translator->trans('Notification order closed', array(), 'messages');
                        $this->setNotification($order, $order->getUsers(), self::NOTIFICATION_CHANGE_STATUS, $message2);

                        // Send push notification
                        $pushNotification->sendPushNotification($translator->trans('Order closed', array(), 'flash'), $message2, 'https://smcentr.su/');
                    }
                }

                $entityManager->flush();

                $message = $translator->trans('Order closed', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            } else {
                // Redirect if order or performer not owner
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_login');
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/take-order/order-{id}", name="app_take_order")
     */
    public function takeOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        Mailer $mailer,
        PushNotification $pushNotification
    ): Response {
        if ($this->security->isGranted(self::ROLE_MASTER)) {
            $entityManager = $this->doctrine->getManager();
            $user = $this->security->getUser();
            $order->setPerformer($user);
            $order->setStatus(self::STATUS_ACTIVE);
            $entityManager->flush();

            // Set balance for master
            $masterBalance = (float)$order->getPerformer()->getBalance();
            /*if ($masterBalance == null || $masterBalance == 0) {
                // Redirect if order or performer not owner
                $message = $translator->trans('Please top up balance', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_top_up_balance');
            }*/

            // If order has custom tax from company
            if ($order->getCustomTaxRate()) {
                $tax = $order->getCustomTaxRate();

            // If company has tax rate (Комисси списаний)
            } elseif ($order->getUsers()->getTaxRate()) {
                $tax = $order->getPrice() * $order->getUsers()->getTaxRate();

            // If company has service Tax Rate (Комиссия сервиса)
            } elseif ($order->getUsers()->getServiceTaxRate()) {
                $tax = $order->getPrice() * $order->getUsers()->getServiceTaxRate();

            // Calculate tax rate depends on city and profession
            } else {
                if (count($order->getCity()->getTaxRates()) > 0) {
                    foreach ($order->getCity()->getTaxRates() as $taxRate) {
                        if ($taxRate->getProfession()->getId() == $order->getProfession()->getId()) {
                            $tax = $order->getPrice() * $taxRate->getPercent(); // For example 2880 * 0.05
                            $newMasterBalance = $order->getPerformer()->getBalance() - $tax;
                            if ($order->getPerformer()->getBalance() <= $tax) {
                                // Redirect if order or performer not owner
                                $message = $translator->trans('Please top up balance', array(), 'flash');
                                $notifier->send(new Notification($message, ['browser']));
                                return $this->redirectToRoute('app_top_up_balance');
                            }
                        }
                    }
                    $user->setBalance($newMasterBalance);
                    $entityManager->persist($user);
                    $entityManager->flush();
                }
            }

            // Set new master balance
            $newMasterBalance = $order->getPerformer()->getBalance() - $tax;
            $user->setBalance($newMasterBalance);
            $entityManager->persist($user);
            $entityManager->flush();

            // Send notifications for masters
            $message1 = $translator->trans('Withdrawal from the balance', array(), 'messages');
            $messageStr1 = $message1 .' '.$tax.' руб.' .' за заявку';
            $messageStr2 = $translator->trans('You got an order', array(), 'messages');
            $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_BALANCE_MINUS, $messageStr1);
            $this->setNotification($order, $order->getPerformer(), self::NOTIFICATION_CHANGE_STATUS, $messageStr2);

            // Send push notification
            $pushNotification->sendCustomerPushNotification($message1, $messageStr1, 'https://smcentr.su/', $order->getPerformer());
            $pushNotification->sendCustomerPushNotification($translator->trans('You accepted application', array(), 'flash'), $messageStr2, 'https://smcentr.su/', $order->getPerformer());

            // Send notifications for user
            $message3 = $translator->trans('Your order has been processed', array(), 'messages');
            $messageStr3 = $message3 .' '.$order->getPerformer()->getFullName().' - '.$order->getPerformer()->getEmail();
            $this->setNotification($order, $order->getUsers(), self::NOTIFICATION_CHANGE_STATUS, $messageStr3);

            // Send push notification
            $pushNotification->sendCustomerPushNotification($message3, $messageStr3, 'https://smcentr.su/', $order->getUsers());

            // Set new order
            $order->getPerformer()->setBalance($newMasterBalance);
            $entityManager->persist($order);

            $entityManager->flush();


            if ($order->getUsers()->isGetNotifications() == 1) {
                // Mail to owner of the order
                //$subject = $translator->trans('Your order taked to work', array(), 'messages');
                if (isset($tax)) {
                    $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $tax . ' руб. комиссии.';
                } else {
                    $subject = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
                }
                $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_taked_to_work.html.twig', $order);
            }

            //$message = $translator->trans('Order taked', array(), 'flash');
            if (isset($tax)) {
                $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль. С вашего баланса будет списано ' . $tax . ' руб. комиссии.';
            } else {
                $message = 'Вы успешно приняли заявку, она добавилась в ваш профиль.';
            }

            $notifier->send(new Notification($message, ['browser']));
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/assign-master/order-{id}", name="app_assign_master")
     */
    public function assignMaster(
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        UserRepository $userRepository,
        PushNotification $pushNotification
    ): Response {
        if ($this->security->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            if ($user->getId() !== $order->getUsers()->getId()) {
                // Redirect if order and client is not owner
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_login');
            }

            $masters = $userRepository->findByCompanyProfessionAndJobType(self::ROLE_MASTER, $user, $order->getProfession(), $order->getJobType());

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if ($_POST['assign_master']) {
                    $master = $userRepository->findOneBy(['id' => $_POST['assign_master']]);
                }

                if (isset($master)) {
                    // Set order to master
                    $order->setPerformer($master);
                    $order->setStatus(self::STATUS_ACTIVE);
                    $entityManager = $this->doctrine->getManager();
                    $entityManager->persist($order);
                    $entityManager->flush();

                    // Send notification to master
                    $message = $translator->trans('The company has assigned you a task', array(), 'messages');
                    $this->setNotification($order, $master, self::NOTIFICATION_CHANGE_STATUS, $message);

                    // Send push notification
                    $pushNotification->sendCustomerPushNotification($this->translator->trans('The company has assigned you a task', array(), 'flash'), $message, 'https://smcentr.su/', $master);

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
                'masters' => $masters
            ]));

            return $response;
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
