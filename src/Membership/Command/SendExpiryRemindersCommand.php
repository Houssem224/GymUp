<?php

namespace App\Membership\Command;

use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Service\EmailNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'membership:send-expiry-reminders',
    description: 'Send email reminders to members whose memberships are expiring soon'
)]
class SendExpiryRemindersCommand extends Command
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EmailNotificationService $emailService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days before expiry to send reminders (default: 7)',
                7
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run without sending actual emails'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        $io->title('Membership Expiry Reminder Sender');
        $io->text("Finding subscriptions expiring within {$days} days...");

        $expiringSubscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->join('s.member', 'm')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate >= :today')
            ->andWhere('s.endDate <= :limit')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('limit', new \DateTimeImmutable("+{$days} days"))
            ->orderBy('s.endDate', 'ASC')
            ->getQuery()
            ->getResult();

        $count = count($expiringSubscriptions);

        if ($count === 0) {
            $io->success('No expiring subscriptions found.');
            return Command::SUCCESS;
        }

        $io->text("Found {$count} expiring subscription(s).");

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No emails will be sent.');
            $io->table(
                ['Member', 'Email', 'Plan', 'Expiry Date', 'Days Remaining'],
                array_map(function ($sub) {
                    $member = $sub->getMember();
                    $plan = $sub->getPlan();
                    $daysRemaining = (new \DateTimeImmutable('today'))->diff($sub->getEndDate())->days;
                    return [
                        $member->getFullName() ?? 'N/A',
                        $member->getEmail(),
                        $plan->getName(),
                        $sub->getEndDate()->format('Y-m-d'),
                        $daysRemaining,
                    ];
                }, $expiringSubscriptions)
            );
            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        $io->progressStart($count);

        foreach ($expiringSubscriptions as $subscription) {
            try {
                $this->emailService->sendMembershipExpiryReminder($subscription);
                $sent++;
                $this->logger->info("Sent expiry reminder to {$subscription->getMember()->getEmail()}");
            } catch (\Throwable $e) {
                $failed++;
                $this->logger->error("Failed to send expiry reminder to {$subscription->getMember()->getEmail()}: " . $e->getMessage());
                $io->error("Failed to send to {$subscription->getMember()->getEmail()}: {$e->getMessage()}");
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->table(
            ['Metric', 'Count'],
            [
                ['Total subscriptions', $count],
                ['Emails sent', $sent],
                ['Emails failed', $failed],
            ]
        );

        if ($failed > 0) {
            $io->warning("Completed with {$failed} failure(s). Check logs for details.");
            return Command::FAILURE;
        }

        $io->success("Successfully sent {$sent} expiry reminder(s).");
        return Command::SUCCESS;
    }
}
