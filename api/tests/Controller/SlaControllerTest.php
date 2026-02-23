<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class SlaControllerTest extends ApiTestCase
{

    public function testListSlaPolicies(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/sla');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertSame(4, count($data));

        foreach ($data as $policy) {
            $this->assertArrayHasKey('priority', $policy);
            $this->assertArrayHasKey('responseHours', $policy);
            $this->assertArrayHasKey('resolutionHours', $policy);
        }
    }

    public function testListSlaPoliciesAsUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/sla');

        $this->assertResponseStatusCode(200);
    }

    public function testListSlaPoliciesUnauthenticated(): void
    {
        $this->client->request('GET', '/api/sla');

        $this->assertResponseStatusCode(401);
    }

    public function testListSlaPoliciesOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/sla');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame(0, count($data));
    }
    public function testUpsertSlaPolicies(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('PUT', '/api/sla', [
            ['priority' => 'critical', 'responseHours' => 1, 'resolutionHours' => 4],
            ['priority' => 'high', 'responseHours' => 4, 'resolutionHours' => 16],
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);

        $critical = null;
        foreach ($data as $policy) {
            if ($policy['priority'] === 'critical') {
                $critical = $policy;
                break;
            }
        }
        $this->assertNotNull($critical);
        $this->assertSame(1, $critical['responseHours']);
        $this->assertSame(4, $critical['resolutionHours']);

        $this->jsonRequest('PUT', '/api/sla', [
            ['priority' => 'critical', 'responseHours' => 2, 'resolutionHours' => 8],
            ['priority' => 'high', 'responseHours' => 8, 'resolutionHours' => 24],
        ]);
    }

    public function testUpsertSlaPoliciesForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('PUT', '/api/sla', [
            ['priority' => 'low', 'responseHours' => 1, 'resolutionHours' => 1],
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testUpsertSlaPoliciesForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('PUT', '/api/sla', [
            ['priority' => 'low', 'responseHours' => 1, 'resolutionHours' => 1],
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testUpsertSlaPoliciesInvalidData(): void
    {
        $this->loginAsAdmin();
        $this->client->request('PUT', '/api/sla', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '"not an array"');

        $this->assertResponseStatusCode(400);
    }
}
