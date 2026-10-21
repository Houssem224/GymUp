<?php

namespace App\Shared\Service;

use App\Auth\Entity\User;
use App\Membership\Entity\Subscription;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $fromEmail,
        private readonly string $fromName
    ) {
    }

    /**
     * Send membership expiry reminder email to member
     */
    public function sendMembershipExpiryReminder(Subscription $subscription): void
    {
        $member = $subscription->getMember();
        $plan = $subscription->getPlan();
        $daysRemaining = (new \DateTimeImmutable('today'))->diff($subscription->getEndDate())->days;

        $subject = $daysRemaining <= 3
            ? "Urgent: Your GymUp membership expires in {$daysRemaining} day(s)"
            : "Reminder: Your GymUp membership expires in {$daysRemaining} day(s)";

        $htmlContent = $this->twig->render('email/membership_expiry.html.twig', [
            'memberName' => $member->getFullName() ?? $member->getEmail(),
            'planName' => $plan->getName(),
            'expiryDate' => $subscription->getEndDate()->format('F j, Y'),
            'daysRemaining' => $daysRemaining,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($member->getEmail())
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    /**
     * Send job application acceptance email with credentials
     */
    public function sendJobApplicationAccepted(User $user, string $temporaryPassword): void
    {
        $role = ucfirst(strtolower($user->getRole()->value));

        $htmlContent = $this->twig->render('email/job_application_accepted.html.twig', [
            'fullName' => $user->getFullName() ?? $user->getEmail(),
            'role' => $role,
            'email' => $user->getEmail(),
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $this->getLoginUrl(),
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject("Congratulations! You've been hired as {$role} at GymUp")
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    /**
     * Send job application rejection email
     */
    public function sendJobApplicationRejected(string $email, string $fullName, string $position): void
    {
        $htmlContent = $this->twig->render('email/job_application_rejected.html.twig', [
            'fullName' => $fullName,
            'position' => $position,
        ]);

        $emailObj = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($email)
            ->subject("Update on your application for {$position} position at GymUp")
            ->html($htmlContent);

        $this->mailer->send($emailObj);
    }

    /**
     * Send job application confirmation email
     */
    public function sendJobApplicationConfirmation(string $email, string $fullName, string $position): void
    {
        $htmlContent = $this->twig->render('email/job_application_confirmation.html.twig', [
            'fullName' => $fullName,
            'position' => $position,
        ]);

        $emailObj = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($email)
            ->subject("Application Received: {$position} at GymUp")
            ->html($htmlContent);

        $this->mailer->send($emailObj);
    }

    /**
     * Send welcome email for new member registration
     */
    public function sendMemberWelcomeEmail(User $user, string $temporaryPassword): void
    {
        $htmlContent = $this->twig->render('email/member_welcome.html.twig', [
            'fullName' => $user->getFullName() ?? $user->getEmail(),
            'email' => $user->getEmail(),
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $this->getLoginUrl(),
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject("Welcome to GymUp! Your Account Details")
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    private function getLoginUrl(): string
    {
        // In production, this should come from configuration
        return $_ENV['APP_URL'] ?? 'http://localhost:4200/login';
    }
}
