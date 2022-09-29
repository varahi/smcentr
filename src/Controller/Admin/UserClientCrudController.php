<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Doctrine\ORM\EntityManagerInterface;

class UserClientCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordEncoder;

    /**
     * @var Security
     */
    private $security;


    /**
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param Security $security
     */
    public function __construct(
        UserPasswordHasherInterface $passwordEncoder,
        Security $security
    ) {
        //$this->passwordEncoder = $passwordEncoder;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;

        // get the user id from the logged in user
        if (null !== $this->security->getUser()) {
            $this->password = $this->security->getUser()->getPassword();
        }
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $role = 'ROLE_CLIENT';
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->where('entity.roles LIKE :roles');
        $qb->setParameter('roles', '%"'.$role.'"%');

        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('professions'))
            ;
    }

    /*public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new', 'edit', 'delete');
    }*/


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Client')
            ->setSearchFields(['firstName', 'lastName', 'email'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled');
        yield EmailField::new('email');

        $roles = [ 'ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_CLIENT', 'ROLE_MASTER', 'ROLE_COMPANY' ];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setPermission('ROLE_SUPER_ADMIN');

        yield TextField::new('password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOption('empty_data', '')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('If the right is not given, leave the field blank.')
            ->setPermission('ROLE_SUPER_ADMIN');

        /*        switch ($pageName) {
                    case Crud::PAGE_INDEX:
                        return [
                            $password,
                        ];
                        break;
                    case Crud::PAGE_DETAIL:
                        return [
                            $password,
                        ];
                        break;
                    case Crud::PAGE_NEW:
                        return [
                            $password,
                        ];
                        break;
                    case Crud::PAGE_EDIT:
                        return [
                            $password,
                        ];
                        break;
                }*/


        //yield TextField::new('firstName');
        //yield TextField::new('lastName');
        yield TextField::new('fullName');
        yield BooleanField::new('isVerified');
        yield BooleanField::new('isDisabled');
        //yield ArrayField::new('roles')->hideOnIndex()->setPermission('ROLE_SUPER_ADMIN');
        yield ImageField::new('avatar')
            ->setBasePath('uploads/files')
            ->setUploadDir('public_html/uploads/files')
            ->setFormType(FileUploadType::class)
            ->setRequired(false);
        yield AssociationField::new('professions')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])->hideOnIndex();
        yield AssociationField::new('jobTypes')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])->hideOnIndex();
        yield AssociationField::new('city');
        yield AssociationField::new('district');
        yield AssociationField::new('orders')->hideOnIndex();
        yield AssociationField::new('assignments')->hideOnIndex();
        yield BooleanField::new('getNotifications');
    }

    /**
     *
     * @param EntityManagerInterface $entityManager
     * @param $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        // set new password with encoder interface
        if (method_exists($entityInstance, 'setPassword')) {
            $clearPassword = trim($this->get('request_stack')->getCurrentRequest()->request->all('User')['password']);

            // if user password not change save the old one
            if (isset($clearPassword) === true && $clearPassword === '') {
                $entityInstance->setPassword($this->password);
            } else {
                //$encodedPassword = $this->passwordEncoder->encodePassword($this->getUser(), $clearPassword);
                $encodedPassword = $this->passwordEncoder->hashPassword($this->getUser(), $this->getUser()->getPassword());
                $entityInstance->setPassword($encodedPassword);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
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
