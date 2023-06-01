<?php

namespace App\Message;

final class SendPushNotification
{
    private $subject;

    private $token;

    private $context;

    public function __construct(
        string $token,
        string $subject,
        array $context = []
    ) {
        $this->token = $token;
        $this->subject = $subject;
        $this->context = $context;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
