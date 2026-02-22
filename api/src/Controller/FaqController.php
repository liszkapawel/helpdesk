<?php

namespace App\Controller;

use App\Entity\FaqArticle;
use App\Entity\User;
use App\Repository\FaqArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/faq')]
class FaqController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(FaqArticleRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $articles = $repo->findByOrganization($user->getOrganization());

        return $this->json($articles, 200, [], ['groups' => ['faq:read']]);
    }

    #[Route('', methods: ['POST'])]
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
