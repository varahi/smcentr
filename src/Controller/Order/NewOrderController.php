<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Order;
use App\Entity\Notification as UserNotification;
use App\Form\Order\OrderFormCompanyType;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\JobTypeRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Form\Order\OrderFormType;
use App\Service\Mailer;
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

class NewOrderController extends AbstractController
{
    use NotificationTrait;

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    public const CLIENT_CREATED = '1';

    public const COMPANY_CREATED = '3';

    public const STATUS_NEW = '0';

    public const NOTIFICATION_NEW_ORDER = '4';

    private $projectId;

    private $doctrine;

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        int $projectId
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
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
                $form = $this->createForm(OrderFormCompanyType::class, $order, [
                    'userId' => $user->getId(),
                ]);
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
}
