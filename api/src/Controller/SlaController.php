<?php

namespace App\Controller;

use App\Entity\SlaPolicy;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Repository\SlaPolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sla')]
class SlaController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(SlaPolicyRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $policies = $repo->findByOrganization($user->getOrganization());

        return $this->json($policies, 200, [], ['groups' => ['sla:read']]);
    }

    #[Route('', methods: ['PUT'])]
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
