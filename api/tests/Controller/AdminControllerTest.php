<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class AdminControllerTest extends ApiTestCase
{

    public function testListUsers(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $this->assertGreaterThanOrEqual(5, count($data));
    }

    public function testListUsersForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCode(403);
    }

    public function testListUsersForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCode(403);
    }

    public function testListUsersUnauthenticated(): void
    {
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCode(401);
    }

    public function testListUsersOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(3, count($data));
    }
    public function testUpdateRole(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/users');
        $users = $this->getResponseData();

        $targetUser = null;
        foreach ($users as $user) {
            if ($user['email'] === self::USER_EMAIL) {
                $targetUser = $user;
                break;
            }
        }

        $this->assertNotNull($targetUser);

        $this->jsonRequest('PUT', '/api/admin/users/' . $targetUser['id'] . '/role', [
            'role' => 'ROLE_AGENT',
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertContains('ROLE_AGENT', $data['roles']);

        $this->jsonRequest('PUT', '/api/admin/users/' . $targetUser['id'] . '/role', [
            'role' => 'ROLE_USER',
        ]);
        $this->assertResponseStatusCode(200);
    }

    public function testUpdateRoleInvalidRole(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/users');
        $users = $this->getResponseData();
        $userId = $users[0]['id'];

        $this->jsonRequest('PUT', '/api/admin/users/' . $userId . '/role', [
            'role' => 'ROLE_SUPERADMIN',
        ]);

        $this->assertResponseStatusCode(400);
    }

    public function testUpdateRoleCrossOrganization(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/users');
        $users = $this->getResponseData();
        $tvUserId = $users[0]['id'];

        $this->loginAsOtherOrgAdmin();
        $this->jsonRequest('PUT', '/api/admin/users/' . $tvUserId . '/role', [
            'role' => 'ROLE_ADMIN',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testUpdateRoleForbiddenForAgent(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/users');
        $users = $this->getResponseData();
        $userId = $users[0]['id'];

        $this->loginAsAgent();
        $this->jsonRequest('PUT', '/api/admin/users/' . $userId . '/role', [
            'role' => 'ROLE_ADMIN',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testListAgents(): void
    {
        $this->loginAsAgent();
        $this->client->request('GET', '/api/admin/agents');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    public function testListAgentsForbiddenForAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/admin/agents');

        $this->assertResponseStatusCode(403);
    }

    public function testListAgentsForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/admin/agents');

        $this->assertResponseStatusCode(403);
    }
}
