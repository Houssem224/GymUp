<?php

namespace App\Auth\Security;

use App\Auth\Entity\User;
use App\Shared\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    private const ROLES = [
        UserRole::ADMIN->value,
        UserRole::RECEPTIONIST->value,
        UserRole::COACH->value,
        UserRole::MEMBER->value,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ROLES, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Admin has universal superuser access
        if ($user->getRole() === UserRole::ADMIN) {
            return true;
        }

        return $user->getRole()->value === $attribute;
    }
}
