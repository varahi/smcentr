<?php

namespace App\Controller;

use App\Form\NotificationFormType;
use App\Repository\CityRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Notification as UserNotification;

class NotificationController extends AbstractController
{
    public const NOTIFICATION_CHANGE_STATUS = '1';

    public const NOTIFICATION_BALANCE_PLUS = '2';

    public const NOTIFICATION_BALANCE_MINUS = '3';

    public const NOTIFICATION_NEW_ORDER = '4';

    public const NOTIFICATION_MAILING = '10';

    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    private $doctrine;

    /**
     * @var Security
     */
    private $security;

    private $twig;

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
     * @Route("/notification", name="app_notification")
     */
    public function index(): Response
    {
        return $this->render('notification/index.html.twig', [
            'controller_name' => 'NotificationController',
        ]);
    }

    /**
     *
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @Route("/new-notification", name="app_new_notification")
     */
    public function newNotification(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        CityRepository $cityRepository,
        ProfessionRepository $professionRepository,
        UserRepository $userRepository
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN)) {
            $user = $this->security->getUser();
            $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
            $professions = $professionRepository->findAllOrder(['name' => 'ASC']);

            $userNotification = new UserNotification();
            $form = $this->createForm(NotificationFormType::class, $userNotification);
            $form->handleRequest($request);

            $entityManager = $this->doctrine->getManager();

            if ($form->isSubmitted()) {
                $post = $request->request->get('notification_form');
                if ($post['recipient'] == 10) {
                    // Notifications to masters of the chosen profession and the chosen city
                    if ($post['city'] =='' || $post['profession'] =='') {
                        //$message = $translator->trans('Mismatch password', array(), 'flash');
                        $message = 'Выберите город и профессию';
                        $notifier->send(new Notification($message, ['browser']));
                        return $this->redirectToRoute("app_new_notification");
                    }
                    $city = $cityRepository->findOneBy(['id' => $post['city']]);
                    $profession = $professionRepository->findOneBy(['id' => $post['profession']]);
                    //$professions = array($profession->getId());

                    // Find all masters by city and profession
                    $users = $userRepository->findByCityAndProfession(self::ROLE_MASTER, $city, $profession);

                    if (count($users) > 0) {
                        foreach ($users as $user) {
                            $userNotification = new UserNotification();
                            $userNotification->setUser($user);
                            $userNotification->setMessage($post['message']);
                            $userNotification->setType(self::NOTIFICATION_MAILING);
                            $userNotification->setIsRead('0');
                            $entityManager->persist($userNotification);
                            $entityManager->flush();
                        }
                    }
                }

                $message = $translator->trans('Notifications sent', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_new_notification');
            }

            $response = new Response($this->twig->render('notification/new.html.twig', [
                'user' => $user,
                'cities' => $cities,
                'professions' => $professions,
                'form' => $form->createView()
            ]));

            return $response;
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
