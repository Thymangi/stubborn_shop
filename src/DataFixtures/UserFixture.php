<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- CLIENT ---
        $user = new User();
        $user->setName('Client Test');
        $user->setEmail('client@stubborn.com');
        $user->setDeliveryAddress('15 avenue de la Résistance, 69003 Lyon');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'clientpass')
        );
        $manager->persist($user);

        // --- ADMIN ---
        $admin = new User();
        $admin->setName('Admin');
        $admin->setEmail('admin@stubborn.com');
        $admin->setRoles(['ROLE_ADMIN']);
        // Pas d'adresse de livraison pour l’admin
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'adminpass')
        );
        $manager->persist($admin);

        $manager->flush();
    }
}