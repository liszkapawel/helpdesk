<?php

namespace App\Repository;

use App\Entity\FaqArticle;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqArticle>
 */
class FaqArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqArticle::class);
    }

    public function findByOrganization(Organization $org, bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.organization = :org')
            ->setParameter('org', $org)
            ->orderBy('f.position', 'ASC');

        if ($publishedOnly) {
            $qb->andWhere('f.isPublished = true');
        }

        return $qb->getQuery()->getResult();
    }
}
