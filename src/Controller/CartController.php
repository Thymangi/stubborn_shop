<?php
// src/Controller/CartController.php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;

class CartController extends AbstractController
{
    private const VALID_SIZES = ['S','M','L','XL'];

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $back = $request->headers->get('referer') ?? $this->generateUrl('products_list');

        $size     = strtoupper((string) $request->request->get('size', ''));
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        if (!in_array($size, self::VALID_SIZES, true)) {
            $this->addFlash('danger', 'Veuillez choisir une taille valide.');
            return $this->redirect($back);
        }

        $available = $product->getStockForSize($size);
        $cart      = $session->get('cart', []);
        $key       = $id . '_' . $size;

        $existingQty = $cart[$key]['quantity'] ?? 0;
        $wantedQty   = $existingQty + $quantity;

        if ($available <= 0 || $wantedQty > $available) {
            $this->addFlash('danger', sprintf(
                "Stock insuffisant pour la taille %s (disponible : %d, demandé : %d).",
                $size, $available, $wantedQty
            ));
            return $this->redirect($back);
        }

        if (!isset($cart[$key])) {
            $cart[$key] = ['product_id' => $id, 'size' => $size, 'quantity' => 0];
        }
        $cart[$key]['quantity'] = $wantedQty;

        $session->set('cart', $cart);
        $this->addFlash('success', 'Produit ajouté au panier.');

        // rester sur la même page (UX fluide)
        return $this->redirect($back);
    }

    #[Route('/cart', name: 'cart_show', methods: ['GET'])]
    public function show(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart  = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $row) {
            $product = $em->getRepository(Product::class)->find($row['product_id']);
            if (!$product) continue;

            $subtotal = $product->getPrice() * $row['quantity'];
            $items[] = [
                'product'  => $product,
                'size'     => $row['size'],
                'quantity' => $row['quantity'],
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        return $this->render('cart/index.html.twig', compact('items','total'));
    }

    private function csrfOkOrBypass(string $id, Request $request, KernelInterface $kernel): bool
    {
        if ('test' === $kernel->getEnvironment()) {
            return true;
        }
        return $this->isCsrfTokenValid($id, (string) $request->request->get('_token'));
    }

    #[Route('/cart/update/{id}/{size}', name: 'cart_update', methods: ['POST'])]
    public function update(
        int $id,
        string $size,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em,
        KernelInterface $kernel
    ): Response {
        if (!$this->csrfOkOrBypass('cart_update_'.$id.'_'.$size, $request, $kernel)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $size = strtoupper($size);
        if (!in_array($size, self::VALID_SIZES, true)) {
            $this->addFlash('danger', 'Taille invalide.');
            return $this->redirectToRoute('cart_show');
        }

        $qty  = max(0, (int) $request->request->get('quantity', 0));
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $available = $product->getStockForSize($size);
        $key = $id.'_'.$size;

        $cart = $session->get('cart', []);

        if ($qty === 0) {
            unset($cart[$key]);
            $this->addFlash('success', 'Article supprimé.');
        } else {
            if ($qty > $available) {
                $this->addFlash('danger', 'Stock insuffisant pour la quantité demandée.');
                return $this->redirectToRoute('cart_show');
            }
            if (!isset($cart[$key])) {
                $cart[$key] = ['product_id' => $id, 'size' => $size, 'quantity' => 0];
            }
            $cart[$key]['quantity'] = $qty;
            $this->addFlash('success', 'Panier mis à jour.');
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/remove/{id}/{size}', name: 'cart_remove', methods: ['POST'])]
    public function remove(
        int $id,
        string $size,
        Request $request,
        SessionInterface $session,
        KernelInterface $kernel
    ): Response {
        $norm = strtoupper($size);

        if (!in_array($norm, self::VALID_SIZES, true)) {
            $this->addFlash('danger', 'Taille invalide.');
            return $this->redirectToRoute('cart_show');
        }

        if (!$this->csrfOkOrBypass('cart_remove_'.$id.'_'.$norm, $request, $kernel)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $key  = $id.'_'.$norm;
        $cart = $session->get('cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Article supprimé.');
        } else {
            $this->addFlash('info', 'Article déjà supprimé.');
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        SessionInterface $session,
        KernelInterface $kernel
    ): Response {
        if (!$this->csrfOkOrBypass('cart_clear', $request, $kernel)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $session->remove('cart');
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('cart_show');
    }
}