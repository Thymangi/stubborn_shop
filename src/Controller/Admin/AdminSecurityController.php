<?php
// src/Controller/Admin/AdminSecurityController.php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class AdminSecurityController extends AbstractController
{
    #[Route('/login', name: 'admin_login', methods: ['GET','POST'])]
    public function login(AuthenticationUtils $authUtils): Response
    {
        // Si déjà connecté en admin envoie directement au dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/security/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'admin_logout', methods: ['GET','POST'])]
    public function logout(): void
    {
        throw new \LogicException('Intercepté par le firewall admin.');
        // Intercepté par le firewall "admin" (security.yaml)
        // Cette méthode peut rester vide.
    }
}