<?php

namespace App\MessageHandler;

use App\Message\SendPushNotification;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Notifier\Bridge\Firebase\Notification\AndroidNotification;
use Symfony\Component\Notifier\Bridge\Firebase\Notification\WebNotification;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\ChatterInterface;

final class SendPushNotificationHandler implements MessageHandlerInterface
{
    public function __construct(ContainerBagInterface $params, ChatterInterface $chatter)
    {
        $this->params = $params;
        $this->chatter = $chatter;
    }

    public function __invoke(SendPushNotification $message)
    {
        $chatMessage = new ChatMessage(
            $message->getSubject(),
            new AndroidNotification(
                $message->getToken(),
                $message->getContext()
            )
        );
        $pushMessage = $chatMessage->transport('firebase');
        try {
            $this->chatter->send($pushMessage);
        } catch (TransportExceptionInterface $e) {
        }
    }
}
