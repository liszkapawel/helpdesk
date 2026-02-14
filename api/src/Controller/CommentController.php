<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tickets/{ticketId}/comments')]
class CommentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(int $ticketId, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        return $this->json(
            $ticket->getComments(),
            200,
            [],
            ['groups' => ['comment:read', 'user:read']],
        );
    }

    #[Route('', methods: ['POST'])]
    public function create(
        int $ticketId,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $comment = new Comment();
        $comment->setContent($data['content'] ?? '');
        $comment->setTicket($ticket);
        $comment->setAuthor($this->getUser());

        $errors = $validator->validate($comment);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($comment);
        $em->flush();

        return $this->json(
            $comment,
            201,
            [],
            ['groups' => ['comment:read', 'user:read']],
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $ticketId, Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        if ($comment->getTicket()->getId() !== $ticketId) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(null, 204);
    }
}
