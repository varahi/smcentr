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
        //yield MenuItem::linkToLogout('Logout', 'fa fa-user-times');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);

        yield MenuItem::section('Справочники');
        yield MenuItem::subMenu('Справочники', 'fa fa-tags')->setSubItems([
            MenuItem::linkToCrud('Города', 'fa fa-city', City::class),
            MenuItem::linkToCrud('Районы', 'fa fa-building', District::class),
            MenuItem::linkToCrud('Профессии', 'fa fa-users', Profession::class),
            MenuItem::linkToCrud('Виды работ', 'fa fa-industry', JobType::class),
        ]);

        yield MenuItem::section('Пользователи');
        yield MenuItem::subMenu('Пользователи', 'fa fa fa-cog')->setSubItems([
            MenuItem::linkToCrud('Мастера', 'fa fa-user', User::class),
            MenuItem::linkToCrud('Клиенты', 'fa fa-user', User::class),
            MenuItem::linkToCrud('Фирмы', 'fa fa-user', User::class),
        ]);

        yield MenuItem::linkToCrud('Список заказов', 'fa fa-reorder', Order::class);
    }
}
