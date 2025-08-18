<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du sweat',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix (€)',
                'currency' => 'EUR',
                'scale' => 2,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (jpg, png, webp)',
                'mapped' => false, // car l’upload ne va pas direct en base
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Formats autorisés : .jpg, .png, .webp',
                            ])
                ],
            ])

            ->add('highlighted', CheckboxType::class, [
                'label'    => 'Mettre en avant sur la page d’accueil',
                'required' => false,
            ])
            // Gère les stocks par taille, sous forme d’array 
            ->add('stocks', CollectionType::class, [
                'label' => 'Stock par taille',
                'entry_type' => IntegerType::class,
                'entry_options' => ['label' => false],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => true,
                // Affiche S, M, L, XL en clé (si géré comme array )
                'attr' => ['class' => 'stock-fields'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

