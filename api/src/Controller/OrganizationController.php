<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/organization')]
class OrganizationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getOrganization(), 200, [], ['groups' => ['organization:read']]);
    }

    #[Route('', methods: ['PUT'])]
    public function update(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $org = $user->getOrganization();
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $org->setName($data['name']);
        }

        $em->flush();

        return $this->json($org, 200, [], ['groups' => ['organization:read']]);
    }
}
