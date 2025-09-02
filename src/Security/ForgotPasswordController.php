<?php
// src/Controller/Security/ForgotPasswordController.php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password_request', methods: ['GET'])]
    public function request(): Response
    {
        // Page placeholder pour l’instant
        return $this->render('security/forgot_password.html.twig');
    }
}