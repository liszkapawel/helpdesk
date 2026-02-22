<?php

namespace App\Entity;

use App\Enum\TicketPriority;
use App\Repository\SlaPolicyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SlaPolicyRepository::class)]
#[ORM\UniqueConstraint(columns: ['organization_id', 'priority'])]
class SlaPolicy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sla:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(length: 20, enumType: TicketPriority::class)]
    #[Groups(['sla:read'])]
    private ?TicketPriority $priority = null;

    #[ORM\Column]
    #[Groups(['sla:read'])]
    private int $responseHours = 24;

    #[ORM\Column]
    #[Groups(['sla:read'])]
    private int $resolutionHours = 72;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPriority(): ?TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getResponseHours(): int
    {
        return $this->responseHours;
    }

    public function setResponseHours(int $responseHours): static
    {
        $this->responseHours = $responseHours;
        return $this;
    }

    public function getResolutionHours(): int
    {
        return $this->resolutionHours;
    }

    public function setResolutionHours(int $resolutionHours): static
    {
        $this->resolutionHours = $resolutionHours;
        return $this;
    }
}
