<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class SatisfactionRatingControllerTest extends ApiTestCase
{
    private function getResolvedTicketId(): int
    {
        $this->loginAsAdmin();
        // Create a ticket as user
        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'Rating test ticket',
            'description' => 'Ticket for rating test',
        ]);
        $ticket = $this->getResponseData();
        $ticketId = $ticket['id'];

        // Resolve it as admin
        $this->loginAsAdmin();
        $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, ['status' => 'resolved']);

        return $ticketId;
    }

    public function testRateTicketSuccess(): void
    {
        $ticketId = $this->getResolvedTicketId();

        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', [
            'rating' => 5,
            'comment' => 'Świetna obsługa!',
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame(5, $data['rating']);
        $this->assertSame('Świetna obsługa!', $data['comment']);
    }

    public function testRateTicketDuplicate(): void
    {
        $ticketId = $this->getResolvedTicketId();

        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 4]);
        $this->assertResponseStatusCode(201);

        // Second rating should fail
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 3]);
        $this->assertResponseStatusCode(400);
    }

    public function testRateTicketWrongStatus(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets', [
            'title' => 'New ticket for rating',
            'description' => 'Still new, not resolved',
        ]);
        $ticket = $this->getResponseData();
        $ticketId = $ticket['id'];

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 4]);
        $this->assertResponseStatusCode(400);
        $data = $this->getResponseData();
        $this->assertStringContainsString('resolved', $data['error']);
    }

    public function testRateTicketWrongUser(): void
    {
        $ticketId = $this->getResolvedTicketId();

        // Admin tries to rate ticket owned by user
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 3]);
        $this->assertResponseStatusCode(403);
    }

    public function testRateTicketInvalidRating(): void
    {
        $ticketId = $this->getResolvedTicketId();

        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 6]);
        $this->assertResponseStatusCode(422);
    }

    public function testRatingAppearsInTicketResponse(): void
    {
        $ticketId = $this->getResolvedTicketId();

        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/rate', ['rating' => 4]);

        $this->client->request('GET', '/api/tickets/' . $ticketId);
        $data = $this->getResponseData();
        $this->assertNotNull($data['satisfactionRating']);
        $this->assertSame(4, $data['satisfactionRating']['rating']);
    }
}
