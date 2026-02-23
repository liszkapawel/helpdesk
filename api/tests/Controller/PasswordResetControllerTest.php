<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class PasswordResetControllerTest extends ApiTestCase
{
    public function testRequestWithValidEmail(): void
    {
        $this->jsonRequest('POST', '/api/password-reset/request', [
            'email' => self::USER_EMAIL,
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
    }

    public function testRequestWithInvalidEmail(): void
    {
        // Always returns 200 for security reasons
        $this->jsonRequest('POST', '/api/password-reset/request', [
            'email' => 'nonexistent@example.com',
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
    }

    public function testConfirmWithInvalidToken(): void
    {
        $this->jsonRequest('POST', '/api/password-reset/confirm', [
            'token' => 'invalid-token-xyz',
            'password' => 'newpassword123',
        ]);

        $this->assertResponseStatusCode(400);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
    }

    public function testConfirmWithShortPassword(): void
    {
        $this->jsonRequest('POST', '/api/password-reset/confirm', [
            'token' => 'sometoken',
            'password' => 'abc',
        ]);

        $this->assertResponseStatusCode(422);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
    }

    public function testFullResetFlow(): void
    {
        // Request reset
        $this->jsonRequest('POST', '/api/password-reset/request', [
            'email' => self::USER_EMAIL,
        ]);
        $this->assertResponseStatusCode(200);

        // Get token from database
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => self::USER_EMAIL]);
        $token = $user->getResetToken();

        $this->assertNotNull($token);

        // Confirm with valid token
        $this->jsonRequest('POST', '/api/password-reset/confirm', [
            'token' => $token,
            'password' => 'newpassword123',
        ]);

        $this->assertResponseStatusCode(200);

        // Verify token is cleared
        $em->clear();
        $freshUser = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => self::USER_EMAIL]);
        $this->assertNull($freshUser->getResetToken());

        // Verify new password works
        $this->createAuthenticatedClient(self::USER_EMAIL, 'newpassword123');

        // Restore original password for other tests
        $hasher = $container->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $freshUser->setPassword($hasher->hashPassword($freshUser, self::USER_PASSWORD));
        $em->flush();
    }
}
