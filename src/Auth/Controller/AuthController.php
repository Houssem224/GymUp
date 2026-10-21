<?php

namespace App\Auth\Controller;

use App\Auth\Entity\User;
use App\Auth\Service\AuthService;
use App\Auth\Service\UserProvisioningService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserProvisioningService $userProvisioningService
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $fullName = $data['fullName'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userProvisioningService->registerMember($email, $password, $fullName);

            return $this->json([
                'message' => 'Registration successful.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'role' => $user->getRole()->value,
                ]
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->authenticate($email, $password);
        if (!$user) {
            return $this->json(['error' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'role' => $user->getRole()->value,
                'status' => $user->getStatus(),
            ]
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'role' => $user->getRole()->value,
            'status' => $user->getStatus(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $fullName = $data['fullName'] ?? null;
        $newPassword = $data['password'] ?? null;

        $updated = $this->userProvisioningService->updateProfile($user, $fullName, $newPassword);

        return $this->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $updated->getId(),
                'email' => $updated->getEmail(),
                'fullName' => $updated->getFullName(),
            ]
        ]);
    }
}
