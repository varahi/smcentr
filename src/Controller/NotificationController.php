<?php

namespace App\Controller;

use App\Form\NotificationFormType;
use App\Repository\CityRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Service\PushNotification;
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
use App\Controller\Traits\NotificationTrait;

class NotificationController extends AbstractController
{
    use NotificationTrait;

    public const NOTIFICATION_MAILING = '10';

    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

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
        UserRepository $userRepository,
        PushNotification $pushNotification
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN)) {
            $user = $this->security->getUser();
            $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
            $professions = $professionRepository->findAllOrder(['name' => 'ASC']);

            $userNotification = new UserNotification();
            $form = $this->createForm(NotificationFormType::class, $userNotification);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $post = $request->request->get('notification_form');

                // Notifications to masters of the chosen profession and the chosen city
                if ($post['notificationType'] == 10) {
                    $this->masterNotificationByProfessionAndCity($request, $notifier, $cityRepository, $professionRepository, $userRepository, $pushNotification);
                }

                // Notifications to clients of the chosen city
                if ($post['notificationType'] == 20) {
                    $this->notificationByCity($request, $notifier, $cityRepository, $userRepository, self::ROLE_CLIENT, $pushNotification);
                }

                // Notifications to masters of the chosen city
                if ($post['notificationType'] == 30) {
                    $this->notificationByCity($request, $notifier, $cityRepository, $userRepository, self::ROLE_MASTER, $pushNotification);
                }

                // Notifications to all masters
                if ($post['notificationType'] == 40) {
                    $this->notificationAllUsersByRole($request, $userRepository, self::ROLE_MASTER, $pushNotification);
                }

                // Notifications to all clients
                if ($post['notificationType'] == 50) {
                    $this->notificationAllUsersByRole($request, $userRepository, self::ROLE_CLIENT, $pushNotification);
                }

                // Notifications to companies of the chosen city
                if ($post['notificationType'] == 60) {
                    $this->notificationByCity($request, $notifier, $cityRepository, $userRepository, self::ROLE_COMPANY, $pushNotification);
                }

                // Notifications to all companies
                if ($post['notificationType'] == 70) {
                    $this->notificationAllUsersByRole($request, $userRepository, self::ROLE_COMPANY, $pushNotification);
                }

                // Notifications to all users
                if ($post['notificationType'] == 80) {
                    $this->notificationAllUsers($request, $userRepository, $pushNotification);
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
