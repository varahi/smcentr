<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('users'))
            ->add(EntityFilter::new('performer'))
            ->add(EntityFilter::new('city'))
            ->add(EntityFilter::new('jobType'))
            ->add(EntityFilter::new('profession'))
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Orders')
            ->setEntityLabelInPlural('Orders')
            ->setSearchFields(['title', 'price', 'description'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        //yield TextField::new('title');
        //yield TextField::new('price');
        yield TelephoneField::new('phone');
        yield TextareaField::new('description');
        yield MoneyField::new('price')->setCurrency('RUB')->setCustomOption('storedAsCents', false);
        yield DateField::new('deadline');
        yield ChoiceField::new('level')->setChoices(
            [
                'Сложноть заявки *' => null,
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
            ]
        )->hideOnIndex();
        /*yield ChoiceField::new('status')->setChoices(
            [
                'Статус заявки *' => null,
                '0' => '0',
                '1' => '1',
                '9' => '9',
            ]
        )->hideOnIndex();*/
        yield AssociationField::new('city')->hideOnIndex();
        yield AssociationField::new('district')->hideOnIndex();
        yield AssociationField::new('users')->hideOnIndex();
        yield AssociationField::new('performer')->hideOnIndex();
        yield AssociationField::new('profession')->hideOnIndex();
        yield AssociationField::new('jobType')->hideOnIndex();
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
