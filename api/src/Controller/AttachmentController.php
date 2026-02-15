<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Załączniki')]
#[Route('/api/attachments')]
class AttachmentController extends AbstractController
{
    private function getUploadDir(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/uploads';
    }

    #[Route('/ticket/{ticketId}', methods: ['POST'])]
    #[OA\Post(
        summary: 'Dodaj załącznik do ticketa',
        parameters: [
            new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Załącznik dodany'),
            new OA\Response(response: 400, description: 'Brak pliku'),
            new OA\Response(response: 404, description: 'Ticket nie znaleziony'),
        ]
    )]
    public function uploadForTicket(int $ticketId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        $filename = bin2hex(random_bytes(16)) . '.' . ($file->guessExtension() ?? 'bin');
        $uploadDir = $this->getUploadDir();

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        $attachment = new Attachment();
        $attachment->setFilename($filename);
        $attachment->setOriginalName($file->getClientOriginalName());
        $attachment->setMimeType($file->getClientMimeType());
        $attachment->setSize($file->getSize());
        $attachment->setUploadedBy($user);
        $attachment->setTicket($ticket);

        $em->persist($attachment);
        $em->flush();

        return $this->json($attachment, 201, [], ['groups' => ['attachment:read', 'user:read']]);
    }

    #[Route('/comment/{commentId}', methods: ['POST'])]
    #[OA\Post(
        summary: 'Dodaj załącznik do komentarza',
        parameters: [
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Załącznik dodany'),
            new OA\Response(response: 400, description: 'Brak pliku'),
            new OA\Response(response: 404, description: 'Komentarz nie znaleziony'),
        ]
    )]
    public function uploadForComment(int $commentId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $comment = $em->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        $filename = bin2hex(random_bytes(16)) . '.' . ($file->guessExtension() ?? 'bin');
        $uploadDir = $this->getUploadDir();

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        $attachment = new Attachment();
        $attachment->setFilename($filename);
        $attachment->setOriginalName($file->getClientOriginalName());
        $attachment->setMimeType($file->getClientMimeType());
        $attachment->setSize($file->getSize());
        $attachment->setUploadedBy($user);
        $attachment->setComment($comment);

        $em->persist($attachment);
        $em->flush();

        return $this->json($attachment, 201, [], ['groups' => ['attachment:read', 'user:read']]);
    }

    #[Route('/{id}/download', methods: ['GET'])]
    #[OA\Get(
        summary: 'Pobierz załącznik',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Plik do pobrania'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function download(Attachment $attachment): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('ATTACHMENT_VIEW', $attachment);

        $path = $this->getUploadDir() . '/' . $attachment->getFilename();

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $attachment->getOriginalName(),
        );

        return $response;
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń załącznik',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Załącznik usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień'),
        ]
    )]
    public function delete(Attachment $attachment, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ATTACHMENT_DELETE', $attachment);

        $path = $this->getUploadDir() . '/' . $attachment->getFilename();
        if (file_exists($path)) {
            unlink($path);
        }

        $em->remove($attachment);
        $em->flush();

        return $this->json(null, 204);
    }
}
