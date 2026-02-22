<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\SlaPolicy;
use App\Enum\TicketPriority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SlaPolicy>
 */
class SlaPolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlaPolicy::class);
    }

    public function findByOrganization(Organization $org): array
    {
        return $this->findBy(['organization' => $org], ['priority' => 'ASC']);
    }

    public function findOneByOrgAndPriority(Organization $org, TicketPriority $priority): ?SlaPolicy
    {
        return $this->findOneBy(['organization' => $org, 'priority' => $priority]);
    }
}
