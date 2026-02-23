<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Service\AuditService;
use App\Service\EmailService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Komentarze')]
#[Route('/api/tickets/{ticketId}/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
        private EmailService $emailService,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista komentarzy',
        parameters: [
            new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista komentarzy'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Dodaj komentarz',
        parameters: [
            new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'Sprawdziłem - problem rozwiązany'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Komentarz dodany'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
            new OA\Response(response: 422, description: 'Błędy walidacji'),
        ]
    )]
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

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $comment = new Comment();
        $comment->setContent($data['content'] ?? '');
        $comment->setTicket($ticket);
        $comment->setAuthor($user);

        $errors = $validator->validate($comment);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        // Set firstResponseAt when agent/admin comments for the first time
        if ($ticket->getFirstResponseAt() === null &&
            (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles()))) {
            $ticket->setFirstResponseAt(new \DateTimeImmutable());
        }

        $em->persist($comment);
        $em->flush();

        $this->audit->log('ticket', $ticketId, 'comment_added', [
            'commentId' => $comment->getId(),
            'author' => $user->getFirstName() . ' ' . $user->getLastName(),
        ], $user);
        $this->notifications->notifyTicketCommented($ticket, $user);
        try {
            $this->emailService->sendNewComment($ticket, $comment);
        } catch (\Throwable) {}
        $em->flush();

        return $this->json(
            $comment,
            201,
            [],
            ['groups' => ['comment:read', 'user:read']],
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń komentarz',
        parameters: [
            new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Komentarz usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
            new OA\Response(response: 404, description: 'Komentarz nie znaleziony'),
        ]
    )]
    public function delete(int $ticketId, Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        if ($comment->getTicket()->getId() !== $ticketId) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        $this->denyAccessUnlessGranted('COMMENT_DELETE', $comment);

        /** @var User $user */
        $user = $this->getUser();

        $this->audit->log('ticket', $ticketId, 'comment_deleted', [
            'commentId' => $comment->getId(),
        ], $user);

        $em->remove($comment);
        $em->flush();

        return $this->json(null, 204);
    }
}
