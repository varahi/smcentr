<?php

namespace App\Controller\User;

use App\Entity\Notification as UserNotification;
use App\Entity\Request as UserRequest;
use App\Entity\User;
use App\Form\Request\RequestFormType;
use App\Form\User\ClientProfileFormType;
use App\Form\User\CompanyProfileFormType;
use App\Form\User\MasterProfileFormType;
use App\ImageOptimizer;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\FirebaseRepository;
use App\Repository\JobTypeRepository;
use App\Repository\NotificationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FileUploader;
use App\Service\Mailer;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

class ClientProfileController extends AbstractController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const STATUS_NEW = '0';
    public const STATUS_ACTIVE = '1';

    public const STATUS_COMPLETED = '9';

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param ImageOptimizer $imageOptimizer
     * @param string $targetDirectory
     * @param VerifyEmailHelperInterface $helper
     * @param EmailVerifier $emailVerifier
     * @param string $defaultDomain
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        ImageOptimizer $imageOptimizer,
        string $targetDirectory,
        VerifyEmailHelperInterface $helper,
        EmailVerifier $emailVerifier,
        string $defaultDomain
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->imageOptimizer = $imageOptimizer;
        $this->targetDirectory = $targetDirectory;
        $this->verifyEmailHelper = $helper;
        $this->emailVerifier = $emailVerifier;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/lk-client", name="app_client_profile")
     */
    public function clinetProfile(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        OrderRepository $orderRepository,
        MailerInterface $mailer,
        PaginatorInterface $paginator
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();

            if ($user->isIsVerified() == 0) {
                // Send a new email link to verify email
                $this->verifyEmail($user);
                $message = $translator->trans('Please verify you profile', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_login");
            }

            //$queryOrders = $orderRepository->findByStatus(self::STATUS_NEW, $user);
            //$queryOrders2 = $orderRepository->findByStatus(self::STATUS_ACTIVE, $user);

            // Pagination
            /*$newOrders = $paginator->paginate(
                $queryOrders,
                $request->query->getInt('page', 1),
                self::LIMIT_PER_PAGE
            );*/

            $newOrdersClient = $orderRepository->findByStatus(self::STATUS_NEW, $user);
            if ($user->getPhone()) {
                $relatedNewOrders = $orderRepository->findByStatusPhoneAndCompany(self::STATUS_NEW, $user);
                // Sorting 2 arrays, maybe use function usort
                $newOrders = array_merge($relatedNewOrders, $newOrdersClient);
                $this->sortByObjectProps($newOrders, 'getId', 'DESC');
            } else {
                $newOrders = $newOrdersClient;
            }

            $activeOrders = $orderRepository->findByStatus(self::STATUS_ACTIVE, $user);
            $completedOrders = $orderRepository->findByStatus(self::STATUS_COMPLETED, $user);

            // Resize image if exist
            if ($user->getAvatar()) {
                $this->imageOptimizer->resize($this->targetDirectory.'/'.$user->getAvatar());
            }

            {
                $response = new Response($this->twig->render('user/client/lk-client.html.twig', [
                    'user' => $user,
                    'newOrders' => $newOrders,
                    'activeOrders' => $activeOrders,
                    'completedOrders' => $completedOrders
                ]));

                //$response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }

    /**
     * Require ROLE_CLIENT for *every* controller method in this class.
     *
     * @IsGranted("ROLE_CLIENT")
     * @Route("/user/edit-client-profile", name="app_edit_client_profile")
     */
    public function editClientProfile(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository
    ): Response {
        if ($this->isGranted(self::ROLE_CLIENT)) {
            $user = $this->security->getUser();
            $form = $this->createForm(ClientProfileFormType::class, $user);
            $form->handleRequest($request);

            $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
            $districts = $districtRepository->findAllOrder(['name' => 'ASC']);

            if ($form->isSubmitted()) {
                $post = $request->request->get('client_profile_form');
                if ($post['city'] !=='') {
                    $city = $cityRepository->findOneBy(['id' => $post['city']]);
                    if ($city) {
                        $user->setCity($city);
                    }
                }
                if ($post['district'] !=='') {
                    $district = $districtRepository->findOneBy(['id' => $post['district']]);
                    if ($district) {
                        $user->setDistrict($district);
                    }
                }
                // Set new password if changed
                if ($post['plainPassword']['first'] !=='' && $post['plainPassword']['second'] !=='') {
                    if (strcmp($post['plainPassword']['first'], $post['plainPassword']['second']) == 0) {
                        // encode the plain password
                        $user->setPassword(
                            $passwordHasher->hashPassword(
                                $user,
                                $post['plainPassword']['first']
                            )
                        );
                    } else {
                        $message = $translator->trans('Mismatch password', array(), 'flash');
                        $notifier->send(new Notification($message, ['browser']));
                        return $this->redirectToRoute("app_edit_client_profile");
                    }
                }

                // File upload
                $avatarFile = $form->get('avatar')->getData();
                if ($avatarFile) {
                    $avatarFileName = $fileUploader->upload($avatarFile);
                    $user->setAvatar($avatarFileName);
                }

                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $message = $translator->trans('Profile updated', array(), 'flash');
                $notifier->send(new Notification($message, ['browser']));
                return $this->redirectToRoute("app_client_profile");
                //$referer = $request->headers->get('referer');
                //return new RedirectResponse($referer);
            }

            {
                $response = new Response($this->twig->render('user/client/edit-client.html.twig', [
                    'user' => $user,
                    'cities' => $cities,
                    'districts' => $districts,
                    'form' => $form->createView(),
                ]));

                //$response->setSharedMaxAge(self::CACHE_MAX_AGE);
                return $response;
            }
        } else {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_login");
        }
    }


    private function sortByObjectProps(&$items, $method, $order)
    {
        if (! is_array($items)) {
            return false;
        }

        return usort($items, function ($a, $b) use ($method, $order) {
            $cmp = strcmp($a->$method(), $b->$method());
            return $order === 'asc' ? $cmp : -$cmp;
        });
    }

    /**
     * @param User $user
     * @return void
     */
    private function verifyEmail(
        User $user
    ) {

        // ToDo: move this method to service
        // Verify email
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()] // add the user's id as an extra query param
        );

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@smcentr.su', 'Admin'))
                ->to($user->getEmail())
                ->subject('Пожалуйста подтвердите ваш пароль')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'verifyUrl' => $signatureComponents->getSignedUrl()
                ])
        );
    }
}
