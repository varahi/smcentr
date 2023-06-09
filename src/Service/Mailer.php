<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Request as UserRequest;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Twig\Environment;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Mailer
{
    private $adminEmail;

    private $noreplyEmail;

    private $mailer;

    private RequestStack $requestStack;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $adminEmail,
        string $noreplyEmail,
        ValidatorInterface $validator,
        NotifierInterface $notifier,
        RequestStack $requestStack
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->adminEmail = $adminEmail;
        $this->noreplyEmail = $noreplyEmail;
        $this->validator = $validator;
        $this->notifier = $notifier;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function updateCrudUserEmail(User $user, string $subject, string $template)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendUserEmail(User $user, string $subject, string $template, Order $order)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendNewCompanyEmail(User $user, string $subject, string $template, $plainPassword)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendMasterVerifedEmail(User $user, string $subject, string $template)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendAnswerEmail(User $user, string $subject, string $template, Answer $answer, Ticket $ticket)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendTicketRequestEmail(User $user, string $subject, string $template, Ticket $ticket)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendWithdrawalRequestEmail(User $user, string $subject, string $template, UserRequest $request)
    {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        $errors = $this->validator->validate(
            $user->getEmail(),
            $emailConstraint
        );
        $httpRequest = $this->requestStack->getCurrentRequest();

        if (0 === count($errors)) {
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
        } else {
            $errorMessage = $errors[0]->getMessage();
            $this->notifier->send(new Notification($errorMessage, ['browser']));
            $referer = $httpRequest->headers->get('referer');
            return new RedirectResponse($referer);
        }
    }
}
