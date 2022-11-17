<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpKernel\KernelInterface;

class SecurityController extends AbstractController
{
    /**
     * Time in seconds
     *
     */
    public const CACHE_MAX_AGE = '86400';

    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLE_SUPPORT = 'ROLE_SUPPORT';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_CLIENT = 'ROLE_CLIENT';

    public const ROLE_MASTER = 'ROLE_MASTER';

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var Security
     */
    private $security;


    /**
     * @param KernelInterface $kernel
     * @param Security $security
     */
    public function __construct(
        KernelInterface $kernel,
        Security $security
    ) {
        $this->environment = $kernel->getEnvironment();
        $this->security = $security;
    }

    /**
     * @Route("/temp", name="app_security")
     */
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    /**
     * @Route("/", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        // Redirect depends on roles
        if ($this->security->getUser()) {
            $user = $this->security->getUser();
            if ($user != null && in_array(self::ROLE_CLIENT, $user->getRoles())) {
                return $this->redirectToRoute("app_client_profile");
            } elseif ($user != null && in_array(self::ROLE_MASTER, $user->getRoles())) {
                return $this->redirectToRoute("app_master_profile");
            } elseif ($user != null && in_array(self::ROLE_COMPANY, $user->getRoles())) {
                return $this->redirectToRoute("app_company_profile");
            } elseif (
                $user != null && in_array(self::ROLE_EDITOR, $user->getRoles()) ||
                $user != null && in_array(self::ROLE_SUPPORT, $user->getRoles()) ||
                $user != null && in_array(self::ROLE_SUPER_ADMIN, $user->getRoles())
            ) {
                return $this->redirectToRoute("app_backend");
            } else {
                return $this->redirectToRoute("app_login");
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $response = $this->render(
            'security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
                'env' => $this->environment
            ]
        );

        //$response->setSharedMaxAge(self::CACHE_MAX_AGE);
        return $response;
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
