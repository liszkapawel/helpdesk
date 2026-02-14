<?php

namespace App\Controller;

use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuditLogController extends AbstractController
{
    #[Route('/api/tickets/{id}/history', methods: ['GET'])]
    public function ticketHistory(int $id, AuditLogRepository $repo): JsonResponse
    {
        $logs = $repo->findByEntity('ticket', $id);

        return $this->json($logs, 200, [], ['groups' => ['audit:read', 'user:read']]);
    }
}
