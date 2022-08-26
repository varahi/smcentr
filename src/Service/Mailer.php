<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class Mailer
{
    private $adminEmail;

    private $mailer;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $adminEmail
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->adminEmail = $adminEmail;
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendUserEmail(User $user, string $subject, string $template, Order $order)
    {
        $date = new \DateTime();
        $email = (new TemplatedEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->context([
                'user' => $user,
                'date' => $date,
                'order' => $order
            ]);

        $this->mailer->send($email);
    }
}
