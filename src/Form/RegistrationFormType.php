<?php
// src/Form/RegistrationFormType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Types de champs
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

// Contraintes
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // correspond à User::$name
            ->add('name', TextType::class, [
                'required'   => false,
                'label'      => 'Nom / Pseudo',
                'empty_data' => '',
            ])

            ->add('email', EmailType::class, [
                'label'       => 'E-mail',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Merci de renseigner un e-mail.']),
                    new Assert\Email(['message' => 'E-mail invalide.']),
                    new Assert\Length(['max' => 180]),
                ],
            ])

            // correspond à User::$deliveryAddress
            ->add('deliveryAddress', TextType::class, [
                'required'   => false,
                'label'      => 'Adresse de livraison',
                'empty_data' => '',
            ])

            // Mot de passe en 2 champs (confirmation), non mappé sur l’entité.
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                // Options du 1er champ
                'first_options'   => [
                    'label'       => 'Mot de passe',
                    'attr'        => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Merci de saisir un mot de passe.']),
                        new Assert\Length([
                            'min'        => 8,
                            'minMessage' => 'Au moins {{ limit }} caractères.',
                            'max'        => 4096,
                        ]),
                    ],
                ],
                // Options du 2e champ (confirmation)
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'label'       => "J'accepte les CGU",
                'mapped'      => false,
                'constraints' => [
                    new Assert\IsTrue(['message' => 'Vous devez accepter les conditions.']),
                ],
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