<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Admin')]
#[Route('/api/admin')]
class AdminController extends AbstractController
{
    #[Route('/users', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista użytkowników organizacji',
        description: 'Wymaga ROLE_ADMIN',
        responses: [
            new OA\Response(response: 200, description: 'Lista użytkowników'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function listUsers(UserRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $users = $repo->findBy(
            ['organization' => $user->getOrganization()],
            ['lastName' => 'ASC']
        );

        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/users/{id}/role', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Zmień rolę użytkownika',
        description: 'Wymaga ROLE_ADMIN',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', enum: ['ROLE_USER', 'ROLE_AGENT', 'ROLE_ADMIN']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rola zmieniona'),
            new OA\Response(response: 400, description: 'Nieprawidłowa rola'),
            new OA\Response(response: 403, description: 'Brak uprawnień / użytkownik z innej organizacji'),
        ]
    )]
    public function updateRole(User $targetUser, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($targetUser->getOrganization()->getId() !== $currentUser->getOrganization()->getId()) {
            return $this->json(['error' => 'User not in your organization'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $role = $data['role'] ?? null;

        $allowedRoles = ['ROLE_USER', 'ROLE_AGENT', 'ROLE_ADMIN'];
        if (!$role || !in_array($role, $allowedRoles)) {
            return $this->json(['error' => 'Invalid role'], 400);
        }

        $roles = $role === 'ROLE_USER' ? [] : [$role];
        $targetUser->setRoles($roles);

        $em->flush();

        return $this->json($targetUser, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/agents', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista agentów i adminów',
        description: 'Wymaga ROLE_AGENT lub wyżej',
        responses: [
            new OA\Response(response: 200, description: 'Lista agentów'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function listAgents(UserRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_AGENT');

        /** @var User $user */
        $user = $this->getUser();
        $orgUsers = $repo->findBy(['organization' => $user->getOrganization()]);
        $agents = array_filter($orgUsers, fn(User $u) =>
            in_array('ROLE_AGENT', $u->getRoles()) || in_array('ROLE_ADMIN', $u->getRoles())
        );

        return $this->json(array_values($agents), 200, [], ['groups' => ['user:read']]);
    }
}
