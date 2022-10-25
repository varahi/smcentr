<?php

namespace App\Form\User;

use App\Entity\District;
use App\Entity\User;
use App\Entity\City;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\File;

class CompanyProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => [
                        'placeholder' => 'form.email'
                    ],
                    'label' => 'Email',
                    'translation_domain' => 'forms',
                ]
            )
            /*->add(
                'phone',
                TelType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'form.phone'
                    ],
                    'label' => false,
                    'translation_domain' => 'forms',
                ]
            )*/
            ->add(
                'fullName',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Company name'
                    ],
                    'label' => 'Company name',
                    'translation_domain' => 'messages',
                ]
            )
            ->add('city', EntityType::class, [
                'class' => City::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => 'City',
                'required' => true,
            ])
            ->add('getNotifications')

            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'required' => false,
                'type' => PasswordType::class,
                'mapped' => false,
                //'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    //new NotBlank([
                    //    'message' => 'Пожалуйста введите пароль',
                    //]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен состоять как минимм из {{ limit }} символов',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                //'first_options' => ['label' => 'form.password'],
                //'second_options' => ['label' => 'form.confirm_password'],
                'first_options' => [
                    'attr' => [
                        'placeholder' => 'form.password'
                    ],
                    'label' => 'form.password'
                ],
                'second_options' => [
                    'attr' => [
                        'placeholder' => 'form.confirm_password'
                    ],
                    'label' => 'form.confirm_password'
                ],
                'invalid_message' => 'Your password does not match the confirmation',
                'translation_domain' => 'forms',
            ])

            ->add('avatar', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/gif',
                            'image/jpeg',
                            'image/pjpeg',
                            'image/png',
                            'image/webp',
                            'image/vnd.wap.wbmp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image document',
                    ])
                ],
                'attr' => [
                    'onchange' => 'readURL(this);'
                ],
                'label' => false,
                'translation_domain' => 'forms',
            ])

            // Bank
            ->add(
                'inn',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Inn'
                    ],
                    'label' => 'Inn',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'ogrn',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Ogrn'
                    ],
                    'label' => 'Ogrn',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'bank',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Bank'
                    ],
                    'label' => 'Bank',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'checkingAccount',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Checking Account'
                    ],
                    'label' => 'Checking Account',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'cardNumber',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Card Number'
                    ],
                    'label' => 'Card Number',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'cardFullName',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Card Full Name'
                    ],
                    'label' => 'Card Full Name',
                    'translation_domain' => 'messages',
                ]
            )

            ->add(
                'responsiblePersonFullName',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Responsible Person Full Name'
                    ],
                    'label' => 'Responsible Person Full Name',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'responsiblePersonPhone',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Responsible Person Phone'
                    ],
                    'label' => 'Responsible Person Phone',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'responsiblePersonEmail',
                EmailType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Responsible Person Email'
                    ],
                    'label' => 'Responsible Person Email',
                    'translation_domain' => 'messages',
                ]
            )
            ->add('companyMasters', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'multiple'  => true,
                'expanded'  => false,
                'by_reference' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :roles')
                        ->andWhere('u.isVerified = 1')
                        ->setParameter('roles', '%"'.'ROLE_MASTER'.'"%')
                        ->orderBy('u.username', 'ASC');
                },
                'label' => 'Company Masters2',
            ])
            ->add('companyClients', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'multiple'  => true,
                'expanded'  => false,
                'by_reference' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :roles')
                        ->andWhere('u.isVerified = 1')
                        ->setParameter('roles', '%"'.'ROLE_CLIENT'.'"%')
                        ->orderBy('u.username', 'ASC');
                },
                'label' => 'Company Clients2',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
