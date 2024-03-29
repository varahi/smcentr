<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use App\Entity\Firebase;
use App\Entity\NotificationGroup;
use App\Entity\Notification as UserNotification;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\CityRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
trait NotificationTrait
{
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        PushNotification $pushNotification
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->pushNotification = $pushNotification;
    }

    /**
     * @param Request $request
     * @param NotifierInterface $notifier
     * @param CityRepository $cityRepository
     * @param ProfessionRepository $professionRepository
     * @param UserRepository $userRepository
     * @return RedirectResponse|void
     */
    public function masterNotificationByProfessionAndCity(
        Request $request,
        NotifierInterface $notifier,
        CityRepository $cityRepository,
        ProfessionRepository $professionRepository,
        UserRepository $userRepository
    ) {
        $post = $request->request->get('notification_form');

        if ($post['city'] =='' || $post['profession'] =='') {
            $notifier->send(new Notification('Выберите город и профессию', ['browser']));
            return new RedirectResponse($this->urlGenerator->generate('app_administrator'));
        }
        $city = $cityRepository->findOneBy(['id' => $post['city']]);
        $profession = $professionRepository->findOneBy(['id' => $post['profession']]);

        // Find all masters by city and profession
        $users = $userRepository->findByCityAndProfession(self::ROLE_MASTER, $city, $profession);
        $this->sendNotification($post, $users);

        // Send push notification via Rabbit MQ
        if (count($users) > 0) {
            $entityManager = $this->doctrine->getManager();
            $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $users));
            $context = [
                'title' => $this->translator->trans('Website push notification', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];
            $this->pushNotification->sendMQPushNotification($post['message'], $context, $tokens);
        }
    }

    public function notificationByCity(
        Request $request,
        NotifierInterface $notifier,
        CityRepository $cityRepository,
        UserRepository $userRepository,
        $role
    ) {
        $post = $request->request->get('notification_form');
        if ($post['city'] == '') {
            $notifier->send(new Notification('Выберите город', ['browser']));
            return new RedirectResponse($this->urlGenerator->generate('app_administrator'));
        }

        // Find clients by city
        $city = $cityRepository->findOneBy(['id' => $post['city']]);
        $users = $userRepository->findByCity($role, $city);
        $this->sendNotification($post, $users);

        // Send push notification via Rabbit MQ
        if (count($users) > 0) {
            $entityManager = $this->doctrine->getManager();
            $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $users));
            $context = [
                'title' => $this->translator->trans('Website push notification', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];
            $this->pushNotification->sendMQPushNotification($post['message'], $context, $tokens);
        }
    }

    public function notificationAllUsersByRole(
        Request $request,
        UserRepository $userRepository,
        $role
    ) {
        $post = $request->request->get('notification_form');
        $users = $userRepository->findByRole($role);
        $this->sendNotification($post, $users);

        // Send push notification via Rabbit MQ
        if (count($users) > 0) {
            $entityManager = $this->doctrine->getManager();
            $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $users));
            $context = [
                'title' => $this->translator->trans('Website push notification', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];
            $this->pushNotification->sendMQPushNotification($post['message'], $context, $tokens);
        }
    }

    public function notificationAllUsers(
        Request $request,
        UserRepository $userRepository
    ) {
        $post = $request->request->get('notification_form');
        $clientUsers = $userRepository->findByRole(self::ROLE_CLIENT);
        $masterUsers = $userRepository->findByRole(self::ROLE_MASTER);
        $companyUsers = $userRepository->findByRole(self::ROLE_COMPANY);
        $users = array_merge($clientUsers, $masterUsers, $companyUsers);
        $this->sendNotification($post, $users);

        // Send push notification via Rabbit MQ
        if (count($users) > 0) {
            $entityManager = $this->doctrine->getManager();
            $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $users));
            $context = [
                'title' => $this->translator->trans('Website push notification', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];
            $this->pushNotification->sendMQPushNotification($post['message'], $context, $tokens);
        }
    }

    /**
     * @param $post
     * @param $users
     * @return void
     */
    private function sendNotification($post, $users)
    {
        $entityManager = $this->doctrine->getManager();
        if (count($users) > 0) {
            $notificationGroup = new NotificationGroup();
            $notificationGroup->setText($post['message']);
            foreach ($users as $user) {
                $userNotification = new UserNotification();
                $userNotification->setUser($user);
                $userNotification->setMessage($post['message']);
                $userNotification->setType(self::NOTIFICATION_MAILING);
                $userNotification->setIsRead((int)0);
                $notificationGroup->addNotification($userNotification);
                $entityManager->persist($userNotification);
                $entityManager->flush();
            }
            $entityManager->persist($notificationGroup);
            $entityManager->flush();
        }
    }

    /**
     * @param Order $order
     * @param User $user
     * @param string $type
     * @param string $messageStr
     * @return void
     */
    public function setNotification(
        Order $order,
        User $user,
        string $type,
        string $messageStr
    ) {
        $entityManager = $this->doctrine->getManager();
        $notification = new UserNotification();
        $notification->setUser($user);
        $notification->setMessage($messageStr);
        $notification->setType($type);
        $notification->setApplication($order);
        $notification->setIsRead((int)0);

        $entityManager->persist($notification);
    }
}
