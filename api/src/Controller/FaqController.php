<?php

namespace App\Controller;

use App\Entity\FaqArticle;
use App\Entity\User;
use App\Repository\FaqArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'FAQ')]
#[Route('/api/faq')]
class FaqController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista artykułów FAQ',
        description: 'Zwraca artykuły FAQ dla organizacji zalogowanego użytkownika',
        responses: [
            new OA\Response(response: 200, description: 'Lista artykułów FAQ'),
        ]
    )]
    public function list(FaqArticleRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $articles = $repo->findByOrganization($user->getOrganization());

        return $this->json($articles, 200, [], ['groups' => ['faq:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Utwórz artykuł FAQ',
        description: 'Tylko dla administratorów',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Jak zresetować hasło?'),
                    new OA\Property(property: 'content', type: 'string', example: 'Aby zresetować hasło, kliknij...'),
                    new OA\Property(property: 'position', type: 'integer', example: 1),
                    new OA\Property(property: 'isPublished', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Artykuł utworzony'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $article = new FaqArticle();
        $article->setOrganization($user->getOrganization());
        $article->setTitle($data['title'] ?? '');
        $article->setContent($data['content'] ?? '');
        if (isset($data['position'])) {
            $article->setPosition((int) $data['position']);
        }
        if (isset($data['isPublished'])) {
            $article->setIsPublished((bool) $data['isPublished']);
        }

        $em->persist($article);
        $em->flush();

        return $this->json($article, 201, [], ['groups' => ['faq:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Edytuj artykuł FAQ',
        description: 'Tylko dla administratorów. Artykuł musi należeć do organizacji użytkownika',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'position', type: 'integer'),
                    new OA\Property(property: 'isPublished', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Artykuł zaktualizowany'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
            new OA\Response(response: 404, description: 'Artykuł nie znaleziony'),
        ]
    )]
    public function update(FaqArticle $article, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        if ($article->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }
        if (isset($data['position'])) {
            $article->setPosition((int) $data['position']);
        }
        if (isset($data['isPublished'])) {
            $article->setIsPublished((bool) $data['isPublished']);
        }

        $em->flush();

        return $this->json($article, 200, [], ['groups' => ['faq:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń artykuł FAQ',
        description: 'Tylko dla administratorów. Artykuł musi należeć do organizacji użytkownika',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Artykuł usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
            new OA\Response(response: 404, description: 'Artykuł nie znaleziony'),
        ]
    )]
    public function delete(FaqArticle $article, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        if ($article->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $em->remove($article);
        $em->flush();

        return $this->json(null, 204);
    }
}
