<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/organization')]
class OrganizationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getOrganization(), 200, [], ['groups' => ['organization:read']]);
    }

    #[Route('', methods: ['PUT'])]
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
