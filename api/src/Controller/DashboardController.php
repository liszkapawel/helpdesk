<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/api/dashboard/stats', methods: ['GET'])]
    #[OA\Get(
        summary: 'Statystyki dashboard',
        description: 'Zwraca statystyki ticketów: wg statusu, priorytetu, w czasie, avg resolution. Admini/agenci widzą też statystyki per agent',
        responses: [
            new OA\Response(response: 200, description: 'Statystyki'),
        ]
    )]
    public function stats(TicketRepository $ticketRepo, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $byStatus = $ticketRepo->countByStatus($user);
        $byPriority = $ticketRepo->countByPriority($user);
        $overTime = $ticketRepo->countOverTime($user);
        $avgResolution = $ticketRepo->avgResolutionTime($user);
        $total = $ticketRepo->countByUserRole($user);

        // Average satisfaction rating
        $conn = $em->getConnection();
        $avgSatisfaction = $conn->fetchOne(
            'SELECT AVG(sr.rating) FROM satisfaction_rating sr JOIN ticket t ON sr.ticket_id = t.id WHERE t.organization_id = :org',
            ['org' => $user->getOrganization()->getId()]
        );

        $response = [
            'total' => $total,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'overTime' => $overTime,
            'avgResolutionHours' => $avgResolution,
            'avgSatisfaction' => $avgSatisfaction !== null && $avgSatisfaction !== false ? round((float) $avgSatisfaction, 1) : null,
        ];

        // Per-agent stats only for admin/agent
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())) {
            $perAgent = $ticketRepo->countPerAgent($user->getOrganization());
            // Resolve agent names
            $agentStats = [];
            foreach ($perAgent as $row) {
                $agent = $userRepo->find($row['agentId']);
                if ($agent) {
                    $agentStats[] = [
                        'agentId' => $row['agentId'],
                        'name' => $agent->getFirstName() . ' ' . $agent->getLastName(),
                        'count' => $row['count'],
                    ];
                }
            }
            $response['perAgent'] = $agentStats;
        }

        return $this->json($response);
    }
}
