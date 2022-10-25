<?php

namespace App\Controller;

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

class OrderController extends AbstractController
{
    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

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
        JobTypeRepository $jobTypeRepository
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

                            $masterNotification = new UserNotification();
                            $masterNotification->setUser($master);
                            $message = $translator->trans('Notification new order for master', array(), 'messages');
                            $masterNotification->setMessage($message);
                            $masterNotification->setApplication($order);
                            $masterNotification->setType(self::NOTIFICATION_NEW_ORDER);
                            $masterNotification->setIsRead('0');
                            $entityManager->persist($masterNotification);
                            $entityManager->flush();
                        }
                    }
                }

                // Send notifications for user
                $userNotification = new UserNotification();
                $userNotification->setUser($user);
                $userNotification->setMessage($translator->trans('Notification new order for user', array(), 'messages'));
                $userNotification->setApplication($order);
                $userNotification->setType(self::NOTIFICATION_NEW_ORDER);
                $userNotification->setIsRead('0');
                $entityManager->persist($userNotification);
                $entityManager->flush();

                $message = $translator->trans('Order created', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_client_profile');
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
        Mailer $mailer
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
                if ($order->getUsers()->isGetNotifications() == 1) {
                    $subject = $translator->trans('Your order closed by perfomer', array(), 'messages');
                    $mailer->sendUserEmail($order->getUsers(), $subject, 'emails/order_closed_by_performer.html.twig', $order);
                }

                // Send notification for master
                $masterNotification = new UserNotification();
                $masterNotification->setUser($order->getPerformer());
                $masterNotification->setMessage($translator->trans('Notification order closed', array(), 'messages'));
                $masterNotification->setApplication($order);
                $masterNotification->setType(self::NOTIFICATION_CHANGE_STATUS);
                $masterNotification->setIsRead('0');

                // Send notification for user
                $userNotification = new UserNotification();
                $userNotification->setUser($order->getUsers());
                $userNotification->setMessage($translator->trans('Notification order closed', array(), 'messages'));
                $userNotification->setApplication($order);
                $userNotification->setType(self::NOTIFICATION_CHANGE_STATUS);
                $userNotification->setIsRead('0');

                $entityManager->persist($masterNotification);
                $entityManager->persist($userNotification);
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
        Mailer $mailer
    ): Response {
        if ($this->security->isGranted(self::ROLE_MASTER)) {
            $entityManager = $this->doctrine->getManager();
            $user = $this->security->getUser();
            $order->setPerformer($user);
            $order->setStatus(self::STATUS_ACTIVE);
            $entityManager->flush();

            // Set balance for master
            $masterBalance = (float)$order->getPerformer()->getBalance();
            if ($masterBalance == null || $masterBalance == 0) {
                // Redirect if order or performer not owner
                $message = $translator->trans('Please top up balance', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_master_top_up_balance');
            }

            // Calculate tax rate depends on city and profession
            if (count($order->getCity()->getTaxRates()) > 0) {
                foreach ($order->getCity()->getTaxRates() as $taxRate) {
                    if ($taxRate->getProfession()->getId() == $order->getProfession()->getId()) {
                        $tax = $order->getPrice() * $taxRate->getPercent();
                        $newMasterBalance = $order->getPerformer()->getBalance() - $tax;
                        if ($order->getPerformer()->getBalance() <= $tax) {
                            // Redirect if order or performer not owner
                            $message = $translator->trans('Please top up balance', array(), 'flash');
                            $notifier->send(new Notification($message, ['browser']));
                            return $this->redirectToRoute('app_master_top_up_balance');
                        } else {

                            // Send notifications for masters
                            $masterNotification = new UserNotification();
                            $masterNotification->setUser($order->getPerformer());
                            $message = $translator->trans('Withdrawal from the balance', array(), 'messages');
                            $masterNotification->setMessage($message .' '.$tax.' руб.' .' за заявку');
                            $masterNotification->setType(self::NOTIFICATION_BALANCE_MINUS);
                            $masterNotification->setApplication($order);
                            $masterNotification->setIsRead('0');

                            $masterNotification2 = new UserNotification();
                            $masterNotification2->setUser($order->getPerformer());
                            $masterNotification2->setMessage($translator->trans('You got an order', array(), 'messages'));
                            $masterNotification2->setType(self::NOTIFICATION_CHANGE_STATUS);
                            $masterNotification2->setApplication($order);
                            $masterNotification2->setIsRead('0');

                            $entityManager->persist($masterNotification);
                            $entityManager->persist($masterNotification2);

                            // Send notifications for user
                            $userNotification = new UserNotification();
                            $userNotification->setUser($order->getUsers());
                            $message = $translator->trans('Your order has been processed', array(), 'messages');
                            $userNotification->setMessage($message .' '.$order->getPerformer()->getFullName().' - '.$order->getPerformer()->getEmail());
                            $userNotification->setType(self::NOTIFICATION_CHANGE_STATUS);
                            $userNotification->setApplication($order);
                            $userNotification->setIsRead('0');
                            $entityManager->persist($userNotification);

                            // Set new order
                            $order->getPerformer()->setBalance($newMasterBalance);
                            $entityManager->persist($order);

                            $entityManager->flush();
                        }
                    }
                }
                $user->setBalance($newMasterBalance);
                $entityManager->persist($user);
                $entityManager->flush();
            }

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
}
