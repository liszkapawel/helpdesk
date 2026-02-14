<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tickets')]
class TicketController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Request $request, TicketRepository $repo): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $total = $repo->count([]);
        $tickets = $repo->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

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
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $ticket = new Ticket();
        $ticket->setTitle($data['title'] ?? '');
        $ticket->setDescription($data['description'] ?? '');
        $ticket->setCreatedBy($this->getUser());

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

        return $this->json(
            $ticket,
            201,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read']],
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->json(
            $ticket,
            200,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read']],
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $ticket->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $ticket->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $status = TicketStatus::tryFrom($data['status']);
            if ($status) {
                $ticket->setStatus($status);
            }
        }
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

        $em->flush();

        return $this->json(
            $ticket,
            200,
            [],
            ['groups' => ['ticket:read', 'user:read', 'comment:read', 'category:read']],
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Ticket $ticket, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($ticket);
        $em->flush();

        return $this->json(null, 204);
    }
}
