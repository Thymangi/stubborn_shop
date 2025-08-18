<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET','POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        file_put_contents('debug_login.txt', 'Login appelé: ' . date('c') . "\n", FILE_APPEND);

        if ($this->getUser()) {
            file_put_contents('debug_login.txt', "Déjà connecté, redirect HOME\n", FILE_APPEND);
            return $this->redirectToRoute('home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Déconnexion GLOBALE (admin ou user)
     * Interceptée par le firewall (aucun code ici).
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET','POST'])]
    public function logout(): void
    {
        throw new \LogicException('Intercepté par le firewall. Ce code ne doit jamais être exécuté.');
    }
}