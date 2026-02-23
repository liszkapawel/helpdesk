<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class CommentControllerTest extends ApiTestCase
{
    private function getFirstTicketId(): int
    {
        $this->client->request('GET', '/api/tickets');
        $data = $this->getResponseData();

        return $data['data'][0]['id'];
    }
    public function testListComments(): void
    {
        $this->loginAsAdmin();
        $ticketId = $this->getFirstTicketId();

        $this->client->request('GET', '/api/tickets/' . $ticketId . '/comments');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
    }

    public function testListCommentsTicketNotFound(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets/99999/comments');

        $this->assertResponseStatusCode(404);
    }

    public function testListCommentsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/tickets/1/comments');

        $this->assertResponseStatusCode(401);
    }
    public function testCreateComment(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => 'This is a test comment',
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('This is a test comment', $data['content']);
        $this->assertArrayHasKey('author', $data);
    }

    public function testCreateCommentValidationError(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => '',
        ]);

        $this->assertResponseStatusCode(422);
    }

    public function testCreateCommentTicketNotFound(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('POST', '/api/tickets/99999/comments', [
            'content' => 'Test comment',
        ]);

        $this->assertResponseStatusCode(404);
    }
    public function testDeleteCommentAsAdmin(): void
    {
        $this->loginAsAdmin();
        $ticketId = $this->getFirstTicketId();

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => 'Comment to be deleted',
        ]);
        $commentId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/tickets/' . $ticketId . '/comments/' . $commentId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteCommentAsOwner(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/tickets');
        $tickets = $this->getResponseData();
        $ticketId = $tickets['data'][0]['id'];

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => 'My own comment to delete',
        ]);
        $commentId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/tickets/' . $ticketId . '/comments/' . $commentId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteCommentForbiddenForOtherUser(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => 'Agent comment that user cannot delete',
        ]);
        $commentId = $this->getResponseData()['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/tickets/' . $ticketId . '/comments/' . $commentId);

        $this->assertResponseStatusCode(403);
    }
}
