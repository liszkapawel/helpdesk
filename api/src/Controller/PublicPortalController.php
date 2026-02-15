<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Repository\OrganizationRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
class PublicPortalController extends AbstractController
{
    #[Route('/org/{slug}', methods: ['GET'])]
    public function orgInfo(string $slug, OrganizationRepository $repo): JsonResponse
    {
        $org = $repo->findOneBy(['slug' => $slug]);
        if (!$org) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        return $this->json([
            'id' => $org->getId(),
            'name' => $org->getName(),
            'slug' => $org->getSlug(),
            'description' => $org->getDescription(),
            'primaryColor' => $org->getPrimaryColor(),
            'logoPath' => $org->getLogoPath(),
        ]);
    }

    #[Route('/tickets', methods: ['POST'])]
    public function submitTicket(Request $request, OrganizationRepository $orgRepo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $slug = $request->headers->get('X-Org-Slug');
        if (!$slug) {
            return $this->json(['error' => 'Organization slug required'], 400);
        }

        $org = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$org) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');

        if (!$name || !$email || !$title || !$description) {
            return $this->json(['error' => 'All fields are required: name, email, title, description'], 422);
        }

        $ticket = new Ticket();
        $ticket->setTitle($title);
        $ticket->setDescription($description);
        $ticket->setSubmitterName($name);
        $ticket->setSubmitterEmail($email);
        $ticket->setOrganization($org);
        $ticket->setTrackingToken(bin2hex(random_bytes(16)));

        if (isset($data['priority'])) {
            $priority = TicketPriority::tryFrom($data['priority']);
            if ($priority) {
                $ticket->setPriority($priority);
            }
        }

        if (isset($data['category'])) {
            $category = $em->getRepository(\App\Entity\Category::class)->find($data['category']);
            if ($category && $category->getOrganization()?->getId() === $org->getId()) {
                $ticket->setCategory($category);
            }
        }

        $em->persist($ticket);
        $em->flush();

        return $this->json([
            'ticketId' => $ticket->getId(),
            'trackingToken' => $ticket->getTrackingToken(),
        ], 201);
    }

    #[Route('/tickets/track', methods: ['POST'])]
    public function trackTicket(Request $request, TicketRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $ticketId = (int) ($data['ticketId'] ?? 0);

        if (!$email || !$ticketId) {
            return $this->json(['error' => 'Email and ticket ID are required'], 422);
        }

        $ticket = $repo->findOneBy([
            'id' => $ticketId,
            'submitterEmail' => $email,
        ]);

        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        return $this->json([
            'id' => $ticket->getId(),
            'title' => $ticket->getTitle(),
            'status' => $ticket->getStatus()->value,
            'priority' => $ticket->getPriority()->value,
            'createdAt' => $ticket->getCreatedAt()->format('c'),
            'updatedAt' => $ticket->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/org/{slug}/categories', methods: ['GET'])]
    public function orgCategories(string $slug, OrganizationRepository $orgRepo): JsonResponse
    {
        $org = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$org) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        return $this->json(
            $org->getCategories()->toArray(),
            200,
            [],
            ['groups' => ['category:read']],
        );
    }
}
