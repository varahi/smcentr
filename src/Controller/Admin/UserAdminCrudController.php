<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Twig;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class UserAdminCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $role = 'ROLE_SUPER_ADMIN';
        $role2 = 'ROLE_EDITOR';
        $role3 = 'ROLE_SUPPORT';

        if (isset($_GET['query'])) {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        } else {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
            $qb->where('entity.roles LIKE :roles');
            $qb->setParameter('roles', '%"'.$role.'"%');
            //$qb->setParameter('roles', '%"'.$role2.'"%');
            //$qb->setParameter('roles', '%"'.$role3.'"%');
        }

        return $qb;
    }

    /*public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('professions'));
    }*/

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
        //->disable('new', 'edit', 'delete');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Super admin')
            ->setEntityLabelInPlural('Super admin')
            ->setHelp('edit', 'Группа администраторов. Обладает всеми правами доступа')
            ->setHelp('index', 'Группа администраторов. Обладает всеми правами доступа')
            ->setSearchFields(['firstName', 'lastName', 'email', 'phone'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield EmailField::new('email');
        yield TextField::new('fullName');


        yield ImageField::new('avatar')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setColumns('col-md-4');


        //yield ArrayField::new('roles')->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        /*yield ImageField::new('avatar')
            ->setBasePath('/uploads/files')
            ->setLabel('Avatar')
            ->onlyOnIndex();*/

/*        return [
            FormField::addPanel('Basic information'),
        ];*/
    }
}
