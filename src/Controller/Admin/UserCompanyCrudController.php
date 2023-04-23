<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Mailer;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserCompanyCrudController extends AbstractCrudController
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
        $role = 'ROLE_COMPANY';
        if (isset($_GET['query'])) {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        } else {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
            $qb->where('entity.roles LIKE :roles');
            $qb->setParameter('roles', '%"'.$role.'"%');
        }
        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            //->add(EntityFilter::new('professions'))
            //->add(EntityFilter::new('jobTypes'))
            ->add(EntityFilter::new('ticket', 'Tickets list'))
            ->add(EntityFilter::new('city'))
            ->add(EntityFilter::new('district'))
            ->add(EntityFilter::new('companyClients', 'Company Clients'))
            ->add(EntityFilter::new('companyMasters', 'Company Masters'))
            ;
    }

    /*public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
        //->disable('new', 'delete');
    }*/


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Company')
            ->setEntityLabelInPlural('Company')
            ->setSearchFields(['id', 'fullName', 'email', 'phone'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('assets/css/easy_admin_custom.css')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Main Info')->setIcon('fa fa-info');
        yield FormField::addRow();
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled')->hideWhenCreating();

        yield FormField::addRow();
        yield TextField::new('fullName')->setColumns('col-md-4')->setLabel('Company name');
        yield EmailField::new('email')->setColumns('col-md-4');

        yield FormField::addRow();
        /*$roles = [ 'ROLE_SUPER_ADMIN', 'ROLE_SUPPORT', 'ROLE_EDITOR', 'ROLE_CLIENT', 'ROLE_MASTER', 'ROLE_COMPANY' ];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setPermission('ROLE_SUPER_ADMIN')
            ->setColumns('col-md-4')
            ->hideOnIndex();*/

        yield FormField::addRow();

        $roleMaster = 'ROLE_MASTER';
        $roleClient = 'ROLE_CLIENT';


        yield BooleanField::new('isVerified')->hideOnIndex();
        yield BooleanField::new('isDisabled');

        yield FormField::addPanel('Change password')->setIcon('fa fa-key')->setPermission('ROLE_SUPER_ADMIN');
        yield FormField::addRow();
        yield Field::new('password', 'New password')->onlyWhenCreating()->setRequired(true)
            ->setFormType(RepeatedType::class)
            ->setRequired(false)
            ->setFormTypeOptions([
                'type'            => PasswordType::class,
                'first_options'   => [ 'label' => 'New password' ],
                'second_options'  => [ 'label' => 'Repeat password' ],
                'error_bubbling'  => true,
                'invalid_message' => 'The password fields do not match.',
            ])
            ->setPermission('ROLE_SUPER_ADMIN');
        yield Field::new('password', 'New password')->onlyWhenUpdating()->setRequired(false)
            ->setFormType(RepeatedType::class)
            ->setRequired(false)
            ->setFormTypeOptions([
                'type'            => PasswordType::class,
                'first_options'   => [ 'label' => 'New password' ],
                'second_options'  => [ 'label' => 'Repeat password' ],
                'error_bubbling'  => true,
                'invalid_message' => 'The password fields do not match.',
            ])
            ->setPermission('ROLE_SUPER_ADMIN');

        yield FormField::addPanel('Contact info')->setIcon('fa fa-info-circle');
        yield FormField::addRow();

        yield AssociationField::new('city')->setColumns('col-md-4')->setRequired('1');
        yield TextField::new('responsiblePersonFullName')->setColumns('col-md-4')->hideOnIndex();
        //yield AssociationField::new('district')->setColumns('col-md-4')->hideOnIndex();

        yield FormField::addRow();
        yield TextField::new('responsiblePersonPhone')->setColumns('col-md-4')->hideOnIndex();
        yield TextField::new('responsiblePersonEmail')->setColumns('col-md-4')->hideOnIndex();

        yield FormField::addRow();
        yield ImageField::new('avatar')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setColumns('col-md-4')
            //->setFormType(VichImageType::class)
        ;


        yield FormField::addPanel('Requisites')->setIcon('fa fa-money')->setPermission('ROLE_SUPER_ADMIN');
        yield MoneyField::new('balance')->setCurrency('RUB')
            ->setCustomOption('storedAsCents', false)
            ->setPermission('ROLE_SUPER_ADMIN')
            ->setColumns('col-md-4');
        yield FormField::addRow();
        yield TextField::new('inn')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield TextField::new('ogrn')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield FormField::addRow();
        yield TextField::new('checkingAccount')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield TextField::new('bank')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield FormField::addRow();
        yield TextField::new('cardNumber')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield TextField::new('cardFullName')->setColumns('col-md-4')->hideOnIndex()->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');

        yield FormField::addPanel('Additional Info')->setIcon('fa fa-info-circle');
        yield BooleanField::new('getNotifications');
        yield PercentField::new('taxRate')->hideOnIndex()->setColumns('col-md-4')->setPermission('ROLE_SUPER_ADMIN');
        yield PercentField::new('serviceTaxRate')->hideOnIndex()->setColumns('col-md-4')->setPermission('ROLE_SUPER_ADMIN');

        yield FormField::addRow();
        yield AssociationField::new('professions')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])->hideOnIndex()
            ->setColumns('col-md-4');
        yield AssociationField::new('jobTypes')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])->hideOnIndex()
            ->setColumns('col-md-4');

        yield FormField::addRow();
        yield AssociationField::new('companyMasters')
            ->setLabel('Company Masters')
            ->setColumns('col-md-4')
            ->hideOnIndex()
            ->setFormTypeOptions([
                'query_builder' => function (EntityRepository $entityRepository) use ($roleMaster) {
                    return $entityRepository->createQueryBuilder('entity')
                        ->where('entity.roles LIKE :roles')
                        ->setParameter('roles', '%"'.$roleMaster.'"%')
                        ->orderBy('entity.fullName', 'ASC');
                },
                'by_reference' => false,
            ]);

        yield AssociationField::new('companyClients')
            ->setLabel('Company Clients')
            ->setColumns('col-md-4')
            ->hideOnIndex()
            ->setFormTypeOptions([
                'query_builder' => function (EntityRepository $entityRepository) use ($roleClient) {
                    return $entityRepository->createQueryBuilder('entity')
                        ->where('entity.roles LIKE :roles')
                        ->setParameter('roles', '%"'.$roleClient.'"%')
                        ->orderBy('entity.fullName', 'ASC');
                },
                'by_reference' => false,
            ]);

        yield FormField::addRow();
        yield AssociationField::new('orders')->hideOnIndex()->setColumns('col-md-4');
        yield AssociationField::new('assignments')->hideOnIndex()->setColumns('col-md-4');
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

    public function createEntity(string $entityFqcn)
    {
        $user = new User();
        $user->setRoles(['ROLE_COMPANY']);
        if ($user->getEmail()) {
            $user->setUsername($user->getEmail());
        }

        return $user;
    }
}
