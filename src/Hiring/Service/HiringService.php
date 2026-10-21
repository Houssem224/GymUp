<?php

namespace App\Hiring\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Hiring\Entity\Application;
use App\Hiring\Entity\JobPosting;
use App\Hiring\Repository\ApplicationRepository;
use App\Hiring\Repository\JobPostingRepository;
use App\Shared\Enum\ApplicationStatus;
use App\Shared\Enum\JobPostingStatus;
use App\Shared\Enum\PositionType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HiringService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JobPostingRepository $jobPostingRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly NotificationService $notificationService
    ) {
    }

    public function createJobPosting(PositionType $positionType): JobPosting
    {
        $posting = new JobPosting($positionType);
        $this->jobPostingRepository->save($posting);
        return $posting;
    }

    public function closeJobPosting(JobPosting $jobPosting): void
    {
        $jobPosting->setStatus(JobPostingStatus::CLOSED);
        $this->jobPostingRepository->save($jobPosting);
    }

    public function submitApplication(
        JobPosting $jobPosting,
        string $fullName,
        string $email,
        string $cvPath
    ): Application {
        if (!$jobPosting->isOpen()) {
            throw new LogicException('This job posting is closed to new applications.');
        }

        $application = new Application($jobPosting, trim($fullName), strtolower(trim($email)), $cvPath);
        $this->applicationRepository->save($application);

        // Send confirmation email
        $this->notificationService->sendApplicationReceivedEmail($application);

        return $application;
    }

    public function rejectApplication(Application $application): void
    {
        if (!$application->isPending()) {
            throw new LogicException('Application is already processed.');
        }

        $application->setStatus(ApplicationStatus::REJECTED);
        $this->applicationRepository->save($application);

        // Notify candidate
        $this->notificationService->sendRejectionEmail($application);
    }

    public function acceptApplication(Application $application): User
    {
        if (!$application->isPending()) {
            throw new LogicException('Application is already processed.');
        }

        $application->setStatus(ApplicationStatus::ACCEPTED);

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $application->getEmail()]);
        $targetRole = $application->getJobPosting()->getPositionType()->toUserRole();

        if ($existingUser) {
            // Upgrade role if user already exists
            $existingUser->setRole($targetRole);
            $existingUser->setFullName($application->getFullName());
            $temporaryPassword = '(existing password unchanged)';
            $user = $existingUser;
        } else {
            // Provision new staff User account
            $temporaryPassword = bin2hex(random_bytes(5)) . '!A1'; // secure 12-char temp password
            $user = new User();
            $user->setEmail($application->getEmail());
            $user->setFullName($application->getFullName());
            $user->setRole($targetRole);
            $user->setStatus('ACTIVE');

            $hashedPassword = $this->passwordHasher->hashPassword($user, $temporaryPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        // Send login credentials email to the new coach / receptionist
        $this->notificationService->sendAcceptanceCredentialsEmail($application, $temporaryPassword);

        return $user;
    }
}
