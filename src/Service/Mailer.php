<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Request as UserRequest;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;

class Mailer
{
    private $adminEmail;

    private $noreplyEmail;

    private $mailer;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $adminEmail,
        string $noreplyEmail
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->adminEmail = $adminEmail;
        $this->noreplyEmail = $noreplyEmail;
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function updateCrudUserEmail(User $user, string $subject, string $template)
    {
        $date = new \DateTime();
        $email = (new TemplatedEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->context([
                'user' => $user,
                'date' => $date
            ]);

        $this->mailer->send($email);
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

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendNewCompanyEmail(User $user, string $subject, string $template, $plainPassword)
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
                'plainPassword' => $plainPassword
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendMasterVerifedEmail(User $user, string $subject, string $template)
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
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendAnswerEmail(User $user, string $subject, string $template, Answer $answer, Ticket $ticket)
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
                'answer' => $answer,
                'ticket' => $ticket
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendTicketRequestEmail(User $user, string $subject, string $template, Ticket $ticket)
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
                'ticket' => $ticket
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendWithdrawalRequestEmail(User $user, string $subject, string $template, UserRequest $request)
    {
        $date = new \DateTime();
        $email = (new TemplatedEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->from($this->noreplyEmail)
            ->to($this->adminEmail)
            ->context([
                'user' => $user,
                'date' => $date,
                'request' => $request
            ]);

        $this->mailer->send($email);
    }
}
