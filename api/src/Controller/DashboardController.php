<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/api/dashboard/stats', methods: ['GET'])]
    public function stats(TicketRepository $ticketRepo, UserRepository $userRepo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $byStatus = $ticketRepo->countByStatus($user);
        $byPriority = $ticketRepo->countByPriority($user);
        $overTime = $ticketRepo->countOverTime($user);
        $avgResolution = $ticketRepo->avgResolutionTime($user);
        $total = $ticketRepo->countByUserRole($user);

        $response = [
            'total' => $total,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'overTime' => $overTime,
            'avgResolutionHours' => $avgResolution,
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
