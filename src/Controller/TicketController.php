<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Answer;
use App\Form\Answer\AnswerFormType;
use App\Form\Order\OrderFormType;
use App\Repository\FirebaseRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\PushNotification;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TicketController extends AbstractController
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_SUPPORT = 'ROLE_SUPPORT';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

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
        ManagerRegistry $doctrine,
        FirebaseRepository $firebaseRepository,
        PushNotification $pushNotification
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->firebaseRepository = $firebaseRepository;
        $this->pushNotification = $pushNotification;
    }

    /**
     * @Route("/support", name="app_support")
     */
    public function index(
        TicketRepository $ticketRepository
    ): Response {
        $user = $this->security->getUser();
        $newTickets = $ticketRepository->findByUserAndStatus($user->getId(), self::STATUS_NEW);
        $activeTickets = $ticketRepository->findByUserAndStatus($user->getId(), self::STATUS_ACTIVE);
        $completedTickets = $ticketRepository->findByUserAndStatus($user->getId(), self::STATUS_COMPLETED);

        return $this->render('ticket/index.html.twig', [
            'user' => $user,
            'newTickets' => array_merge($newTickets, $activeTickets),
            'activeTickets' => $activeTickets,
            'completedTickets' => $completedTickets
        ]);
    }


    /**
     *
     * @Route("/ticket-count-new", name="app_ticket_count_new")
     */
    public function countNew(
        TicketRepository $ticketRepository,
        NotifierInterface $notifier,
        TranslatorInterface $translator
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR) || $this->isGranted(self::ROLE_SUPPORT)) {
            $user = $this->security->getUser();

            $newTickets = count($ticketRepository->findAllByStatus(self::STATUS_NEW));

            return new Response(<<<EOF
                $newTickets
             EOF);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     *
     * @Route("/ticket-list", name="app_ticket_list")
     */
    public function list(
        TicketRepository $ticketRepository,
        NotifierInterface $notifier,
        TranslatorInterface $translator
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR) || $this->isGranted(self::ROLE_SUPPORT)) {
            $user = $this->security->getUser();

            $newTickets = $ticketRepository->findAllByStatus(self::STATUS_NEW);
            $activeTickets = $ticketRepository->findAllByStatus(self::STATUS_ACTIVE);
            $completedTickets = $ticketRepository->findAllByStatus(self::STATUS_COMPLETED);

            return $this->render('ticket/ticket_list.html.twig', [
                'user' => $user,
                'newTickets' => $newTickets,
                'activeTickets' => $activeTickets,
                'completedTickets' => $completedTickets,
            ]);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/edit-ticket/ticket-{id}", name="app_edit_ticket")
     */
    public function editTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Ticket $ticket,
        ManagerRegistry $doctrine,
        Mailer $mailer
    ): Response {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR) || $this->isGranted(self::ROLE_SUPPORT)) {
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
                $ticket->setIsRead((int)1);
                $entityManager->flush();

                $subject = $translator->trans('Your request has been answered', array(), 'messages');
                $mailer->sendAnswerEmail($ticket->getUser(), $subject, 'emails/answer_ticket_to_user.html.twig', $answer, $ticket);

                // Send push notification
                $context = [
                    'title' => $translator->trans('You got answer on your ticket', array(), 'messages'),
                    'clickAction' => 'https://smcentr.su/support',
                    'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
                ];

                if ($ticket->getUser()) {
                    $tokens = $this->firebaseRepository->findAllByOneUser($ticket->getUser());
                    $this->pushNotification->sendMQPushNotification($answer->getAnswer(), $context, $tokens);
                }

                $message = $translator->trans('Answered', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_ticket_list");
                //$referer = $request->headers->get('referer');
                //return new RedirectResponse($referer);
            }

            return $this->render('ticket/ticket_edit.html.twig', [
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
     * @Route("/detail-ticket/ticket-{id}", name="app_detail_ticket")
     */
    public function detailTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Ticket $ticket,
        ManagerRegistry $doctrine,
        Mailer $mailer,
        UserRepository $userRepository
    ): Response {
        if ($this->isGranted(self::ROLE_MASTER) || $this->isGranted(self::ROLE_CLIENT) || $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            $adminUsers = $userRepository->findByRole(self::ROLE_SUPER_ADMIN);

            if ($user->getId() == $ticket->getUser()->getId()) {
                $answer = new Answer();
                //$form = $this->createForm(AnswerFormType::class, $answer);
                $form = $this->createForm(AnswerFormType::class, $answer, [
                    'action' => $this->generateUrl('app_detail_ticket', ['id' => $ticket->getId()]),
                    'method' => 'POST',
                ]);

                //if ($form->isSubmitted())
                if (!empty($_POST['answer_form'])) {
                    $entityManager = $doctrine->getManager();
                    $form->handleRequest($request);

                    $answer->setUser($user);
                    $answer->setTicket($ticket);
                    $entityManager->persist($answer);
                    $ticket->setIsRead((int)0);
                    $entityManager->flush();

                    // Send emails to admin
                    $subject = $translator->trans('Support request', array(), 'messages');
                    if (isset($adminUsers)) {
                        foreach ($adminUsers as $adminUser) {
                            $mailer->sendAnswerEmail($adminUser, $subject, 'emails/ticket_to_admin.html.twig', $answer, $ticket);
                        }
                    }

                    $message = $translator->trans('Answered', array(), 'flash');
                    $notifier->send(new Notification($message, ['browser']));
                    $referer = $request->headers->get('referer');
                    return new RedirectResponse($referer);
                }

                return $this->render('ticket/ticket_detail.html.twig', [
                    'ticket' => $ticket,
                    'user' => $user,
                    'answerForm' => $form->createView()
                ]);
            } else {
                // Redirect if order or performer not owner
                $message = $translator->trans('Please login', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute('app_login');
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * @Route("/support/new-ticket", name="app_ticket_new")
     */
    public function newTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        ManagerRegistry $doctrine,
        Mailer $mailer,
        UserRepository $userRepository
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT) ||
            $this->isGranted(self::ROLE_MASTER) ||
            $this->isGranted(self::ROLE_COMPANY)) {
            $user = $this->security->getUser();
            $adminUsers = $userRepository->findByRole(self::ROLE_SUPER_ADMIN);
            $ticket = new Ticket();

            $form = $this->createForm(TicketFormType::class, $ticket);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $doctrine->getManager();
                $ticket->setUser($user);
                $ticket->setStatus(0);
                $entityManager->persist($ticket);
                $entityManager->flush();

                // Send emails to admin
                $subject = $translator->trans('Support request', array(), 'messages');
                if (isset($adminUsers)) {
                    foreach ($adminUsers as $adminUser) {
                        $mailer->sendTicketRequestEmail($adminUser, $subject, 'emails/ticket_request.html.twig', $ticket);
                    }
                }

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

    /**
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @Route("/activate-ticket/ticket-{id}", name="app_activate_ticket")
     */
    public function activateTicket(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Ticket $ticket,
        ManagerRegistry $doctrine
    ) {
        if ($this->isGranted(self::ROLE_SUPER_ADMIN) || $this->isGranted(self::ROLE_EDITOR) || $this->isGranted(self::ROLE_SUPPORT)) {
            $entityManager = $doctrine->getManager();
            $ticket->setStatus(self::STATUS_ACTIVE);
            $entityManager->persist($ticket);
            $entityManager->flush();

            $message = $translator->trans('Ticket activated again', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }
}
