<?php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order', methods: ['GET'])]
    public function index(SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Récupérer le panier
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        // Construire les lignes de commande depuis les IDs en session
        $items = [];
        $total = 0;

        foreach ($cart as $row) {
            /** @var Product|null $product */
            $product = $em->getRepository(Product::class)->find($row['product_id']);
            if (!$product) {
                // produit supprimé en base ? on ignore la ligne
                continue;
            }

            $size     = $row['size'];
            $quantity = (int) $row['quantity'];

            // Sécurité: revérifier le stock au moment de la commande
            $available = $product->getStockForSize($size);
            if ($quantity > $available) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        "Stock insuffisant pour %s (taille %s). Disponible: %d, demandé: %d.",
                        $product->getName(),
                        $size,
                        $available,
                        $quantity
                    )
                );
                return $this->redirectToRoute('cart_show');
            }

            $subtotal = $product->getPrice() * $quantity;

            $items[] = [
                'product'  => $product,
                'size'     => $size,
                'quantity' => $quantity,
                'price'    => $product->getPrice(),
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        if (empty($items)) {
            $this->addFlash('info', 'Votre panier ne contient plus d’articles disponibles.');
            return $this->redirectToRoute('cart_show');
        }

        return $this->render('order/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }
}