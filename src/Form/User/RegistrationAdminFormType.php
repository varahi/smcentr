<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class RegistrationAdminFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fullName',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Full name'
                    ],
                    'label' => 'Full name',
                    'translation_domain' => 'messages',
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'form.email'
                    ],
                    'label' => 'form.email',
                    'translation_domain' => 'forms',
                ]
            )
            ->add('plainPassword', RepeatedType::class, [
                'required' => false,
                'type' => PasswordType::class,
                'mapped' => false,
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
