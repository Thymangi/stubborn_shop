<?php
// src/Controller/DevMailController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class DevMailController extends AbstractController
{
    #[Route('/_dev/test-mail', name: 'dev_test_mail', methods: ['GET'])]
    public function __invoke(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('no-reply@exemple.test')
            ->to('ton.email@exemple.com')
            ->subject('Test Mailer OK')
            ->html('<p>Mailer OK ✅</p>');

        $mailer->send($email);

        return new Response('OK: mail envoyé');
    }
}