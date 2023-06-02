<?php

namespace App\Service;

use App\Entity\Firebase;
use App\Entity\User;
use App\Message\SendPushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class PushNotification
{
    /**
     * @var string
     */
    private $firebaseApiKey;

    /**
     * @var string
     */
    private $defaultDomain;

    /**
     * @param string $firebaseApiKey
     * @param string $defaultDomain
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        string $firebaseApiKey,
        string $defaultDomain,
        ManagerRegistry $doctrine,
        MessageBusInterface $messageBus
    ) {
        $this->firebaseApiKey = $firebaseApiKey;
        $this->defaultDomain = $defaultDomain;
        $this->doctrine = $doctrine;
        $this->messageBus = $messageBus;
    }

    public function sendMQPushNotification($subject, $context)
    {
        $entityManager = $this->doctrine->getManager();
        $tokens = $entityManager->getRepository(Firebase::class)->findNonHidden()??null;
        if (count($tokens) > 0) {
            foreach ($tokens as $token) {
                $token = new SendPushNotification($token->getToken(), $subject, $context);
                $envelope = new Envelope($token, [
                    new AmqpStamp('normal')
                ]);
                $this->messageBus->dispatch($envelope);
            }
        }
    }

    /**
     * @param $title
     * @param $body
     * @param $click
     * @return void
     */
    public function sendPushNotification($title, $body, $click)
    {
        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => $this->defaultDomain . '/assets/images/logo_black.svg',
            'click_action' => $click,
        ];

        $entityManager = $this->doctrine->getManager();
        $tokens = $entityManager->getRepository(Firebase::class)->findAll();
        if (count($tokens) > 0) {
            foreach ($tokens as $key => $token) {
                $this->sendSimplePushNotification($token->getToken(), $notification);
            }
        }
    }

    /**
     * @param $title
     * @param $body
     * @param $click
     * @param User $user
     * @return void
     */
    public function sendCustomerPushNotification($title, $body, $click, User $user)
    {
        $context = [
            'title' => $title,
            'clickAction' => $click,
            'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
        ];

        $entityManager = $this->doctrine->getManager();
        $tokens = $entityManager->getRepository(Firebase::class)->findAllByUser($user);
        if (count($tokens) > 0) {
            foreach ($tokens as $key => $token) {
                //$this->sendSimplePushNotification($token->getToken(), $notification);
                $token = new SendPushNotification($token->getToken(), $body, $context);
                $envelope = new Envelope($token, [
                    new AmqpStamp('normal')
                ]);
                $this->messageBus->dispatch($envelope);
            }
        }
    }

    /**
     * @param $token
     * @param $msg
     * @return void
     */
    public function sendSimplePushNotification($token, $notification)
    {
        ignore_user_abort();
        ob_start();

        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverApiKey = $this->firebaseApiKey;
        $request_body = [
            'notification' => $notification,
            'to' => $token
        ];
        $fields = json_encode($request_body);
        $request_headers = [
            'Content-Type: application/json',
            'Authorization: key=' . $serverApiKey,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
}
