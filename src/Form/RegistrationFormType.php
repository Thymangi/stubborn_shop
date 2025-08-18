<?php
// src/Form/RegistrationFormType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // correspond à User::$name
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nom / Pseudo',
                'empty_data' => '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Merci de renseigner un e-mail.']),
                    new Assert\Email(['message' => 'E-mail invalide.']),
                    new Assert\Length(['max' => 180]),
                ],
            ])
            // correspond à User::$deliveryAddress
            ->add('deliveryAddress', TextType::class, [
                'required' => false,
                'label' => 'Adresse de livraison',
                'empty_data' => '',
            ])
            // mot de passe non mappé : on le hash dans le contrôleur
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Merci de saisir un mot de passe.']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "J'accepte les CGU",
                'mapped' => false,
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