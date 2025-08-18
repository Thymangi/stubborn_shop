<?php
// src/Security/UserChecker.php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // ✅ Les admins passent toujours
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return;
        }

        // ❌ Bloquer les autres si non vérifiés
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Merci de valider ton e-mail pour te connecter.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Pas de vérification supplémentaire après authentification
    }
}