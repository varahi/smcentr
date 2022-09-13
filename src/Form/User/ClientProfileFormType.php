<?php

namespace App\Form\User;

use App\Entity\District;
use App\Entity\User;
use App\Entity\City;
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

class ClientProfileFormType extends AbstractType
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
                    'label' => false,
                    'translation_domain' => 'forms',
                ]
            )
            ->add(
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
            )
            ->add(
                'fullName',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'form.full_name'
                    ],
                    'label' => false,
                    'translation_domain' => 'forms',
                ]
            )
            ->add('city', EntityType::class, [
                'class' => City::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => 'List of housing',
                'required' => true,
            ])
            ->add('district', EntityType::class, [
                'class' => District::class,
                'multiple'  => false,
                'expanded'  => false,
                'label' => 'List of housing',
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
                    'label' => false
                ],
                'second_options' => [
                    'attr' => [
                        'placeholder' => 'form.confirm_password'
                    ],
                    'label' => false
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
                'label' => false,
                'translation_domain' => 'forms',
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
