<?php
// tests/Controller/CartControllerTest.php

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\BrowserKit\Cookie;

final class CartControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient(); // ✅ un seul client pour tout le test
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createProduct(
        string $name = 'Test Tee',
        float $price = 29.90,
        array $stocks = ['S'=>10,'M'=>5,'L'=>0,'XL'=>2]
    ): Product {
        $p = new Product();
        $p->setName($name);
        $p->setPrice($price);
        $p->setImage('test.jpg');
        $p->setHighlighted(false);
        $p->setDescription('desc');
        $p->setStocks($stocks);
        $this->em->persist($p);
        $this->em->flush();
        return $p;
    }

    public function testAddToCartSuccess(): void
    {
        $p = $this->createProduct();

        $this->client->request('POST', "/cart/add/{$p->getId()}", [
            'size' => 'M',
            'quantity' => 2,
        ]);

        $this->assertResponseRedirects('/order'); // redirige vers app_order
        $this->client->followRedirect();

        $cart = $this->client->getRequest()->getSession()->get('cart', []);
        $this->assertArrayHasKey($p->getId().'_M', $cart);
        $this->assertSame(2, $cart[$p->getId().'_M']['quantity']);
    }

    public function testAddToCartInsufficientStock(): void
    {
        $p = $this->createProduct(stocks: ['M'=>1]);

        $this->client->request('POST', "/cart/add/{$p->getId()}", [
            'size' => 'M',
            'quantity' => 5,
        ]);

        // doit revenir sur la page produit avec un flash "danger"
        $this->assertResponseRedirects("/product/{$p->getId()}");
        $crawler = $this->client->followRedirect();

        $this->assertStringContainsString('Stock insuffisant', $crawler->filter('body')->text());
    }

 public function testUpdateQuantityAndRemoveAndClear(): void
{
    $p = $this->createProduct(stocks: ['M'=>10]);

    // 1) Ajout initial (qty=1) -> démarre la session du client
    $this->client->request('POST', "/cart/add/{$p->getId()}", ['size'=>'M','quantity'=>1]);
    $this->client->followRedirect();

    // Session du client
    $session = $this->client->getRequest()->getSession();
    $this->assertNotNull($session);

    // Factory de token qui ECRIT + SAUVE en session (clé interne _csrf/<id>)
    $putToken = function(string $id) use ($session): string {
        $val = bin2hex(random_bytes(16));
        $session->set('_csrf/'.$id, $val);
        $session->save(); // ✅ essentiel pour que la requête suivante voie le token
        return $val;
    };

    // 2) Update quantité à 4
    $updateId    = 'cart_update_'.$p->getId().'_M';
    $updateToken = $putToken($updateId);

    $this->client->request('POST', "/cart/update/{$p->getId()}/M", [
        '_token'   => $updateToken,
        'quantity' => 4,
    ]);
    $this->client->followRedirect();

    $cart = $this->client->getRequest()->getSession()->get('cart', []);
    $this->assertSame(4, $cart[$p->getId().'_M']['quantity']);

    // 3) Remove
    $removeId    = 'cart_remove_'.$p->getId().'_M';
    $removeToken = $putToken($removeId);

    $this->client->request('POST', "/cart/remove/{$p->getId()}/M", ['_token'=>$removeToken]);
    $this->client->followRedirect();
    $cart = $this->client->getRequest()->getSession()->get('cart', []);
    $this->assertArrayNotHasKey($p->getId().'_M', $cart);

    // 4) Clear
    $this->client->request('POST', "/cart/add/{$p->getId()}", ['size'=>'M','quantity'=>1]);
    $this->client->followRedirect();

    $clearToken = $putToken('cart_clear');
    $this->client->request('POST', '/cart/clear', ['_token'=>$clearToken]);
    $this->client->followRedirect();

    $cart = $this->client->getRequest()->getSession()->get('cart', []);
    $this->assertSame([], $cart);
}

    public function testCartShowTotals(): void
    {
        $p = $this->createProduct(price: 10.0, stocks: ['S'=>10]);

        // Add 3 items
        $this->client->request('POST', "/cart/add/{$p->getId()}", ['size'=>'S','quantity'=>3]);
        $this->client->followRedirect();

        $this->client->request('GET', '/cart');
        $crawler = $this->client->getCrawler();

        // 3 * 10 = 30
        $this->assertStringContainsString('30,00', $crawler->filter('body')->text());
    }
}