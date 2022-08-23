<?php

namespace App\Form\Order;

use App\Entity\City;
use App\Entity\District;
use App\Entity\JobType;
use App\Entity\Order;
use App\Entity\Profession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'price',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Цена заявки *',
                    ],
                    'label' => false,
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Описание заявки',
                        'class' => 'form-control textarea-form-control',
                    ],
                    'label' => false,
                ]
            )
            //->add('level')
            ->add(
                'level',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'State',
                    'translation_domain' => 'messages',
                    'choices'  => [
                        'Выберите сложноть заявки *' => null,
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                    ],
                ]
            )
            ->add(
                'phone',
                TelType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Телефон *',
                    ],
                    'label' => false,
                ]
            )
            ->add('deadline', DateType::class, [
                'label'     => false,
                'required' => true,
                'widget' => 'single_text',
                //'format' => 'MM/DD/yyyy',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                //'input'  => 'datetime_immutable',
                'attr' => [
                    'class' => 'date'
                ]
            ])
            ->add('profession', EntityType::class, [
                'class' => Profession::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => false,
                'required' => true,
                'placeholder' => 'Выберите профессию *'
            ])
            ->add('jobType', EntityType::class, [
                'class' => JobType::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => false,
                'required' => true,
                'placeholder' => 'Выберите тип работ *'
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => false,
                'required' => true,
                'placeholder' => 'Выберите город *'
            ])
            ->add('district', EntityType::class, [
                'class' => District::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => false,
                'required' => true,
                'placeholder' => 'Выберите район *'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
