<?php

namespace App\Controller\Admin;

use App\Entity\Request;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;

class RequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Request::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Request a withdrawal')
            ->setEntityLabelInPlural('Request a withdrawal')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield DateTimeField::new('created')->setFormTypeOption('disabled', 'disabled');
        yield MoneyField::new('amount')->setCurrency('RUB')->setCustomOption('storedAsCents', false)->setCssClass('col-sm-8');
        yield ChoiceField::new('status')->setChoices(
            [
                'Выберите статус заявки *' => null,
                'Новая заявка' => '1',
                'Выполняется' => '2',
                'На удержании' => '3',
                'Успешно завершено' => '4',
                'Завершено с ошибкой' => '10',

            ]
        )->hideOnIndex();
        yield AssociationField::new('user')->setCssClass('col-sm-10');
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
