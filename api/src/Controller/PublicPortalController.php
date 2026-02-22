<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Repository\FaqArticleRepository;
use App\Repository\OrganizationRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Portal publiczny')]
#[Route('/api/public')]
class PublicPortalController extends AbstractController
{
    #[Route('/org/{slug}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Informacje o organizacji',
        description: 'Publiczny endpoint - bez autoryzacji. Zwraca dane organizacji widoczne na portalu',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dane organizacji'),
            new OA\Response(response: 404, description: 'Organizacja nie znaleziona'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Zgłoś ticket (publiczny)',
        description: 'Publiczny endpoint - bez autoryzacji. Wymaga nagłówka X-Org-Slug',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'title', 'description'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jan Kowalski'),
                    new OA\Property(property: 'email', type: 'string', example: 'jan@example.com'),
                    new OA\Property(property: 'title', type: 'string', example: 'Problem z logowaniem'),
                    new OA\Property(property: 'description', type: 'string', example: 'Nie mogę się zalogować'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OA\Property(property: 'category', type: 'integer', description: 'ID kategorii'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ticket utworzony - zwraca ticketId i trackingToken'),
            new OA\Response(response: 400, description: 'Brak X-Org-Slug'),
            new OA\Response(response: 404, description: 'Organizacja nie znaleziona'),
            new OA\Response(response: 422, description: 'Brakujące pola'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Sprawdź status ticketa',
        description: 'Publiczny endpoint - bez autoryzacji. Wyszukuje ticket po email i ID',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'ticketId'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'jan@example.com'),
                    new OA\Property(property: 'ticketId', type: 'integer', example: 42),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status ticketa'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
            new OA\Response(response: 422, description: 'Brakujące pola'),
        ]
    )]
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

    #[Route('/tickets/{ticketId}/attachments', methods: ['POST'])]
    #[OA\Post(
        summary: 'Dodaj załącznik do ticketa (publiczny)',
        description: 'Publiczny endpoint - wymaga nagłówka X-Tracking-Token zamiast autoryzacji JWT',
        parameters: [
            new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tracking-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Załącznik dodany'),
            new OA\Response(response: 400, description: 'Brak pliku'),
            new OA\Response(response: 403, description: 'Nieprawidłowy tracking token'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
        ]
    )]
    public function uploadAttachment(int $ticketId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        // Verify by tracking token
        $token = $request->headers->get('X-Tracking-Token');
        if (!$token || $ticket->getTrackingToken() !== $token) {
            return $this->json(['error' => 'Invalid tracking token'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';
        $filename = bin2hex(random_bytes(16)) . '.' . ($file->guessExtension() ?? 'bin');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $size = $file->getSize();

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        $attachment = new Attachment();
        $attachment->setFilename($filename);
        $attachment->setOriginalName($originalName);
        $attachment->setMimeType($mimeType);
        $attachment->setSize($size);
        $attachment->setTicket($ticket);

        $em->persist($attachment);
        $em->flush();

        return $this->json($attachment, 201, [], ['groups' => ['attachment:read']]);
    }

    #[Route('/check-slug/{slug}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Sprawdź dostępność slug',
        description: 'Publiczny endpoint - bez autoryzacji',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dostępność slug'),
        ]
    )]
    public function checkSlug(string $slug, OrganizationRepository $repo): JsonResponse
    {
        $slug = strtolower(trim($slug));
        $valid = preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug) && strlen($slug) >= 3 && strlen($slug) <= 48;
        $available = $valid && !$repo->findOneBy(['slug' => $slug]);

        return $this->json(['available' => $available]);
    }

    #[Route('/org/{slug}/faq', methods: ['GET'])]
    #[OA\Get(
        summary: 'FAQ organizacji',
        description: 'Publiczny endpoint - bez autoryzacji. Zwraca opublikowane artykuły FAQ',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista artykułów FAQ'),
            new OA\Response(response: 404, description: 'Organizacja nie znaleziona'),
        ]
    )]
    public function orgFaq(string $slug, OrganizationRepository $orgRepo, FaqArticleRepository $faqRepo): JsonResponse
    {
        $org = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$org) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $articles = $faqRepo->findByOrganization($org, true);

        return $this->json($articles, 200, [], ['groups' => ['faq:read']]);
    }

    #[Route('/org/{slug}/categories', methods: ['GET'])]
    #[OA\Get(
        summary: 'Kategorie organizacji',
        description: 'Publiczny endpoint - bez autoryzacji',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista kategorii'),
            new OA\Response(response: 404, description: 'Organizacja nie znaleziona'),
        ]
    )]
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
