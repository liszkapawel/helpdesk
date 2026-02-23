<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Autoryzacja')]
class PasswordResetController extends AbstractController
{
    #[Route('/api/password-reset/request', methods: ['POST'])]
    #[OA\Post(
        summary: 'Żądanie resetu hasła',
        description: 'Wysyła email z tokenem resetującym hasło. Zawsze zwraca 200 (bezpieczeństwo).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jan@firma.pl'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Żądanie przetworzone'),
        ]
    )]
    public function request(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        EmailService $emailService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $userRepo->findOneBy(['email' => $email]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
            $em->flush();

            try {
                $emailService->sendPasswordReset($email, $token);
            } catch (\Throwable) {}
        }

        return $this->json(['message' => 'Jeśli konto istnieje, wysłaliśmy email z instrukcjami.']);
    }

    #[Route('/api/password-reset/confirm', methods: ['POST'])]
    #[OA\Post(
        summary: 'Potwierdzenie resetu hasła',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'password'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Hasło zmienione'),
            new OA\Response(response: 400, description: 'Token nieprawidłowy lub wygasły'),
            new OA\Response(response: 422, description: 'Hasło zbyt krótkie'),
        ]
    )]
    public function confirm(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (strlen($password) < 6) {
            return $this->json(['error' => 'Hasło musi mieć co najmniej 6 znaków'], 422);
        }

        $user = $userRepo->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Token jest nieprawidłowy lub wygasł'], 400);
        }

        $hashed = $hasher->hashPassword($user, $password);
        $user->setPassword($hashed);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Hasło zostało zmienione. Możesz się teraz zalogować.']);
    }
}
