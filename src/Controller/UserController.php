<?php
// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[Route('/compte')]
class UserController extends AbstractController
{
    public function __construct(private VerifyEmailHelperInterface $verifyEmailHelper) {}

    #[Route('/profil', name: 'user_profile')]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig');
    }

    #[Route('/commandes', name: 'user_orders')]
    public function orders(): Response
    {
        return $this->render('user/orders.html.twig', ['orders' => []]);
    }

    #[Route('/profil/modifier', name: 'user_profile_edit', methods: ['GET','POST'])]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $logger->info('[register] form invalid', [
                'errors' => (string) $form->getErrors(true, false)
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plain));

            // Sauvegarde user (id nécessaire pour signer le lien)
            $em->persist($user);
            $em->flush();

            // Lien signé de vérification
            $signature = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                (string) $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            // Envoi d’e-mail avec fallback + logs
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@exemple.test', 'Stubborn Shop'))
                    ->to($user->getEmail())
                    ->subject('Confirme ton adresse e-mail')
                    ->htmlTemplate('emails/verify_email.html.twig')
                    ->context([
                        'signedUrl' => $signature->getSignedUrl(),
                        'expiresAt' => $signature->getExpiresAt(),
                        'user' => $user,
                    ]);

                $mailer->send($email);
                $logger->info('[register] verification email sent', ['to' => $user->getEmail()]);
                $this->addFlash('success', 'Un e-mail de confirmation t’a été envoyé.');
            } catch (\Throwable $e) {
                $logger->error('[register] templated email failed, fallback to plain', ['error' => $e->getMessage()]);
                try {
                    $plainEmail = (new Email())
                        ->from('no-reply@exemple.test')
                        ->to($user->getEmail())
                        ->subject('Confirme ton adresse e-mail')
                        ->text("Clique ce lien pour vérifier ton e-mail : ".$signature->getSignedUrl());
                    $mailer->send($plainEmail);
                    $this->addFlash('warning', "Le modèle d'e-mail a échoué, e-mail texte envoyé.");
                } catch (\Throwable $e2) {
                    $logger->critical('[register] fallback email failed', ['error' => $e2->getMessage()]);
                    $this->addFlash('danger', "Impossible d'envoyer l'e-mail de vérification.");
                }
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->query->get('id');
        if (!$userId) {
            $this->addFlash('danger', 'Lien invalide.');
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                (string) $user->getId(),
                $user->getEmail()
            );

            $user->setIsVerified(true);
            $em->flush();
            $this->addFlash('success', 'Ton e-mail a bien été confirmé !');
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('danger', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('user_profile');
    }
}