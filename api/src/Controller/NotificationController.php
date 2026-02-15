<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Powiadomienia')]
#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista powiadomień',
        description: 'Zwraca powiadomienia użytkownika z liczbą nieprzeczytanych',
        responses: [
            new OA\Response(response: 200, description: 'Lista powiadomień'),
        ]
    )]
    public function list(NotificationRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $repo->findForUser($user);
        $unreadCount = $repo->countUnread($user);

        return $this->json([
            'data' => $notifications,
            'unreadCount' => $unreadCount,
        ], 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/{id}/read', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Oznacz jako przeczytane',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Powiadomienie oznaczone'),
            new OA\Response(response: 404, description: 'Nie znaleziono'),
        ]
    )]
    public function markRead(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->json($notification, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/read-all', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Oznacz wszystkie jako przeczytane',
        responses: [
            new OA\Response(response: 200, description: 'Wszystkie oznaczone'),
        ]
    )]
    public function markAllRead(NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $repo->findBy(['user' => $user, 'isRead' => false]);
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }
        $em->flush();

        return $this->json(['success' => true]);
    }
}
