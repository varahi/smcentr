<?php

namespace App\Controller\Admin;

use App\Entity\Firebase;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class FirebaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Firebase::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            BooleanField::new('hidden'),
            TextareaField::new('token'),
            AssociationField::new('user'),
        ];
    }
}
