<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class DashboardControllerTest extends ApiTestCase
{

    public function testDashboardStatsAsAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/dashboard/stats');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('byStatus', $data);
        $this->assertArrayHasKey('byPriority', $data);
        $this->assertArrayHasKey('overTime', $data);
        $this->assertArrayHasKey('avgResolutionHours', $data);
        $this->assertArrayHasKey('avgSatisfaction', $data);
        $this->assertArrayHasKey('perAgent', $data);
        $this->assertGreaterThan(0, $data['total']);
    }

    public function testDashboardStatsAsAgent(): void
    {
        $this->loginAsAgent();
        $this->client->request('GET', '/api/dashboard/stats');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('perAgent', $data);
    }

    public function testDashboardStatsAsUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/dashboard/stats');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayNotHasKey('perAgent', $data);
    }

    public function testDashboardStatsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/dashboard/stats');

        $this->assertResponseStatusCode(401);
    }

    public function testDashboardStatsOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/dashboard/stats');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame(3, $data['total']);
    }
}
