<?php

namespace App\Controller;

use App\Entity\SlaPolicy;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Repository\SlaPolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'SLA')]
#[Route('/api/sla')]
class SlaController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista polityk SLA',
        description: 'Zwraca polityki SLA dla organizacji zalogowanego użytkownika',
        responses: [
            new OA\Response(response: 200, description: 'Lista polityk SLA'),
        ]
    )]
    public function list(SlaPolicyRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $policies = $repo->findByOrganization($user->getOrganization());

        return $this->json($policies, 200, [], ['groups' => ['sla:read']]);
    }

    #[Route('', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Upsert polityk SLA',
        description: 'Tylko dla administratorów. Tworzy lub aktualizuje polityki SLA per priorytet',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                        new OA\Property(property: 'responseHours', type: 'integer', example: 8),
                        new OA\Property(property: 'resolutionHours', type: 'integer', example: 24),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zaktualizowane polityki SLA'),
            new OA\Response(response: 400, description: 'Nieprawidłowe dane'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function upsert(Request $request, SlaPolicyRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $org = $user->getOrganization();
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Expected array'], 400);
        }

        foreach ($data as $item) {
            $priority = TicketPriority::tryFrom($item['priority'] ?? '');
            if (!$priority) {
                continue;
            }

            $policy = $repo->findOneByOrgAndPriority($org, $priority);
            if (!$policy) {
                $policy = new SlaPolicy();
                $policy->setOrganization($org);
                $policy->setPriority($priority);
                $em->persist($policy);
            }

            if (isset($item['responseHours'])) {
                $policy->setResponseHours(max(1, (int) $item['responseHours']));
            }
            if (isset($item['resolutionHours'])) {
                $policy->setResolutionHours(max(1, (int) $item['resolutionHours']));
            }
        }

        $em->flush();

        $policies = $repo->findByOrganization($org);

        return $this->json($policies, 200, [], ['groups' => ['sla:read']]);
    }
}
