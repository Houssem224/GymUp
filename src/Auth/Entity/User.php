<?php

namespace App\Auth\Entity;

use App\Attendance\Entity\CheckIn;
use App\Attendance\Entity\Schedule;
use App\Attendance\Entity\Streak;
use App\Auth\Repository\UserRepository;
use App\Shared\Enum\UserRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(name: 'full_name', type: 'string', length: 150, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    private UserRole $role = UserRole::MEMBER;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(mappedBy: 'member', targetEntity: Schedule::class, cascade: ['persist', 'remove'])]
    private ?Schedule $schedule = null;

    #[ORM\OneToOne(mappedBy: 'member', targetEntity: Streak::class, cascade: ['persist', 'remove'])]
    private ?Streak $streak = null;

    /**
     * @var Collection<int, CheckIn>
     */
    #[ORM\OneToMany(mappedBy: 'member', targetEntity: CheckIn::class, orphanRemoval: true)]
    private Collection $checkIns;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->checkIns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear sensitive temporary data if needed
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return strtoupper($this->status) === 'ACTIVE';
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): self
    {
        if ($schedule === null && $this->schedule !== null) {
            $this->schedule->setMember(null);
        }

        if ($schedule !== null && $schedule->getMember() !== $this) {
            $schedule->setMember($this);
        }

        $this->schedule = $schedule;
        return $this;
    }

    public function getStreak(): ?Streak
    {
        return $this->streak;
    }

    public function setStreak(?Streak $streak): self
    {
        if ($streak === null && $this->streak !== null) {
            $this->streak->setMember(null);
        }

        if ($streak !== null && $streak->getMember() !== $this) {
            $streak->setMember($this);
        }

        $this->streak = $streak;
        return $this;
    }

    /**
     * @return Collection<int, CheckIn>
     */
    public function getCheckIns(): Collection
    {
        return $this->checkIns;
    }

    public function addCheckIn(CheckIn $checkIn): self
    {
        if (!$this->checkIns->contains($checkIn)) {
            $this->checkIns->add($checkIn);
            $checkIn->setMember($this);
        }

        return $this;
    }

    public function removeCheckIn(CheckIn $checkIn): self
    {
        if ($this->checkIns->removeElement($checkIn)) {
            if ($checkIn->getMember() === $this) {
                $checkIn->setMember(null);
            }
        }

        return $this;
    }
}
