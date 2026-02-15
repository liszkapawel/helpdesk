<?php

namespace App\Controller;

use App\Repository\AuditLogRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Historia zmian')]
class AuditLogController extends AbstractController
{
    #[Route('/api/tickets/{id}/history', methods: ['GET'])]
    #[OA\Get(
        summary: 'Historia zmian ticketa',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista wpisów audytu'),
        ]
    )]
    public function ticketHistory(int $id, AuditLogRepository $repo): JsonResponse
    {
        $logs = $repo->findByEntity('ticket', $id);

        return $this->json($logs, 200, [], ['groups' => ['audit:read', 'user:read']]);
    }
}
