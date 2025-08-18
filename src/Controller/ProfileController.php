<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('security/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profil/edit', name: 'profile_edit')]
public function edit(Request $request, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    // Pré-remplissage
    $name = $user->getName();
    $parts = explode(' ', $name, 2);
    $prenom = $parts[0];
    $nom = $parts[1] ?? '';

    $form = $this->createFormBuilder()
        ->add('prenom', TextType::class, [
            'data' => $prenom,
            'required' => true,
        ])
        ->add('nom', TextType::class, [
            'data' => $nom,
            'required' => false,
        ])
        ->getForm();

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $newName = $data['prenom'] . ($data['nom'] ? ' ' . $data['nom'] : '');
        $user->setName($newName);
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour !');
        return $this->redirectToRoute('user_profile');
    }

    return $this->render('security/edit_profile.html.twig', [
        'form' => $form->createView(),
    ]);
}
}