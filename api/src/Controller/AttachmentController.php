<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/attachments')]
class AttachmentController extends AbstractController
{
    private function getUploadDir(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/uploads';
    }

    #[Route('/ticket/{ticketId}', methods: ['POST'])]
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
