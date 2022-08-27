<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Entity\City;
use App\Entity\District;
use App\Entity\JobType;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\Profession;
use App\Entity\User;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/backend", name="app_backend")
     */
    public function index(): Response
    {
        //return parent::index();
        $routeBuilder = $this->get(AdminUrlGenerator::class);
        return $this->redirect($routeBuilder->setController(CityCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SMC Admin Panel');
    }

    public function configureMenuItems(): iterable
    {
        //yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('adminpanel.back_to_site', 'fa fa-home', 'app_login');
        yield MenuItem::linkToCrud('Admins', 'fa fa-user', User::class)->setController(UserAdminCrudController::class);

        yield MenuItem::section('References');
        yield MenuItem::subMenu('References', 'fa fa-tags')->setSubItems([
            MenuItem::linkToCrud('Cities', 'fa fa-city', City::class),
            MenuItem::linkToCrud('Districts', 'fa fa-building', District::class),
            MenuItem::linkToCrud('Professions', 'fa fa-users', Profession::class),
            MenuItem::linkToCrud('Job Types', 'fa fa-industry', JobType::class),
        ]);

        yield MenuItem::section('Users');
        yield MenuItem::subMenu('Users', 'fa fa fa-cog')->setSubItems([
            MenuItem::linkToCrud('Masters', 'fa fa-user', User::class)->setController(UserMasterCrudController::class),
            MenuItem::linkToCrud('Clients', 'fa fa-user', User::class)->setController(UserClientCrudController::class),
        ]);

        yield MenuItem::linkToCrud('Orders list', 'fa fa-reorder', Order::class);
        yield MenuItem::linkToCrud('Tickets list', 'fa fa-support', Ticket::class);
        yield MenuItem::section('__________________________________');
        yield MenuItem::linkToLogout('Logout', 'fa fa-user-times');
    }
}
