<?php
// src/Form/Admin/ProductType.php
namespace App\Form\Admin;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $sizesInitial = $options['sizes_initial'] ?? ['S'=>0,'M'=>0,'L'=>0,'XL'=>0];

        $builder
            // Champs mappés à l'entité Product
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
            ])
            ->add('highlighted', CheckboxType::class, [
                'label' => 'Mettre en avant',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])

            // Upload d'image (non mappé, géré dans le contrôleur)
            ->add('imageFile', FileType::class, [
                'label' => 'Image (JPG/PNG/WebP)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WebP',
                    ]),
                ],
            ])

            // Stocks par taille (non mappés, on lit/écrit dans le contrôleur)
            ->add('sizeS', IntegerType::class, [
                'label' => 'Stock S',
                'mapped' => false,
                'data' => (int)($sizesInitial['S'] ?? 0),
                'empty_data' => '0',
            ])
            ->add('sizeM', IntegerType::class, [
                'label' => 'Stock M',
                'mapped' => false,
                'data' => (int)($sizesInitial['M'] ?? 0),
                'empty_data' => '0',
            ])
            ->add('sizeL', IntegerType::class, [
                'label' => 'Stock L',
                'mapped' => false,
                'data' => (int)($sizesInitial['L'] ?? 0),
                'empty_data' => '0',
            ])
            ->add('sizeXl', IntegerType::class, [
                'label' => 'Stock XL',
                'mapped' => false,
                'data' => (int)($sizesInitial['XL'] ?? 0),
                'empty_data' => '0',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            // pour préremplir les champs de taille depuis le contrôleur
            'sizes_initial' => null,
        ]);
    }
}