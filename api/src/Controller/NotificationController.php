<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
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
