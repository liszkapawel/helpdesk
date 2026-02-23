<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class AuthControllerTest extends ApiTestCase
{

    public function testLoginSuccess(): void
    {
        $this->jsonRequest('POST', '/api/login', [
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->jsonRequest('POST', '/api/login', [
            'email' => self::ADMIN_EMAIL,
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseStatusCode(401);
    }

    public function testLoginMissingFields(): void
    {
        $this->jsonRequest('POST', '/api/login', []);

        $this->assertResponseStatusCode(400);
    }
    public function testMeAuthenticated(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame(self::ADMIN_EMAIL, $data['email']);
        $this->assertArrayHasKey('organization', $data);
        $this->assertSame('TechVision', $data['organization']['name']);
    }

    public function testMeUnauthenticated(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCode(401);
    }
    public function testRegisterWithNewOrganization(): void
    {
        $unique = substr(md5(uniqid()), 0, 8);
        $this->jsonRequest('POST', '/api/register', [
            'email' => "new.user.{$unique}@newcompany.pl",
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'organizationName' => "New Company {$unique}",
            'organizationSlug' => "new-company-{$unique}",
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame("new.user.{$unique}@newcompany.pl", $data['email']);
        $this->assertSame("New Company {$unique}", $data['organization']['name']);
        $this->assertContains('ROLE_ADMIN', $data['roles']);
    }

    public function testRegisterMissingInviteAndOrg(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
        ]);

        $this->assertResponseStatusCode(400);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
    }

    public function testRegisterInvalidInviteCode(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'inviteCode' => 'nonexistentcode123456789012',
        ]);

        $this->assertResponseStatusCode(400);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => self::ADMIN_EMAIL,
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'organizationName' => 'Some Org',
        ]);

        $this->assertResponseStatusCode(422);
    }

    public function testRegisterDuplicateSlug(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'unique@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'organizationName' => 'Some Org',
            'organizationSlug' => 'techvision',
        ]);

        $this->assertResponseStatusCode(422);
    }
}
