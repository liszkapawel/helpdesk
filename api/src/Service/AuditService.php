<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuditService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function log(string $entityType, int $entityId, string $action, ?array $changes, User $performedBy): void
    {
        $log = new AuditLog();
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setAction($action);
        $log->setChanges($changes);
        $log->setPerformedBy($performedBy);

        $this->em->persist($log);
    }
}
