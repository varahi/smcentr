<?php

namespace App\Controller\Admin;

use App\Entity\Firebase;
use App\Entity\Order;
use App\Repository\FirebaseRepository;
use App\Repository\UserRepository;
use App\Service\Order\SetBalanceService;
use App\Service\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderNewCrudController extends AbstractCrudController
{
    public const ROLE_MASTER = 'ROLE_MASTER';

    public function __construct(
        SetBalanceService $setBalanceService,
        PushNotification $pushNotification,
        TranslatorInterface $translator,
        FirebaseRepository $firebaseRepository,
        ManagerRegistry $doctrine,
        UserRepository $userRepository
    ) {
        $this->setBalanceService = $setBalanceService;
        $this->pushNotification = $pushNotification;
        $this->translator = $translator;
        $this->firebaseRepository = $firebaseRepository;
        $this->doctrine = $doctrine;
        $this->userRepository = $userRepository;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        if (isset($_GET['query'])) {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        } else {
            $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
            $qb
                ->where($qb->expr()->eq('entity.status', 0))
            ;
        }

        return $qb;
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
            ->setEntityLabelInSingular('New orders list')
            ->setEntityLabelInPlural('New orders list')
            ->setSearchFields(['title', 'price', 'description', 'id'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('assets/css/easy_admin_custom.css')
            ->addJsFile('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js')
            ->addJsFile('assets/js/jquery.maskedinput.min.js')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Main Info')->setIcon('fa fa-info')->setCssClass('col-sm-8');
        yield FormField::addRow();

        yield IntegerField::new('id')->setFormTypeOption('disabled', 'disabled')->hideWhenCreating();
        //yield TextField::new('title');
        //yield TextField::new('price');
        yield TelephoneField::new('phone')->setColumns('col-md-4')->hideOnIndex();
        yield TextareaField::new('description')->setColumns('col-md-10');
        yield MoneyField::new('price')->setCurrency('RUB')->setCustomOption('storedAsCents', false)->setColumns('col-md-3');
        yield TextField::new('estimatedTime')->setColumns('col-md-3')->hideOnIndex();
        yield DateField::new('deadline')->setColumns('col-md-3')->hideOnIndex();

        yield TextField::new('address')->setColumns('col-md-10');
        yield AssociationField::new('performer')
            ->setFormTypeOption('choice_label', 'selector')
            ->hideOnIndex()->setColumns('col-md-10');
        yield FormField::addRow();
        yield IntegerField::new('quantity')->setColumns('col-md-2');
        yield FormField::addPanel('Additional Info')->setIcon('fa fa-info-circle')->setCssClass('col-sm-4');
        yield FormField::addRow();

        yield AssociationField::new('city')->hideOnIndex()->setColumns('col-md-10')->setRequired(true)->addCssClass('js-select-city');
        yield AssociationField::new('district')->hideOnIndex()->setColumns('col-md-10')->addCssClass('js-hide-district');

        yield AssociationField::new('users')->setColumns('col-md-10')->setLabel('Customer')->setRequired(true);
        yield AssociationField::new('profession')->hideOnIndex()->setColumns('col-md-10')->setRequired(true);
        yield AssociationField::new('jobType')->hideOnIndex()->setColumns('col-md-10')->setRequired(true);
        yield ChoiceField::new('level')->setChoices(
            [
                'Выберите' => '',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
            ]
        )->hideOnIndex()->setColumns('col-md-10')->setRequired(true);

        yield ChoiceField::new('status')->setChoices(
            [
                'Выберите' => '',
                'Новая' => '0',
                'В работе' => '1',
                'Завершена' => '9',
            ]
        )->setLabel('Order status')->setColumns('col-md-10')->setRequired(true);

        yield ChoiceField::new('typeCreated')->setChoices(
            [
                'Выберите' => '',
                'Клиент' => '1',
                'Мастер' => '2',
                'Компания' => '3',
            ]
        )->hideOnIndex()->setColumns('col-md-10')->setRequired(true);

        yield TextField::new('customTaxRate')->setColumns('col-md-10')->hideWhenUpdating()
            ->hideOnIndex()->setPermission('ROLE_EDITOR');
        yield TextField::new('customTaxRate')->setColumns('col-md-10')->hideWhenCreating()
            ->hideOnIndex()->setPermission('ROLE_EDITOR');

        //yield BooleanField::new('sendOwnMasters')->setColumns('col-md-10')->hideOnIndex();
        //yield BooleanField::new('sendAllMasters')->setColumns('col-md-10')->hideOnIndex();
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder  = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        //$this->updateTax($formBuilder);
        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->updateBalance($formBuilder);
        $this->sendPushNotification($formBuilder);

        return $formBuilder;
    }

    protected function sendPushNotification(FormBuilderInterface $formBuilder): void
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Order order */
            $order = $event->getData();
            $subject = $order->getJobType()->getName() .' '. $order->getPrice() . ' руб.';

            // Send push notification
            $context = [
                'title' => $this->translator->trans('Notification new order for master', array(), 'messages'),
                'clickAction' => 'https://smcentr.su/',
                'icon' => 'https://smcentr.su/assets/images/logo_black.svg'
            ];
            if ($order->getPerformer()) {
                // If isset performer
                $tokens = $this->firebaseRepository->findAllByOneUser($order->getPerformer());
            } else {
                // If not isset performer push sending for all relevant masters
                $relevantMasters = $this->userRepository->findByCityAndProfession(self::ROLE_MASTER, $order->getCity(), $order->getProfession());
                if (isset($relevantMasters) && !empty($relevantMasters)) {
                    foreach ($relevantMasters as $master) {
                        $relevantMastersIds[] = $master->getId();
                    }
                    $entityManager = $this->doctrine->getManager();
                    $tokens = $entityManager->getRepository(Firebase::class)->findBy(array('user' => $relevantMastersIds));
                }
            }
            if (isset($tokens)) {
                $this->pushNotification->sendMQPushNotification($subject, $context, $tokens);
            }
        });
    }

    protected function updateBalance(FormBuilderInterface $formBuilder): void
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Order order */
            $order = $event->getData();
            if ($order->getPerformer()) {
                $this->setBalanceService->setBalance($order);
            }
        });
    }
}
