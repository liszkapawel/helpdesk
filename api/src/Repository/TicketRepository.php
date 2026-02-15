<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    private function applyRoleFilter(QueryBuilder $qb, User $user): void
    {
        $qb->andWhere('t.organization = :org')->setParameter('org', $user->getOrganization());

        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_AGENT', $user->getRoles())) {
            $qb->andWhere('t.createdBy = :user')->setParameter('user', $user);
        }
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }
        if (!empty($filters['category'])) {
            $qb->andWhere('t.category = :category')->setParameter('category', $filters['category']);
        }
        if (!empty($filters['assignedTo'])) {
            if ($filters['assignedTo'] === 'unassigned') {
                $qb->andWhere('t.assignedTo IS NULL');
            } else {
                $qb->andWhere('t.assignedTo = :assignedTo')->setParameter('assignedTo', $filters['assignedTo']);
            }
        }
        if (!empty($filters['search'])) {
            $qb->andWhere('(t.title LIKE :search OR t.description LIKE :search OR t.submitterName LIKE :search OR t.submitterEmail LIKE :search)')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('t.createdAt >= :dateFrom')->setParameter('dateFrom', $filters['dateFrom']);
        }
        if (!empty($filters['dateTo'])) {
            $qb->andWhere('t.createdAt <= :dateTo')->setParameter('dateTo', $filters['dateTo'] . ' 23:59:59');
        }
    }

    private function applySorting(QueryBuilder $qb, ?string $sortField, ?string $sortOrder): void
    {
        $allowed = ['createdAt', 'title', 'status', 'priority'];
        $field = in_array($sortField, $allowed) ? $sortField : 'createdAt';
        $order = $sortOrder === 'asc' ? 'ASC' : 'DESC';
        $qb->orderBy('t.' . $field, $order);
    }

    public function findByUserRole(User $user, int $limit, int $offset, array $filters = [], ?string $sortField = null, ?string $sortOrder = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyRoleFilter($qb, $user);
        $this->applyFilters($qb, $filters);
        $this->applySorting($qb, $sortField, $sortOrder);

        return $qb->getQuery()->getResult();
    }

    public function countByUserRole(User $user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        $this->applyRoleFilter($qb, $user);
        $this->applyFilters($qb, $filters);

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
