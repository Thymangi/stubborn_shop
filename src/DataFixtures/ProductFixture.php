<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $productsData = [
            [
                'name' => 'Stubborn Classic',
                'price' => 29.99,
                'image' => 'classic.jpg',
                'highlighted' => true,
                'stocks' => [
                    'S' => 10,
                    'M' => 8,
                    'L' => 12,
                    'XL' => 5,
                ],
            ],
            [
                'name' => 'Stubborn Sport',
                'price' => 39.99,
                'image' => 'sport.jpg',
                'highlighted' => true,
                'stocks' => [
                    'S' => 7,
                    'M' => 6,
                    'L' => 10,
                    'XL' => 4,
                ],
            ],
            [
                'name' => 'Stubborn Urban',
                'price' => 35.99,
                'image' => 'urban.jpg',
                'highlighted' => false,
                'stocks' => [
                    'S' => 5,
                    'M' => 5,
                    'L' => 7,
                    'XL' => 2,
                ],
            ],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setImage($data['image']);
            $product->setHighlighted($data['highlighted']);
            $product->setStocks($data['stocks']);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
