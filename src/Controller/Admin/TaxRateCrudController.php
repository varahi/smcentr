<?php

namespace App\Controller\Admin;

use App\Entity\TaxRate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;

class TaxRateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TaxRate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tax Rate')
            ->setEntityLabelInPlural('Tax Rate')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield AssociationField::new('city');
        yield AssociationField::new('profession');
        yield PercentField::new('percent')->setColumns('col-md-2');
    }
}
