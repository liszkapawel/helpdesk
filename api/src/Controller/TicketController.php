<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use App\Service\AuditService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Tickety')]
#[Route('/api/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lista ticketów',
        description: 'Zwraca paginowaną listę ticketów z opcjonalnym filtrowaniem i sortowaniem',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Szukaj w tytule, opisie, nazwie i emailu zgłaszającego'),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['new', 'open', 'in_progress', 'resolved', 'closed'])),
            new OA\Parameter(name: 'priority', in: 'query', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'critical'])),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'ID kategorii'),
            new OA\Parameter(name: 'assignedTo', in: 'query', schema: new OA\Schema(type: 'string'), description: 'ID agenta lub "unassigned"'),
            new OA\Parameter(name: 'dateFrom', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'dateTo', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['createdAt', 'title', 'status', 'priority'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista ticketów z metadanymi paginacji'),
        ]
    )]
    public function list(Request $request, TicketRepository $repo): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $filters = array_filter([
            'search' => $request->query->get('search'),
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'category' => $request->query->get('category'),
            'assignedTo' => $request->query->get('assignedTo'),
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo' => $request->query->get('dateTo'),
        ]);

        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order');

        /** @var User $user */
        $user = $this->getUser();
        $total = $repo->countByUserRole($user, $filters);
        $tickets = $repo->findByUserRole($user, $limit, $offset, $filters, $sortField, $sortOrder);

        return $this->json([
            'data' => $tickets,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ], 200, [], ['groups' => ['ticket:list', 'user:read', 'category:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Utwórz ticket',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Problem z logowaniem'),
                    new OA\Property(property: 'description', type: 'string', example: 'Nie mogę się zalogować do systemu'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OA\Property(property: 'category', type: 'integer', description: 'ID kategorii'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ticket utworzony'),
            new OA\Response(response: 422, description: 'Błędy walidacji'),
        ]
    )]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        $ticket = new Ticket();
        $ticket->setTitle($data['title'] ?? '');
        $ticket->setDescription($data['description'] ?? '');
        $ticket->setCreatedBy($user);
        $ticket->setOrganization($user->getOrganization());

        if (isset($data['priority'])) {
            $priority = TicketPriority::tryFrom($data['priority']);
            if ($priority) {
                $ticket->setPriority($priority);
            }
        }

        if (isset($data['category'])) {
            $category = $em->getRepository(\App\Entity\Category::class)->find($data['category']);
            if ($category) {
                $ticket->setCategory($category);
            }
        }

        $errors = $validator->validate($ticket);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($ticket);
        $em->flush();

        $this->audit->log('ticket', $ticket->getId(), 'created', [
            'title' => $ticket->getTitle(),
            'priority' => $ticket->getPriority()->value,
        ], $user);
        $em->flush();

        return $this->json(
            $ticket,
            201,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read', 'attachment:read']],
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Szczegóły ticketa',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Szczegóły ticketa z komentarzami i załącznikami'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
        ]
    )]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->json(
            $ticket,
            200,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read', 'attachment:read']],
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Aktualizuj ticket',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'status', type: 'string', enum: ['new', 'open', 'in_progress', 'resolved', 'closed']),
                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                new OA\Property(property: 'category', type: 'integer', description: 'ID kategorii'),
                new OA\Property(property: 'assignedTo', type: 'integer', nullable: true, description: 'ID agenta'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ticket zaktualizowany'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
            new OA\Response(response: 422, description: 'Błędy walidacji'),
        ]
    )]
    public function update(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('TICKET_EDIT', $ticket);

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $changes = [];
        $oldStatus = $ticket->getStatus()->value;
        $oldAssignedTo = $ticket->getAssignedTo();

        if (isset($data['title']) && $data['title'] !== $ticket->getTitle()) {
            $changes['title'] = ['from' => $ticket->getTitle(), 'to' => $data['title']];
            $ticket->setTitle($data['title']);
        }
        if (isset($data['description']) && $data['description'] !== $ticket->getDescription()) {
            $changes['description'] = ['from' => '(changed)', 'to' => '(changed)'];
            $ticket->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $status = TicketStatus::tryFrom($data['status']);
            if ($status && $status !== $ticket->getStatus()) {
                $changes['status'] = ['from' => $ticket->getStatus()->value, 'to' => $status->value];
                $ticket->setStatus($status);
                if ($status === TicketStatus::CLOSED || $status === TicketStatus::RESOLVED) {
                    $ticket->setClosedAt(new \DateTimeImmutable());
                } else {
                    $ticket->setClosedAt(null);
                }
            }
        }
        if (isset($data['priority'])) {
            $priority = TicketPriority::tryFrom($data['priority']);
            if ($priority && $priority !== $ticket->getPriority()) {
                $changes['priority'] = ['from' => $ticket->getPriority()->value, 'to' => $priority->value];
                $ticket->setPriority($priority);
            }
        }
        if (isset($data['category'])) {
            $category = $em->getRepository(\App\Entity\Category::class)->find($data['category']);
            if ($category) {
                $ticket->setCategory($category);
            }
        }
        if (array_key_exists('assignedTo', $data)) {
            $this->denyAccessUnlessGranted('TICKET_ASSIGN', $ticket);
            if ($data['assignedTo']) {
                $agent = $em->getRepository(User::class)->find($data['assignedTo']);
                if ($agent) {
                    $changes['assignedTo'] = [
                        'from' => $oldAssignedTo ? $oldAssignedTo->getFirstName() . ' ' . $oldAssignedTo->getLastName() : null,
                        'to' => $agent->getFirstName() . ' ' . $agent->getLastName(),
                    ];
                    $ticket->setAssignedTo($agent);
                }
            } else {
                if ($oldAssignedTo) {
                    $changes['assignedTo'] = [
                        'from' => $oldAssignedTo->getFirstName() . ' ' . $oldAssignedTo->getLastName(),
                        'to' => null,
                    ];
                }
                $ticket->setAssignedTo(null);
            }
        }

        $errors = $validator->validate($ticket);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        // Audit & notifications
        if (!empty($changes)) {
            $this->audit->log('ticket', $ticket->getId(), 'updated', $changes, $user);
        }
        if (isset($changes['status'])) {
            $this->notifications->notifyTicketStatusChanged($ticket, $oldStatus, $user);
        }
        if (isset($changes['assignedTo']) && $ticket->getAssignedTo()) {
            $this->notifications->notifyTicketAssigned($ticket, $ticket->getAssignedTo(), $user);
        }

        $em->flush();

        return $this->json(
            $ticket,
            200,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read', 'attachment:read']],
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń ticket',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Ticket usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function delete(Ticket $ticket, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('TICKET_DELETE', $ticket);

        $em->remove($ticket);
        $em->flush();

        return $this->json(null, 204);
    }
}
