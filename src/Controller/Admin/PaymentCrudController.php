<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable('new', 'delete');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id')->setFormTypeOption('disabled', 'disabled'),
            TextField::new('title'),
            TextareaField::new('note')->hideOnIndex(),
            MoneyField::new('amount')->setCurrency('RUB')->setFormTypeOption('disabled', 'disabled'),
            DateTimeField::new('created')->setFormTypeOption('disabled', 'disabled'),
            AssociationField::new('user'),
            ChoiceField::new('status')->setChoices(
                [
                    'Выберите' => '',
                    'Новая' => '0',
                    'Оплачен' => '1',
                    'Завершена с ошибкой' => '9',
                ]
            )->setLabel('Order status')->setRequired(true),
        ];
    }
}
