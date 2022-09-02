<?php

namespace App\Controller;

use App\Repository\PagesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class PagesController extends AbstractController
{
    /**
     * @param Security $security
     */
    public function __construct(
        Security $security
    ) {
        $this->security = $security;
    }

    /**
     * @Route("/pages", name="app_pages")
     */
    public function index(): Response
    {
        return $this->render('pages/index.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }

    /**
     * @Route("/privacy", name="app_page_privacy")
     */
    public function privacyPage(
        PagesRepository $pagesRepository
    ): Response {
        $user = $this->security->getUser();
        $pages = $pagesRepository->findAll();
        return $this->render('pages/privacy.html.twig', [
            'user' => $user,
            'pages' => $pages
        ]);
    }

    /**
     * @Route("/oferta", name="app_page_oferta")
     */
    public function ofertaPage(
        PagesRepository $pagesRepository
    ): Response {
        $user = $this->security->getUser();
        $pages = $pagesRepository->findAll();
        return $this->render('pages/oferta.html.twig', [
            'user' => $user,
            'pages' => $pages
        ]);
    }
}
