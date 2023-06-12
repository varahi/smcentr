<?php

namespace App\Controller;

use App\Entity\Firebase;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class FirebaseController extends AbstractController
{
    /**
     * @param Security $security
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        ManagerRegistry $doctrine
    ) {
        $this->security = $security;
        $this->doctrine = $doctrine;
    }
    /**
     * @Route("/user-token", name="app_save_token")
     */
    public function saveToken()
    {
        //file_put_contents('token.txt', $_POST['token']);
        $token = $_POST['token'];
        $entityManager = $this->doctrine->getManager();
        if (isset($token) && !empty($token)) {
            $firebase = new Firebase();
            $firebase->setHidden(0);
            $firebase->setToken($token);
            if (null !== $this->security->getUser()) {
                $firebase->setUser($this->security->getUser());
            }
            $entityManager->persist($firebase);
            $entityManager->flush();
        }
        return new Response(
            'Token saved',
            Response::HTTP_OK
        );
    }
}
