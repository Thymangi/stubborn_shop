<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
        public function index(ProductRepository $productRepository): Response
    {
        // On récupère jusqu'à 3 produits "mis en avant"
        $featured = $productRepository->findBy(
            ['highlighted' => true],
            ['id' => 'DESC'],
            3
        );

        // Fallback : si aucun "highlighted", on affiche 3 derniers produits
        if (!$featured) {
            $featured = $productRepository->findBy([], ['id' => 'DESC'], 3);
        }

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featured,
        ]);
    }
}