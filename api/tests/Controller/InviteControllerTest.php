<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class InviteControllerTest extends ApiTestCase
{

    public function testListInvites(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/invites');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function testListInvitesForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->client->request('GET', '/api/invites');

        $this->assertResponseStatusCode(403);
    }

    public function testListInvitesForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/invites');

        $this->assertResponseStatusCode(403);
    }

    public function testListInvitesUnauthenticated(): void
    {
        $this->client->request('GET', '/api/invites');

        $this->assertResponseStatusCode(401);
    }
    public function testCreateInvite(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/invites', [
            'email' => 'invited.user@techvision.pl',
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('code', $data);
        $this->assertSame('invited.user@techvision.pl', $data['email']);
        $this->assertArrayHasKey('expiresAt', $data);
    }

    public function testCreateInviteWithoutEmail(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/invites', []);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('code', $data);
    }

    public function testCreateInviteForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('POST', '/api/invites', [
            'email' => 'test@test.com',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testDeleteInvite(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest('POST', '/api/invites', [
            'email' => 'to-delete@techvision.pl',
        ]);
        $inviteId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/invites/' . $inviteId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteInviteCrossOrganization(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/invites');
        $invites = $this->getResponseData();
        $inviteId = $invites[0]['id'];

        $this->loginAsOtherOrgAdmin();
        $this->client->request('DELETE', '/api/invites/' . $inviteId);

        $this->assertResponseStatusCode(404);
    }

    public function testDeleteInviteForbiddenForUser(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/invites');
        $invites = $this->getResponseData();
        $inviteId = $invites[0]['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/invites/' . $inviteId);

        $this->assertResponseStatusCode(403);
    }
    public function testValidateInviteCode(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/invites', [
            'email' => 'validate-test@techvision.pl',
        ]);
        $code = $this->getResponseData()['code'];

        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');
        $this->client->request('GET', '/api/invites/' . $code . '/validate');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertTrue($data['valid']);
        $this->assertSame('TechVision', $data['organizationName']);
    }

    public function testValidateInvalidCode(): void
    {
        $this->client->request('GET', '/api/invites/nonexistentcode12345/validate');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertFalse($data['valid']);
    }
}
