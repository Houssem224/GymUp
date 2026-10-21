<?php

namespace App\Attendance\Entity;

use App\Attendance\Repository\StreakRepository;
use App\Auth\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StreakRepository::class)]
#[ORM\Table(name: 'streaks')]
class Streak
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'streak', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $member = null;

    #[ORM\Column(name: 'current_streak', type: 'integer', options: ['default' => 0])]
    private int $currentStreak = 0;

    #[ORM\Column(name: 'last_success_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSuccessDate = null;

    public function __construct(?User $member = null)
    {
        $this->member = $member;
        $this->currentStreak = 0;
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

    public function getCurrentStreak(): int
    {
        return $this->currentStreak;
    }

    public function setCurrentStreak(int $currentStreak): self
    {
        $this->currentStreak = max(0, $currentStreak);
        return $this;
    }

    public function incrementStreak(): self
    {
        $this->currentStreak++;
        return $this;
    }

    public function resetStreak(): self
    {
        $this->currentStreak = 0;
        return $this;
    }

    public function getLastSuccessDate(): ?\DateTimeImmutable
    {
        return $this->lastSuccessDate;
    }

    public function setLastSuccessDate(?\DateTimeImmutable $lastSuccessDate): self
    {
        $this->lastSuccessDate = $lastSuccessDate ? $lastSuccessDate->setTime(0, 0, 0) : null;
        return $this;
    }
}
