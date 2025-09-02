<?php
// tests/Smoke/CartCheckoutSmokeTest.php

namespace App\Tests\Smoke;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartCheckoutSmokeTest extends WebTestCase
{
    public function testAddToCartAndCheckout(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        // 0) Schéma propre (SQLite en mémoire)
        $this->ensureSchema($em);

        // 1) USER connecté (et compatible avec ton UserChecker)
        $user = (new User())
            ->setEmail('smoke+test@example.test')
            ->setName('Smoke User')
            ->setPassword('dummy')
            ->setRoles(['ROLE_USER']);
        if (method_exists($user, 'setIsVerified')) $user->setIsVerified(true);
        if (method_exists($user, 'setEnabled'))    $user->setEnabled(true);
        if (method_exists($user, 'setActive'))     $user->setActive(true);
        $em->persist($user);

        // 2) PRODUIT de test (stocks en JSON + champs requis)
        $p = (new Product())
            ->setName('Smoke Hoodie')
            ->setPrice(29.90)
            ->setImage('assets/img/placeholder.png')
            ->setHighlighted(false)
            ->setStocks(['S' => 5, 'M' => 0, 'L' => 0, 'XL' => 0]);
        $em->persist($p);
        $em->flush();

        // 3) Connexion + IP locale (si règle d’IP sur /cart)
        $client->loginUser($user, 'user_shared');
        $client->setServerParameter('REMOTE_ADDR', '127.0.0.1');
        $client->disableReboot();

        // 4) Ajout au panier
        $client->request('POST', "/cart/add/{$p->getId()}", [
            'size'     => 'S',
            'quantity' => 1,
        ]);

        // 👉 Option A : on accepte /cart OU /products
        $this->assertTrue($client->getResponse()->isRedirect());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('#^(/cart|/products)$#', $location);

        // Suivre la redirection (peut être /products)
        $client->followRedirect();

        // Pour être robuste, on va ensuite sur /cart et on vérifie la présence du produit
        $client->request('GET', '/cart');
        $this->assertPageContains($client, 'Smoke Hoodie');

        // 5) Paiement (en test, create-session redirige vers /checkout/success)
        $client->request('POST', '/checkout/create-session');
        $this->assertResponseRedirects('/checkout/success');
        $client->followRedirect();
        $this->assertPageContains($client, 'Merci');
    }

    private function ensureSchema(EntityManagerInterface $em): void
    {
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }

    private function assertPageContains($client, string $needle): void
    {
        $this->assertStringContainsString($needle, $client->getResponse()->getContent());
    }
}