<?php
// src/Controller/Admin/AdminRegisterController.php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminRegisterController extends AbstractController
{
    #[Route('/register', name: 'admin_register', methods: ['GET','POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setRoles(['ROLE_ADMIN']); //  admin
            $user->setPassword(
                $hasher->hashPassword($user, (string)$request->request->get('password'))
            );
            $user->setName($request->request->get('name') ?: null);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte admin créé. Vous pouvez vous connecter.');
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('admin/security/register.html.twig');
    }
}