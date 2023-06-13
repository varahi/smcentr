<?php

namespace App\Controller\Order;

use App\Controller\Traits\NotificationTrait;
use App\Entity\Firebase;
use App\Entity\Order;
use App\Entity\Notification as UserNotification;
use App\Form\Order\OrderFormCompanyType;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\FirebaseRepository;
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
use Symfony\Component\Messenger\MessageBusInterface;
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

    private const DEFAULT_LEVEL = '3';

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
        int $projectId,
        Mailer $mailer,
        TranslatorInterface $translator,
        PushNotification $pushNotification,
        FirebaseRepository $firebaseRepository
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->pushNotification = $pushNotification;
        $this->firebaseRepository = $firebaseRepository;
    }

    /**
     * @Route("/order/new", name="app_order_new")
     */
    public function newOrder(
        Request $request,
        UserRepository $userRepository,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository,
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository,
        MessageBusInterface $messageBus
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
                    'level' => self::DEFAULT_LEVEL
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

                // Set non-push notifications for user
                $message = $this->translator->trans('Notification new order for user', array(), 'messages');
                $this->setNotification($order, $user, self::NOTIFICATION_NEW_ORDER, $message);

                // Send push via RabbitMQ to relevant masters
                $relevantMasters = $userRepository->findByCityAndProfession(self::ROLE_MASTER, $order->getCity(), $order->getProfession());
                if (isset($relevantMasters) && !empty($relevantMasters)) {
                    foreach ($relevantMasters as $master) {
                        $relevantMastersIds[] = $master->getId();
                        // Send notifications to relevant masters
                        $this->setNotification($order, $master, self::NOTIFICATION_NEW_ORDER, $message);
                    }
                    $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $relevantMastersIds));
                }

                if (isset($tokens) && count($tokens) > 0) {
                    $context = [
                        'title' => $this->translator->trans('Notification new order for master', array(), 'messages'),
                        'clickAction' => 'https://smcentr.su/',
                        'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
                    ];
                    $this->pushNotification->sendMQPushNotification($this->translator->trans('New order on site', array(), 'flash'), $context, $tokens);
                }

                $entityManager->flush();

                $message = $this->translator->trans('Order created', array(), 'flash');
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
            $message = $this->translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
