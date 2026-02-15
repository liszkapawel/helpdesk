<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Kategorie')]
#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista kategorii',
        description: 'Zwraca kategorie organizacji użytkownika',
        responses: [
            new OA\Response(response: 200, description: 'Lista kategorii'),
        ]
    )]
    public function list(CategoryRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $org = $user->getOrganization();

        return $this->json(
            $repo->findBy(['organization' => $org], ['name' => 'ASC']),
            200,
            [],
            ['groups' => ['category:read']],
        );
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Utwórz kategorię',
        description: 'Wymaga ROLE_ADMIN',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Infrastruktura'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kategoria utworzona'),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $category = new Category();
        $category->setName($data['name'] ?? '');
        $category->setDescription($data['description'] ?? null);
        $category->setOrganization($user->getOrganization());

        $em->persist($category);
        $em->flush();

        return $this->json(
            $category,
            201,
            [],
            ['groups' => ['category:read']],
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Szczegóły kategorii',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Szczegóły kategorii'),
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        return $this->json(
            $category,
            200,
            [],
            ['groups' => ['category:read']],
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Aktualizuj kategorię',
        description: 'Wymaga ROLE_ADMIN',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Kategoria zaktualizowana'),
        ]
    )]
    public function update(Category $category, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $category->setDescription($data['description']);
        }

        $em->flush();

        return $this->json(
            $category,
            200,
            [],
            ['groups' => ['category:read']],
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń kategorię',
        description: 'Wymaga ROLE_ADMIN',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kategoria usunięta'),
        ]
    )]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($category);
        $em->flush();

        return $this->json(null, 204);
    }
}
