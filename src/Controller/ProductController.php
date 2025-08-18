<?php
// src/Controller/ProductController.php
namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'products_list')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $priceFilter = $request->query->get('price');
        $min = null;
        $max = null;

        if ($priceFilter === '1') {
            $min = 10;
            $max = 29;
        } elseif ($priceFilter === '2') {
            $min = 29;
            $max = 35;
        } elseif ($priceFilter === '3') {
            $min = 35;
            $max = 50;
        }

        $products = ($min !== null && $max !== null)
            ? $productRepository->findByPriceRange($min, $max)
            : $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products'     => $products,
            'priceFilter'  => $priceFilter,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product = null): Response
    {
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}