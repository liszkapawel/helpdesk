<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    #[Route('/users', methods: ['GET'])]
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
