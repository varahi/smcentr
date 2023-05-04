<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserEditorCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(
        UserPasswordHasherInterface $passwordEncoder
    ) {
        $this->passwordEncoder = $passwordEncoder;
    }

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
            //$qb->setParameter('roles', '%"'.$role.'"%');
            $qb->setParameter('roles', '["ROLE_EDITOR","ROLE_SUPPORT"]');
            //$qb->setParameter('roles', '%"'.$role3.'"%');
        }

        return $qb;
    }

    /*    public function configureFilters(Filters $filters): Filters
        {
            return $filters
                ->add(EntityFilter::new('professions'))
                ;
        }*/

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Editor')
            ->setEntityLabelInPlural('Editor')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone'])
            ->setHelp('edit', 'Группа модераторов. Полный доступ без редактирования баланса мастеров и фирм')
            ->setHelp('index', 'Группа модераторов. Полный доступ без редактирования баланса мастеров и фирм')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Main Info')->setIcon('fa fa-info');
        yield FormField::addRow()->setColumns('col-md-4');
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield EmailField::new('email');
        yield TextField::new('fullName');
        yield BooleanField::new('isVerified');
        //yield ArrayField::new('roles')->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        /*yield ImageField::new('avatar')
            ->setBasePath('/uploads/files')
            ->setLabel('Avatar')
            ->onlyOnIndex();*/

        yield FormField::addPanel('Change password')->setIcon('fa fa-key');
        yield FormField::addRow();
        yield Field::new('password', 'New password')->onlyWhenCreating()->setRequired(true)
            ->setFormType(RepeatedType::class)
            ->setColumns('col-md-8')
            ->hideOnIndex()
            ->setRequired(false)
            ->setFormTypeOptions([
                'type'            => PasswordType::class,
                'first_options'   => [ 'label' => 'New password' ],
                'second_options'  => [ 'label' => 'Repeat password' ],
                'error_bubbling'  => true,
                'invalid_message' => 'The password fields do not match.',
            ]);
        yield Field::new('password', 'New password')->onlyWhenUpdating()->setRequired(false)
            ->setFormType(RepeatedType::class)
            ->setColumns('col-md-4')
            ->hideOnIndex()
            ->setRequired(false)
            ->setFormTypeOptions([
                'type'            => PasswordType::class,
                'first_options'   => [ 'label' => 'New password' ],
                'second_options'  => [ 'label' => 'Repeat password' ],
                'error_bubbling'  => true,
                'invalid_message' => 'The password fields do not match.',
            ]);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        if ($entityDto->getInstance() !== null) {
            $plainPassword = $entityDto->getInstance()->getPassword();
        }

        $formBuilder  = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->addEncodePasswordEventListener($formBuilder, $plainPassword);

        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    protected function addEncodePasswordEventListener(FormBuilderInterface $formBuilder, $plainPassword = null): void
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($plainPassword) {
            /** @var User $user */
            $user = $event->getData();
            if ($user->getPassword() !== $plainPassword) {
                $user->setPassword($this->passwordEncoder->hashPassword($user, $user->getPassword()));
            }
        });
    }
}
