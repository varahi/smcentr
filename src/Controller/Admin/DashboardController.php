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
use App\Entity\Profession;
use App\Entity\User;
use App\Entity\Pages;
use App\Entity\Notification;
use App\Entity\TaxRate;
use App\Entity\Request;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/backend", name="app_backend")
     */
    public function index(): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        //$this->denyAccessUnlessGranted('ROLE_EDITOR');
        //$this->denyAccessUnlessGranted('ROLE_SUPPORTISTRATOR');

        //return parent::index();
        $routeBuilder = $this->get(AdminUrlGenerator::class);
        return $this->redirect($routeBuilder->setController(CityCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            //->setTitle('SMC Admin Panel');
            ->setTitle('<img src="assets/images/logo.svg" class="img-fluid d-block mx-auto" style="max-width:120px; width:100%;"><h2 class="mt-3 fw-bold text-black text-center" style="font-size: 22px;">SMC Admin Panel</h2>')
            ->renderContentMaximized()
            ;
    }

    public function configureMenuItems(): iterable
    {
        //yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('adminpanel.back_to_site', 'fa fa-home', 'app_login');
        //yield MenuItem::linkToCrud('Admins', 'fa fa-user', User::class)->setController(UserAdminCrudController::class);

        yield MenuItem::section('References')->setPermission('ROLE_EDITOR');
        yield MenuItem::subMenu('References', 'fa fa-tags')->setSubItems([
            MenuItem::linkToCrud('Cities', 'fa fa-city', City::class),
            MenuItem::linkToCrud('Districts', 'fa fa-building', District::class),
            MenuItem::linkToCrud('Professions', 'fa fa-users', Profession::class),
            MenuItem::linkToCrud('Job Types', 'fa fa-industry', JobType::class),
            MenuItem::section('<hr />'),
            MenuItem::linkToCrud('Tax Rate', 'fa fa-percent', TaxRate::class)
        ])->setPermission('ROLE_EDITOR');

        yield MenuItem::section('Users');
        yield MenuItem::subMenu('Users', 'fa fa fa-cog')->setSubItems([
            MenuItem::linkToCrud('Masters', 'fa fa-user', User::class)->setController(UserMasterCrudController::class),
            MenuItem::linkToCrud('Clients', 'fa fa-user', User::class)->setController(UserClientCrudController::class),
            MenuItem::linkToCrud('Companies', 'fa fa-users', User::class)
                ->setController(UserCompanyCrudController::class),

            MenuItem::linkToRoute('Create company', 'fa fa-users', 'app_registration_company')->setPermission('ROLE_EDITOR'),
            //MenuItem::linkToRoute('Edit company', 'fa fa-users', 'app_registration_company'),
            MenuItem::section('<hr />')->setPermission('ROLE_EDITOR'),
            //MenuItem::linkToCrud('Moderators', 'fa fa-user', User::class)->setController(UserAdminCrudController::class),
            MenuItem::linkToCrud('Admins', 'fa fa-user', User::class)
                ->setController(UserAdminCrudController::class)->setPermission('ROLE_EDITOR'),
            MenuItem::section(''),

            MenuItem::linkToCrud('Notifications', 'fa fa-bell', Notification::class)->setPermission('ROLE_EDITOR'),
            MenuItem::linkToRoute('Create notification', 'fa fa-bell-o', 'app_new_notification')
                ->setPermission('ROLE_SUPER_ADMIN'),
        ]);

        yield MenuItem::section('Information');
        yield MenuItem::subMenu('Information', 'fa fa fa-info')->setSubItems([
            //MenuItem::linkToCrud('News', 'fa fa-newspaper-o', District::class),
            MenuItem::linkToCrud('Oferta', 'fa fa-legal', Pages::class)->setController(OfertaCrudController::class)
                ->setPermission('ROLE_EDITOR'),
            MenuItem::linkToCrud('Privacy Policy', 'fa fa-ticket', Pages::class)
                ->setController(PrivacyCrudController::class)
                ->setPermission('ROLE_EDITOR'),
        ])->setPermission('ROLE_EDITOR');

        yield MenuItem::linkToCrud('Orders list', 'fa fa-reorder', Order::class);
        yield MenuItem::linkToRoute('Tickets list', 'fa fa-support', 'app_ticket_list');
        yield MenuItem::linkToCrud('Request a withdrawal', 'fa fa-mail-forward', Request::class)
            ->setPermission('ROLE_EDITOR');
        yield MenuItem::section('<hr />');
        yield MenuItem::linkToLogout('Logout', 'fa fa-user-times');
    }
}
