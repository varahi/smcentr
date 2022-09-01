<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Answer;
use App\Form\Answer\AnswerFormType;
use App\Form\Order\OrderFormType;
use App\Repository\TicketRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use App\Form\Ticket\TicketFormType;
use App\Service\Mailer;

class TicketController extends AbstractController
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const STATUS_NEW = '0';

    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    private $doctrine;

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
     * @Route("/ticket-list", name="app_ticket_list")
     */
    public function list(
        TicketRepository $ticketRepository
    ): Response {
        $user = $this->security->getUser();
        $tickets = $ticketRepository->findAll();


        return $this->render('ticket/ticket_list.html.twig', [
            'user' => $user,
            'newTickets' => $tickets,
        ]);
    }

    /**
     * @Route("/ticket-detail/ticket-{id}", name="app_ticket_detail")
     */
    public function detailTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Ticket $ticket,
        ManagerRegistry $doctrine,
        Mailer $mailer
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN)) {
            $user = $this->security->getUser();
            $answer = new Answer();
            $form = $this->createForm(AnswerFormType::class, $answer);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $post = $request->request->get('answer_form');
                $entityManager = $doctrine->getManager();
                $ticket->setStatus(self::STATUS_ACTIVE);
                $answer->setTicket($ticket);

                // Close ticket
                if (isset($post['closeTicket']) && $post['closeTicket'] !=='') {
                    $ticket->setClosed(new \DateTime());
                    $ticket->setStatus(self::STATUS_COMPLETED);
                }

                // Set answered user
                $answer->setUser($user);
                $entityManager->persist($answer);
                $entityManager->persist($ticket);
                $entityManager->flush();

                $subject = $translator->trans('Your request has been answered', array(), 'messages');
                $mailer->sendAnswerEmail($ticket->getUser(), $subject, 'emails/answer_ticket_to_user.html.twig', $answer, $ticket);

                $message = $translator->trans('Answered', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                $referer = $request->headers->get('referer');
                return new RedirectResponse($referer);
            }

            return $this->render('ticket/ticket_detail.html.twig', [
                'ticket' => $ticket,
                'answerForm' => $form->createView()
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/support", name="app_support")
     */
    public function index(
        TicketRepository $ticketRepository
    ): Response {
        $user = $this->security->getUser();
        $newTickets = $ticketRepository->findAll();
        return $this->render('ticket/index.html.twig', [
            'user' => $user,
            'newTickets' => $newTickets,
        ]);
    }

    /**
     * @Route("/support/new-ticket", name="app_ticket_new")
     */
    public function newTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_MASTER)) {
            $user = $this->security->getUser();
            $ticket = new Ticket();

            $form = $this->createForm(TicketFormType::class, $ticket);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $doctrine->getManager();
                $ticket->setUser($user);
                $ticket->setStatus(0);
                $entityManager->persist($ticket);
                $entityManager->flush();

                $message = $translator->trans('Ticket created', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_support');
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }

        return $this->render('ticket/new_ticket.html.twig', [
            'user' => $user,
            'ticketForm' => $form->createView()
        ]);
    }
}
