<?php
// src/Controller/RegisterController.php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[Route('/compte')]
final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher,
        VerifyEmailHelperInterface $verifyEmailHelper
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Si un compte existe déjà avec cet e-mail
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existing) {
                if ($existing->isVerified()) {
                    $this->addFlash('danger', 'Un compte existe déjà avec cet e-mail.');
                    return $this->render('security/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                // Compte non vérifié : renvoyer le lien de confirmation
                $sig = $verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    (string) $existing->getId(),
                    $existing->getEmail(),
                    ['id' => $existing->getId()]
                );

                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@exemple.test', 'Stubborn Shop'))
                    ->to($existing->getEmail())
                    ->subject('Confirme ton adresse e-mail')
                    ->htmlTemplate('emails/verify_email.html.twig')
                    ->context([
                        'signedUrl' => $sig->getSignedUrl(),
                        'expiresAt' => $sig->getExpiresAt(),
                        'user'      => $existing,
                    ]);

                $mailer->send($email);
                $this->addFlash('success', 'Un compte non vérifié existe déjà : nous venons de renvoyer le lien de confirmation.');
                return $this->redirectToRoute('app_login');
            }

            // Création du nouveau compte
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            try {
                $em->persist($user);
                $em->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('danger', 'Un compte existe déjà avec cet e-mail.');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Générer le lien signé et envoyer l’e-mail de vérification
            $sig = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                (string) $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@exemple.test', 'Stubborn Shop'))
                ->to($user->getEmail())
                ->subject('Confirme ton adresse e-mail')
                ->htmlTemplate('emails/verify_email.html.twig')
                ->context([
                    'signedUrl' => $sig->getSignedUrl(),
                    'expiresAt' => $sig->getExpiresAt(),
                    'user'      => $user,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Un e-mail de confirmation t’a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}