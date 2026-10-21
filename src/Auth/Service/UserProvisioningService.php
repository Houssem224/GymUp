<?php

namespace App\Auth\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProvisioningService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function registerMember(string $email, string $plainPassword, ?string $fullName = null): User
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address format.');
        }

        if (strlen($plainPassword) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters long.');
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new InvalidArgumentException('An account with this email already exists.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRole(UserRole::MEMBER);
        $user->setStatus('ACTIVE');

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        return $user;
    }

    public function updateProfile(User $user, ?string $fullName = null, ?string $newPassword = null): User
    {
        if ($fullName !== null) {
            $user->setFullName(trim($fullName));
        }

        if ($newPassword !== null && strlen($newPassword) >= 6) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        }

        $this->entityManager->flush();

        return $user;
    }
}
