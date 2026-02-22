<?php

namespace App\Controller;

use App\Entity\Invite;
use App\Entity\User;
use App\Repository\InviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Zaproszenia')]
class InviteController extends AbstractController
{
    #[Route('/api/invites', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista zaproszeń',
        description: 'Wymaga ROLE_ADMIN',
        responses: [
            new OA\Response(response: 200, description: 'Lista zaproszeń organizacji'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Utwórz zaproszenie',
        description: 'Wymaga ROLE_ADMIN. Generuje kod zaproszenia',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'email', type: 'string', nullable: true, example: 'jan@firma.pl'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Zaproszenie utworzone'),
        ]
    )]
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
    #[OA\Delete(
        summary: 'Usuń zaproszenie',
        description: 'Wymaga ROLE_ADMIN',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Zaproszenie usunięte'),
            new OA\Response(response: 404, description: 'Nie znaleziono'),
        ]
    )]
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
    #[OA\Get(
        summary: 'Walidacja kodu zaproszenia',
        description: 'Publiczny endpoint - nie wymaga autoryzacji',
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wynik walidacji z nazwą organizacji'),
        ]
    )]
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
