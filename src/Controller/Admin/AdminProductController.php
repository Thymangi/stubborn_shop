<?php
// src/Controller/Admin/AdminProductController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\Admin\ProductType; // <-- IMPORTANT : le FormType admin
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/product')]
class AdminProductController extends AbstractController
{
    #[Route('/new', name: 'admin_product_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();

        // préremplissage des tailles à 0
        $form = $this->createForm(ProductType::class, $product, [
            'sizes_initial' => ['S'=>0,'M'=>0,'L'=>0,'XL'=>0],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1) Upload image (champ non mappé imageFile)
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid('prod_').'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_dir'), $newFilename);
                $product->setImage($newFilename);
            }

            // 2) Stocks par taille (champs non mappés)
            $product->setStockForSize('S', (int)$form->get('sizeS')->getData());
            $product->setStockForSize('M', (int)$form->get('sizeM')->getData());
            $product->setStockForSize('L', (int)$form->get('sizeL')->getData());
            $product->setStockForSize('XL', (int)$form->get('sizeXl')->getData());

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté !');
            return $this->redirectToRoute('admin_product_list');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET','POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        // préremplir les tailles avec l’existant
        $form = $this->createForm(ProductType::class, $product, [
            'sizes_initial' => [
                'S'  => $product->getStockForSize('S'),
                'M'  => $product->getStockForSize('M'),
                'L'  => $product->getStockForSize('L'),
                'XL' => $product->getStockForSize('XL'),
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1) Upload image si nouvelle image fournie
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid('prod_').'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_dir'), $newFilename);
                $product->setImage($newFilename);
                // (optionnel : supprimer l’ancienne image si besoin)
            }

            // 2) Mettre à jour les stocks
            $product->setStockForSize('S', (int)$form->get('sizeS')->getData());
            $product->setStockForSize('M', (int)$form->get('sizeM')->getData());
            $product->setStockForSize('L', (int)$form->get('sizeL')->getData());
            $product->setStockForSize('XL', (int)$form->get('sizeXl')->getData());

            $em->flush();

            $this->addFlash('success', 'Produit modifié !');
            return $this->redirectToRoute('admin_product_list');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('', name: 'admin_product_list', methods: ['GET'])]
    public function list(ProductRepository $repo): Response
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $repo->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        // CSRF token: même id que dans le Twig
        if ($this->isCsrfTokenValid('delete_product_'.$product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_product_list');
    }
}