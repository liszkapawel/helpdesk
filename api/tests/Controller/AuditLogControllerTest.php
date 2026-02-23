<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class AuditLogControllerTest extends ApiTestCase
{

    public function testTicketHistory(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Ticket for audit test',
            'description' => 'Testing audit log',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->client->request('GET', '/api/tickets/' . $ticketId . '/history');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $this->assertSame('created', $data[0]['action']);
        $this->assertArrayHasKey('changes', $data[0]);
        $this->assertArrayHasKey('performedBy', $data[0]);
    }

    public function testTicketHistoryAfterUpdate(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();

        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Ticket for update audit',
            'description' => 'Will be updated',
            'category' => $categories[0]['id'],
        ]);
        $ticketId = $this->getResponseData()['id'];

        $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
            'status' => 'open',
            'priority' => 'high',
        ]);

        $this->client->request('GET', '/api/tickets/' . $ticketId . '/history');
        $data = $this->getResponseData();

        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function testTicketHistoryUnauthenticated(): void
    {
        $this->client->request('GET', '/api/tickets/1/history');

        $this->assertResponseStatusCode(401);
    }

    public function testTicketHistoryEmptyForNewTicket(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets/99999/history');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
