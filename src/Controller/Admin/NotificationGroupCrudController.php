<?php

namespace App\Controller\Admin;

use App\Entity\NotificationGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NotificationGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NotificationGroup::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            //->disable('new');
        ->disable('new', 'delete');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('text')->setLabel('Message'),
            DateTimeField::new('created')->setFormTypeOption('disabled', 'disabled'),
            AssociationField::new('notification')->hideOnIndex()->setFormTypeOption('disabled', 'disabled'),
        ];
    }
}
