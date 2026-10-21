<?php

namespace App\Payment\Entity;

use App\Auth\Entity\User;
use App\Membership\Entity\Subscription;
use App\Payment\Repository\PaymentRepository;
use App\Shared\Enum\PaymentMethod;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\Index(name: 'idx_payment_paid_at', columns: ['paid_at'])]
#[ORM\Index(name: 'idx_payment_method', columns: ['method'])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $member = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'subscription_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Subscription $subscription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', enumType: PaymentMethod::class)]
    private PaymentMethod $method;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'processed_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $processedBy = null;

    #[ORM\Column(name: 'paid_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $paidAt;

    public function __construct(
        User $member,
        Subscription $subscription,
        string|float $amount,
        PaymentMethod $method = PaymentMethod::ONLINE,
        ?User $processedBy = null,
        ?\DateTimeImmutable $paidAt = null
    ) {
        $this->member = $member;
        $this->subscription = $subscription;
        $this->setAmount($amount);
        $this->method = $method;
        $this->processedBy = $processedBy;
        $this->paidAt = $paidAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): self
    {
        $this->member = $member;
        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string|float $amount): self
    {
        $this->amount = number_format((float) $amount, 2, '.', '');
        return $this;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function setMethod(PaymentMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): self
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function isCash(): bool
    {
        return $this->method === PaymentMethod::CASH;
    }

    public function isOnline(): bool
    {
        return $this->method === PaymentMethod::ONLINE;
    }

    public function getPaidAt(): \DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }
}
