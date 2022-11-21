<?php

namespace App\Service;

class Firebase
{
    private $firebaseApiKey;

    /**
     * @param string $firebaseApiKey
     */
    public function __construct(
        string $firebaseApiKey
    ) {
        $this->firebaseApiKey = $firebaseApiKey;
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


    public function sendPushNotification(array $tokens, $message)
    {
        ignore_user_abort();
        ob_start();

        $url = 'https://fcm.googleapis.com/fcm/send';
        //FCM requires registration_ids array to have correct indexes, starting from 0
        $tokens = array_values($tokens);
        if (count($tokens) == 1) {
            $fields = [
                'to' => $tokens[0],
                'data' => $message,
            ];
        } elseif (count($tokens) >1) {
            $fields = [
                'registration_ids' => $tokens,
                'data' => $message,
            ];
        }

        $headers = [
            'Authorization:key='.$this->firebaseApiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === false) {
            die('Curl failed ' . curl_error());
        }

        curl_close($ch);
        return $result;
        ob_flush();
    }
}
