<?php

namespace App\Service;

use App\Entity\Firebase;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

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
        ManagerRegistry $doctrine
    ) {
        $this->firebaseApiKey = $firebaseApiKey;
        $this->defaultDomain = $defaultDomain;
        $this->doctrine = $doctrine;
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
            'icon' => $this->defaultDomain . '/assets/images/logo.svg',
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
        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => $this->defaultDomain . '/assets/images/logo.svg',
            'click_action' => $click,
        ];

        $entityManager = $this->doctrine->getManager();
        $tokens = $entityManager->getRepository(Firebase::class)->findAllByUser($user);
        if (count($tokens) > 0) {
            foreach ($tokens as $key => $token) {
                $this->sendSimplePushNotification($token->getToken(), $notification);
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
