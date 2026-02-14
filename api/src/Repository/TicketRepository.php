<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    private function applyRoleFilter($qb, User $user): void
    {
        $qb->andWhere('t.organization = :org')->setParameter('org', $user->getOrganization());

        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_AGENT', $user->getRoles())) {
            $qb->andWhere('t.createdBy = :user')->setParameter('user', $user);
        }
    }

    public function findByUserRole(User $user, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyRoleFilter($qb, $user);

        return $qb->getQuery()->getResult();
    }

    public function countByUserRole(User $user): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        $this->applyRoleFilter($qb, $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByStatus(User $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as count')
            ->groupBy('t.status');

        $this->applyRoleFilter($qb, $user);

        return $qb->getQuery()->getResult();
    }

    public function countByPriority(User $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.priority as priority, COUNT(t.id) as count')
            ->groupBy('t.priority');

        $this->applyRoleFilter($qb, $user);

        return $qb->getQuery()->getResult();
    }

    public function countOverTime(User $user, int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        $qb = $this->createQueryBuilder('t')
            ->select("SUBSTRING(t.createdAt, 1, 10) as date, COUNT(t.id) as count")
            ->where('t.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $this->applyRoleFilter($qb, $user);

        return $qb->getQuery()->getResult();
    }

    public function avgResolutionTime(User $user): ?float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at)) as avgHours FROM ticket t WHERE t.closed_at IS NOT NULL AND t.organization_id = :org';
        $params = ['org' => $user->getOrganization()->getId()];

        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_AGENT', $user->getRoles())) {
            $sql .= ' AND t.created_by_id = :user';
            $params['user'] = $user->getId();
        }

        $result = $conn->fetchOne($sql, $params);
        return $result !== null && $result !== false ? round((float) $result, 1) : null;
    }

    public function countPerAgent(Organization $org): array
    {
        return $this->createQueryBuilder('t')
            ->select('IDENTITY(t.assignedTo) as agentId, COUNT(t.id) as count')
            ->where('t.assignedTo IS NOT NULL')
            ->andWhere('t.organization = :org')
            ->setParameter('org', $org)
            ->groupBy('t.assignedTo')
            ->getQuery()
            ->getResult();
    }
}
