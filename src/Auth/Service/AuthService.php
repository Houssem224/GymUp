<?php

namespace App\Auth\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower(trim($email))]);
        if (!$user) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }
}
