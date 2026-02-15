<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[OA\Tag(name: 'Organizacja')]
#[Route('/api/organization')]
class OrganizationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Dane organizacji',
        description: 'Zwraca organizację zalogowanego użytkownika',
        responses: [
            new OA\Response(response: 200, description: 'Dane organizacji'),
        ]
    )]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getOrganization(), 200, [], ['groups' => ['organization:read']]);
    }

    #[Route('', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Aktualizuj organizację',
        description: 'Wymaga ROLE_ADMIN',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'primaryColor', type: 'string', nullable: true, example: '#3B82F6'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Organizacja zaktualizowana'),
        ]
    )]
    public function update(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $org = $user->getOrganization();
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $org->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $org->setDescription($data['description']);
        }
        if (array_key_exists('primaryColor', $data)) {
            $org->setPrimaryColor($data['primaryColor']);
        }

        $em->flush();

        return $this->json($org, 200, [], ['groups' => ['organization:read']]);
    }

    #[Route('/logo', methods: ['POST'])]
    #[OA\Post(
        summary: 'Upload logo organizacji',
        description: 'Wymaga ROLE_ADMIN. Przesyłanie pliku multipart',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Logo zaktualizowane'),
            new OA\Response(response: 400, description: 'Brak pliku'),
        ]
    )]
    public function uploadLogo(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User $user */
        $user = $this->getUser();
        $org = $user->getOrganization();

        $file = $request->files->get('logo');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
        $file->move($uploadDir, $newFilename);

        // Remove old logo if exists
        if ($org->getLogoPath()) {
            $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $org->getLogoPath();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $org->setLogoPath('/uploads/logos/' . $newFilename);
        $em->flush();

        return $this->json($org, 200, [], ['groups' => ['organization:read']]);
    }
}
