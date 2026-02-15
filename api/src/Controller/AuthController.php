<?php

namespace App\Controller;

use App\Entity\Invite;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Autoryzacja')]
class AuthController extends AbstractController
{
    #[Route('/api/login', methods: ['POST'])]
    #[OA\Post(
        summary: 'Logowanie',
        description: 'Zwraca token JWT',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'jan@firma.pl'),
                    new OA\Property(property: 'password', type: 'string', example: 'haslo123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token JWT', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'token', type: 'string')]
            )),
            new OA\Response(response: 401, description: 'Nieprawidłowe dane'),
        ]
    )]
    public function login(): void
    {
        // Handled by lexik/jwt-authentication-bundle
    }

    #[Route('/api/me', methods: ['GET'])]
    #[OA\Get(
        summary: 'Aktualny użytkownik',
        description: 'Zwraca dane zalogowanego użytkownika z organizacją',
        responses: [
            new OA\Response(response: 200, description: 'Dane użytkownika'),
            new OA\Response(response: 401, description: 'Brak autoryzacji'),
        ]
    )]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser(), 200, [], ['groups' => ['user:read', 'organization:read']]);
    }

    #[Route('/api/register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Rejestracja',
        description: 'Tworzy nowe konto. Wymaga inviteCode (dołączenie do org) lub organizationName (nowa org)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'jan@firma.pl'),
                    new OA\Property(property: 'password', type: 'string', example: 'haslo123'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jan'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Kowalski'),
                    new OA\Property(property: 'inviteCode', type: 'string', nullable: true),
                    new OA\Property(property: 'organizationName', type: 'string', nullable: true, example: 'Moja Firma'),
                    new OA\Property(property: 'organizationSlug', type: 'string', nullable: true, example: 'moja-firma', description: 'Nazwa skrótowa (subdomena)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Użytkownik utworzony'),
            new OA\Response(response: 400, description: 'Brak inviteCode/organizationName'),
            new OA\Response(response: 422, description: 'Błędy walidacji'),
        ]
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setPlainPassword($data['password'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');

        $inviteCode = $data['inviteCode'] ?? null;
        $organizationName = $data['organizationName'] ?? null;
        $organizationSlug = $data['organizationSlug'] ?? null;

        if ($inviteCode) {
            // Join existing organization via invite
            $invite = $em->getRepository(Invite::class)->findOneBy(['code' => $inviteCode]);
            if (!$invite || !$invite->isValid()) {
                return $this->json(['error' => 'Invalid or expired invite code'], 400);
            }
            $user->setOrganization($invite->getOrganization());
            $invite->setUsedBy($user);
        } elseif ($organizationName) {
            // Validate slug
            if ($organizationSlug) {
                $slug = strtolower(trim($organizationSlug));
                if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug) || strlen($slug) < 3 || strlen($slug) > 48) {
                    return $this->json(['error' => 'Nazwa skrótowa musi mieć 3-48 znaków i zawierać tylko małe litery, cyfry i myślniki'], 422);
                }
                $existing = $em->getRepository(Organization::class)->findOneBy(['slug' => $slug]);
                if ($existing) {
                    return $this->json(['error' => 'Ta nazwa skrótowa jest już zajęta'], 422);
                }
            } else {
                $slugger = new AsciiSlugger();
                $slug = strtolower($slugger->slug($organizationName)->toString());
                $existing = $em->getRepository(Organization::class)->findOneBy(['slug' => $slug]);
                if ($existing) {
                    $slug .= '-' . substr(bin2hex(random_bytes(4)), 0, 8);
                }
            }

            $org = new Organization();
            $org->setName($organizationName);
            $org->setSlug($slug);
            $em->persist($org);

            $user->setOrganization($org);
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            return $this->json(['error' => 'Either inviteCode or organizationName is required'], 400);
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->eraseCredentials();

        $em->persist($user);
        $em->flush();

        return $this->json(
            $user,
            201,
            [],
            ['groups' => ['user:read', 'organization:read']],
        );
    }
}
