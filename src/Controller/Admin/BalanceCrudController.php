<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class BalanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('System balance')
            ->setEntityLabelInPlural('System balance')
            ->overrideTemplates([
                'crud/index' => 'bundles/EasyAdminBundle/crud/balance.html.twig',
            ])

            ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('assets/css/easy_admin_custom.css')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable('new', 'edit', 'delete');
    }

    public function configureFields(string $pageName): iterable
    {
        //yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield MoneyField::new('balance')->setCurrency('RUB')
            ->setCustomOption('storedAsCents', false)->setColumns('col-md-3')->setCssClass('big-text');
    }
}
