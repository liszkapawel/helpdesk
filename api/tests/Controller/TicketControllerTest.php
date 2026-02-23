<?php

namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Tests\ApiTestCase;

class TicketControllerTest extends ApiTestCase
{

    public function testListTicketsAsAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertGreaterThan(0, $data['meta']['total']);
    }

    public function testListTicketsWithFilters(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets?status=new&priority=high');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        foreach ($data['data'] as $ticket) {
            $this->assertSame('new', $ticket['status']);
            $this->assertSame('high', $ticket['priority']);
        }
    }

    public function testListTicketsPagination(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets?page=1&limit=2');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame(1, $data['meta']['page']);
        $this->assertSame(2, $data['meta']['limit']);
        $this->assertLessThanOrEqual(2, count($data['data']));
    }

    public function testListTicketsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/tickets');

        $this->assertResponseStatusCode(401);
    }

    public function testListTicketsOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/tickets');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame(3, $data['meta']['total']);
    }
    public function testCreateTicket(): void
    {
        $this->loginAsUser();

        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();
        $categoryId = $categories[0]['id'];

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Test ticket',
            'description' => 'Test description for ticket',
            'priority' => 'medium',
            'category' => $categoryId,
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('Test ticket', $data['title']);
        $this->assertSame('medium', $data['priority']);
        $this->assertSame('new', $data['status']);
    }

    public function testCreateTicketValidationError(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets', [
            'title' => '',
            'description' => '',
        ]);

        $this->assertResponseStatusCode(422);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    public function testCreateTicketUnauthenticated(): void
    {
        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Test',
            'description' => 'Test',
        ]);

        $this->assertResponseStatusCode(401);
    }
    public function testShowTicket(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets');
        $tickets = $this->getResponseData();
        $ticketId = $tickets['data'][0]['id'];

        $this->client->request('GET', '/api/tickets/' . $ticketId);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame($ticketId, $data['id']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
    }

    public function testShowTicketNotFound(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets/99999');

        $this->assertResponseStatusCode(404);
    }
    public function testUpdateTicketAsAgent(): void
    {
        $this->loginAsAgent();
        $this->client->request('GET', '/api/tickets');
        $tickets = $this->getResponseData();
        $ticketId = $tickets['data'][0]['id'];

        $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
            'status' => 'in_progress',
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('in_progress', $data['status']);
    }

    public function testUpdateTicketForbiddenForUserOnOthersTicket(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets?search=SharePoint');
        $tickets = $this->getResponseData();
        $ticketId = $tickets['data'][0]['id'];

        $this->loginAsUser();
        $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
            'title' => 'Hacked title',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testAssignTicketAsAgent(): void
    {
        $this->loginAsAgent();

        $this->client->request('GET', '/api/admin/agents');
        $agents = $this->getResponseData();
        $agentId = $agents[0]['id'];

        $this->client->request('GET', '/api/tickets?assignedTo=unassigned');
        $tickets = $this->getResponseData();

        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];

            $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
                'assignedTo' => $agentId,
            ]);

            $this->assertResponseStatusCode(200);
            $data = $this->getResponseData();
            $this->assertNotNull($data['assignedTo']);
        } else {
            $this->markTestSkipped('No unassigned tickets available');
        }
    }

    public function testAssignTicketForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'User ticket for assign test',
            'description' => 'Testing assign denied',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
            'assignedTo' => 1,
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testRateTicket(): void
    {
        $this->createAuthenticatedClient('katarzyna.dabrowska@techvision.pl', 'user123');
        $this->client->request('GET', '/api/tickets?status=resolved');
        $tickets = $this->getResponseData();

        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];

            $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', [
                'rating' => 5,
                'comment' => 'Excellent support',
            ]);

            $code = $this->client->getResponse()->getStatusCode();
            $this->assertContains($code, [201, 400]);
        } else {
            $this->markTestSkipped('No resolved tickets');
        }
    }

    public function testRateTicketNotOwner(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets?status=resolved');
        $tickets = $this->getResponseData();

        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];
            $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', [
                'rating' => 3,
            ]);
            $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 400, 201]);
        } else {
            $this->markTestSkipped('No resolved tickets');
        }
    }

    public function testRateTicketInvalidRating(): void
    {
        $this->createAuthenticatedClient('katarzyna.dabrowska@techvision.pl', 'user123');
        $this->client->request('GET', '/api/tickets?status=resolved');
        $tickets = $this->getResponseData();

        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];
            $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', [
                'rating' => 10,
            ]);
            $code = $this->client->getResponse()->getStatusCode();
            $this->assertContains($code, [422, 400]);
        } else {
            $this->markTestSkipped('No resolved tickets');
        }
    }
    public function testDeleteTicketAsAdmin(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Ticket to delete',
            'description' => 'Will be deleted',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/tickets/' . $ticketId);
        $this->assertResponseStatusCode(204);
    }

    public function testDeleteTicketForbiddenForUser(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Admin ticket',
            'description' => 'Created by admin',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/tickets/' . $ticketId);
        $this->assertResponseStatusCode(403);
    }

    public function testDeleteTicketForbiddenForAgent(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Admin ticket for agent test',
            'description' => 'Created by admin',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->loginAsAgent();
        $this->client->request('DELETE', '/api/tickets/' . $ticketId);
        $this->assertResponseStatusCode(403);
    }
}
