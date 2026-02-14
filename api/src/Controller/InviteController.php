<?php

namespace App\Controller;

use App\Entity\Invite;
use App\Entity\User;
use App\Repository\InviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class InviteController extends AbstractController
{
    #[Route('/api/invites', methods: ['GET'])]
    public function list(InviteRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $invites = $repo->findBy(
            ['organization' => $user->getOrganization()],
            ['createdAt' => 'DESC']
        );

        return $this->json($invites, 200, [], ['groups' => ['invite:read', 'organization:read', 'user:read']]);
    }

    #[Route('/api/invites', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $invite = new Invite();
        $invite->setOrganization($user->getOrganization());
        $invite->setCreatedBy($user);

        if (!empty($data['email'])) {
            $invite->setEmail($data['email']);
        }

        $em->persist($invite);
        $em->flush();

        return $this->json($invite, 201, [], ['groups' => ['invite:read', 'organization:read', 'user:read']]);
    }

    #[Route('/api/invites/{id}', methods: ['DELETE'])]
    public function delete(Invite $invite, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        if ($invite->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $em->remove($invite);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/api/invites/{code}/validate', methods: ['GET'])]
    public function validate(string $code, InviteRepository $repo): JsonResponse
    {
        $invite = $repo->findOneBy(['code' => $code]);

        if (!$invite || !$invite->isValid()) {
            return $this->json(['valid' => false], 200);
        }

        return $this->json([
            'valid' => true,
            'organizationName' => $invite->getOrganization()->getName(),
        ]);
    }
}
