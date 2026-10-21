<?php

namespace App\Hiring\Service;

use App\Hiring\Entity\Application;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private readonly ?MailerInterface $mailer = null,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function sendApplicationReceivedEmail(Application $application): void
    {
        $subject = "Application Received - GymUp";
        $body = "Hello {$application->getFullName()},\n\n"
            . "Thank you for applying for the position of {$application->getJobPosting()->getPositionType()->value} at GymUp.\n"
            . "We have received your application and CV, and our team will review it shortly.\n\n"
            . "Best regards,\nGymUp Team";

        $this->sendEmail($application->getEmail(), $subject, $body);
    }

    public function sendRejectionEmail(Application $application): void
    {
        $subject = "Update on your application with GymUp";
        $body = "Dear {$application->getFullName()},\n\n"
            . "Thank you for your interest in the {$application->getJobPosting()->getPositionType()->value} position at GymUp.\n"
            . "After careful review, we regret to inform you that we will not be moving forward with your application at this time.\n\n"
            . "We wish you all the best in your job search.\n\n"
            . "Best regards,\nGymUp Team";

        $this->sendEmail($application->getEmail(), $subject, $body);
    }

    public function sendAcceptanceCredentialsEmail(Application $application, string $temporaryPassword): void
    {
        $roleName = $application->getJobPosting()->getPositionType()->value;
        $subject = "Welcome to GymUp - Your Account Details";
        $body = "Dear {$application->getFullName()},\n\n"
            . "Congratulations! Your application for the position of {$roleName} has been accepted.\n\n"
            . "Your staff account has been created with the following credentials:\n"
            . "Email: {$application->getEmail()}\n"
            . "Temporary Password: {$temporaryPassword}\n\n"
            . "Please log in to GymUp and update your password and profile information.\n\n"
            . "Welcome aboard!\nGymUp Team";

        $this->sendEmail($application->getEmail(), $subject, $body);
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        if ($this->mailer !== null) {
            try {
                $email = (new Email())
                    ->from('no-reply@gymup.com')
                    ->to($to)
                    ->subject($subject)
                    ->text($body);

                $this->mailer->send($email);
                return;
            } catch (\Throwable $e) {
                $this->logger->error("Failed to send email to {$to}: " . $e->getMessage());
            }
        }

        // Fallback / local logging
        $this->logger->info("Email to [{$to}] | Subject: [{$subject}]\n{$body}");
    }
}
