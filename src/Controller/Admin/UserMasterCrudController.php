<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Service\Mailer;
use Symfony\Component\Stopwatch\Section;

class UserMasterCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordEncoder;

    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(
        UserPasswordHasherInterface $passwordEncoder,
        Mailer $mailer
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->mailer = $mailer;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $role = 'ROLE_MASTER';
        if (isset($_GET['query'])) {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
            $qb->andWhere($qb->expr()->eq('entity.isVerified', 1))
            ;
        } else {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
            $qb
                ->where('entity.roles LIKE :roles')
                ->setParameter('roles', '%"'.$role.'"%')
                ->andWhere($qb->expr()->eq('entity.isVerified', 1))
            ;
        }

        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('professions'))
            ->add(EntityFilter::new('jobTypes'))
            ->add(EntityFilter::new('ticket', 'Tickets list'))
            ->add(EntityFilter::new('city'))
            ->add(EntityFilter::new('district'))
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Master')
            ->setEntityLabelInPlural('Master')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setFormThemes(['bundles/EasyAdminBundle/crud/form_theme.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('assets/css/easy_admin_custom.css')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $role = 'ROLE_COMPANY';

        yield FormField::addPanel('Main Info')->setIcon('fa fa-info');
        yield FormField::addRow();
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled')->hideWhenCreating();

        yield FormField::addRow();
        yield EmailField::new('email')->setColumns('col-md-4');
        yield TextField::new('fullName')->setColumns('col-md-4')->hideOnIndex();

        //yield ArrayField::new('roles')->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        /*$roles = [ 'ROLE_SUPER_ADMIN', 'ROLE_SUPPORT', 'ROLE_EDITOR', 'ROLE_CLIENT', 'ROLE_MASTER', 'ROLE_COMPANY' ];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setPermission('ROLE_SUPER_ADMIN')
            ->hideOnIndex()
            ->setColumns('col-md-4');*/

        yield FormField::addRow();
        yield AssociationField::new('master')
            //->setFormTypeOption('disabled', 'disabled')
            ->setLabel('Master Company')
            ->setColumns('col-md-4')
            ->setFormTypeOption('query_builder', function (EntityRepository $entityRepository) use ($role) {
                return $entityRepository->createQueryBuilder('entity')
                    ->where('entity.roles LIKE :roles')
                    ->setParameter('roles', '%"'.$role.'"%')
                    ->orderBy('entity.fullName', 'ASC');
            });

        yield TelephoneField::new('phone')->setColumns('col-md-4');

        /*yield AssociationField::new('master')
            ->setLabel('Master Company')
            ->setColumns('col-md-4')
            ->setFormTypeOptions([
                'query_builder' => function (EntityRepository $entityRepository) use ($role) {
                    return $entityRepository->createQueryBuilder('entity')
                        ->where('entity.roles LIKE :roles')
                        ->setParameter('roles', '%"'.$role.'"%')
                        ->orderBy('entity.fullName', 'ASC');
                },
                'by_reference' => false,
            ]);*/

        yield BooleanField::new('isVerified');
        yield BooleanField::new('isDisabled');

        yield FormField::addPanel('Change password')->setIcon('fa fa-key');
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
            ]);
        yield Field::new('password', 'New password')->onlyWhenUpdating()->setRequired(false)
            ->setFormType(RepeatedType::class)
            ->setRequired(false)
            ->setFormTypeOptions([
                'type'            => PasswordType::class,
                'first_options'   => [ 'label' => 'New password' ],
                'second_options'  => [ 'label' => 'Repeat password' ],
                'error_bubbling'  => true,
                'invalid_message' => 'The password fields do not match.',
            ]);

        yield FormField::addPanel('Documents')->setIcon('fa fa-info-circle');
        yield FormField::addRow();

        yield ImageField::new('avatar')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setColumns('col-md-4')->hideOnIndex();

        yield ImageField::new('doc1')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setLabel('Passport main page')
            ->setColumns('col-md-4')->hideOnIndex();

        yield FormField::addRow();
        yield ImageField::new('doc2')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setLabel('Registration page')
            ->setColumns('col-md-4')->hideOnIndex();

        yield ImageField::new('doc3')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false)
            ->setLabel('Selfie with a passport')
            ->setColumns('col-md-4')->hideOnIndex();

        yield FormField::addPanel('Additional Info')->setIcon('fa fa-info-circle');
        yield BooleanField::new('getNotifications')->hideOnIndex();
        yield FormField::addRow();

        yield MoneyField::new('balance')->setCurrency('RUB')
            ->setCustomOption('storedAsCents', false)->setColumns('col-md-4')
            ->setPermission('ROLE_SUPER_ADMIN')->hideOnIndex();

        yield ChoiceField::new('level')->setChoices(
            [
                'Профессиональный рейтинг' => null,
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5'
            ]
        )
            ->setColumns('col-md-4')
            ->setLabel('Professional rating')
            ->hideOnIndex();


        yield FormField::addRow();
        yield AssociationField::new('city')->setColumns('col-md-4')->setRequired('1');
        yield AssociationField::new('district')->setColumns('col-md-4')->hideOnIndex();

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
        yield AssociationField::new('orders')->hideOnIndex()->setColumns('col-md-4');
        yield AssociationField::new('assignments')->hideOnIndex()->setColumns('col-md-4');

        yield FormField::addRow();
        yield AssociationField::new('notifications')->hideOnIndex()->setColumns('col-md-4')->setPermission('ROLE_SUPER_ADMIN');
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
        $user->setRoles(['ROLE_MASTER']);
        if ($user->getEmail()) {
            $user->setUsername($user->getEmail());
        }

        return $user;
    }

    /**
     *
     * @param EntityManagerInterface $entityManager
     * @param $entityInstance
     */
    public function _updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        /*$user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Currently logged in user is not an instance of User?!');
        }

        if (method_exists($entityInstance, 'setIsVerified')) {
            $subject = 'Bla bla bla';
            $this->mailer->updateCrudUserEmail($user, $subject, 'emails/update_crud_user.html.twig');
        }*/
    }

//    public function configureActions(Actions $actions): Actions
//    {
//        return $actions->add(CRUD::PAGE_INDEX, 'detail');
//    }
}
