<?php

namespace App\Entity;

use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ticket:read', 'ticket:list'])]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['ticket:read'])]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: TicketStatus::class)]
    #[Groups(['ticket:read', 'ticket:list'])]
    private TicketStatus $status = TicketStatus::NEW;

    #[ORM\Column(length: 20, enumType: TicketPriority::class)]
    #[Groups(['ticket:read', 'ticket:list'])]
    private TicketPriority $priority = TicketPriority::MEDIUM;

    #[ORM\Column]
    #[Groups(['ticket:read', 'ticket:list'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $firstResponseAt = null;

    #[ORM\OneToOne(targetEntity: SatisfactionRating::class, mappedBy: 'ticket', cascade: ['remove'])]
    #[Groups(['ticket:read'])]
    private ?SatisfactionRating $satisfactionRating = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdTickets')]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?User $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?string $submitterName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?string $submitterEmail = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $trackingToken = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTickets')]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'tickets')]
    #[Groups(['ticket:read', 'ticket:list'])]
    private ?Category $category = null;

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket', cascade: ['remove'])]
    #[Groups(['ticket:read'])]
    private Collection $comments;

    /** @var Collection<int, Attachment> */
    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'ticket', cascade: ['remove'])]
    #[Groups(['ticket:read'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;
        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, Attachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function getSubmitterName(): ?string
    {
        return $this->submitterName;
    }

    public function setSubmitterName(?string $submitterName): static
    {
        $this->submitterName = $submitterName;
        return $this;
    }

    public function getSubmitterEmail(): ?string
    {
        return $this->submitterEmail;
    }

    public function setSubmitterEmail(?string $submitterEmail): static
    {
        $this->submitterEmail = $submitterEmail;
        return $this;
    }

    public function getTrackingToken(): ?string
    {
        return $this->trackingToken;
    }

    public function setTrackingToken(?string $trackingToken): static
    {
        $this->trackingToken = $trackingToken;
        return $this;
    }

    public function getFirstResponseAt(): ?\DateTimeImmutable
    {
        return $this->firstResponseAt;
    }

    public function setFirstResponseAt(?\DateTimeImmutable $firstResponseAt): static
    {
        $this->firstResponseAt = $firstResponseAt;
        return $this;
    }

    public function getSatisfactionRating(): ?SatisfactionRating
    {
        return $this->satisfactionRating;
    }

    public function setSatisfactionRating(?SatisfactionRating $satisfactionRating): static
    {
        $this->satisfactionRating = $satisfactionRating;
        return $this;
    }
}
