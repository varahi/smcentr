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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
            ->setSearchFields(['title', 'price', 'description', 'id'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Main Info')->setIcon('fa fa-info')->setCssClass('col-sm-8');
        yield FormField::addRow();

        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        //yield TextField::new('title');
        //yield TextField::new('price');
        yield TelephoneField::new('phone')->setColumns('col-md-4')->hideOnIndex();
        yield TextareaField::new('description')->setColumns('col-md-10');
        yield MoneyField::new('price')->setCurrency('RUB')->setCustomOption('storedAsCents', false)->setColumns('col-md-3');
        yield TextField::new('estimatedTime')->setColumns('col-md-3')->hideOnIndex();
        yield DateField::new('deadline')->setColumns('col-md-3')->hideOnIndex();

        yield TextField::new('address')->setColumns('col-md-10');
        yield FormField::addPanel('Additional Info')->setIcon('fa fa-info-circle')->setCssClass('col-sm-4');
        yield FormField::addRow();

        yield AssociationField::new('city')->hideOnIndex()->setColumns('col-md-10');
        yield AssociationField::new('district')->hideOnIndex()->setColumns('col-md-10');
        yield AssociationField::new('users')->setColumns('col-md-10')->setLabel('Customer');
        yield AssociationField::new('performer')->hideOnIndex()->setColumns('col-md-10');
        yield AssociationField::new('profession')->hideOnIndex()->setColumns('col-md-10');
        yield AssociationField::new('jobType')->hideOnIndex()->setColumns('col-md-10');
        yield ChoiceField::new('level')->setChoices(
            [
                'Сложноть заявки *' => null,
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
            ]
        )->hideOnIndex()->setColumns('col-md-10');

        yield ChoiceField::new('status')->setChoices(
            [
                'Статус заявки *' => null,
                'Новая' => '0',
                'В работе' => '1',
                'Завершена' => '9',
            ]
        )->hideOnIndex()->setLabel('Order status')->setColumns('col-md-10');

        yield ChoiceField::new('typeCreated')->setChoices(
            [
                'Кем создана заявка *' => null,
                'Клиент' => '1',
                'Мастер' => '2',
                'Компания' => '3',
            ]
        )->hideOnIndex()->setColumns('col-md-10');

        yield TextField::new('customTaxRate')->setColumns('col-md-10')->hideOnIndex()->setPermission('ROLE_EDITOR');
        //yield BooleanField::new('sendOwnMasters')->setColumns('col-md-10')->hideOnIndex();
        //yield BooleanField::new('sendAllMasters')->setColumns('col-md-10')->hideOnIndex();
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
