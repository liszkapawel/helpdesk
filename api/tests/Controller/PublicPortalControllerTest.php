<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PublicPortalControllerTest extends ApiTestCase
{

    public function testOrgInfo(): void
    {
        $this->client->request('GET', '/api/public/org/techvision');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('TechVision', $data['name']);
        $this->assertSame('techvision', $data['slug']);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('primaryColor', $data);
    }

    public function testOrgInfoNotFound(): void
    {
        $this->client->request('GET', '/api/public/org/nonexistent-org');

        $this->assertResponseStatusCode(404);
    }
    public function testSubmitPublicTicket(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'External User',
            'email' => 'external@example.com',
            'title' => 'Public ticket test',
            'description' => 'Submitted from public portal',
            'priority' => 'medium',
        ], ['X-Org-Slug' => 'techvision']);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('ticketId', $data);
        $this->assertArrayHasKey('trackingToken', $data);
        $this->assertNotEmpty($data['trackingToken']);
    }

    public function testSubmitPublicTicketMissingOrgSlug(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'User',
            'email' => 'user@example.com',
            'title' => 'Test',
            'description' => 'Test',
        ]);

        $this->assertResponseStatusCode(400);
    }

    public function testSubmitPublicTicketInvalidOrg(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'User',
            'email' => 'user@example.com',
            'title' => 'Test',
            'description' => 'Test',
        ], ['X-Org-Slug' => 'nonexistent-org']);

        $this->assertResponseStatusCode(404);
    }

    public function testSubmitPublicTicketMissingFields(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'User',
            'email' => 'user@example.com',
        ], ['X-Org-Slug' => 'techvision']);

        $this->assertResponseStatusCode(422);
    }
    public function testTrackTicket(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'Tracker User',
            'email' => 'tracker@example.com',
            'title' => 'Trackable ticket',
            'description' => 'Will be tracked',
        ], ['X-Org-Slug' => 'techvision']);

        $ticketData = $this->getResponseData();

        $this->jsonRequest('POST', '/api/public/tickets/track', [
            'email' => 'tracker@example.com',
            'ticketId' => $ticketData['ticketId'],
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame($ticketData['ticketId'], $data['id']);
        $this->assertSame('Trackable ticket', $data['title']);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('priority', $data);
    }

    public function testTrackTicketNotFound(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets/track', [
            'email' => 'nonexistent@example.com',
            'ticketId' => 99999,
        ]);

        $this->assertResponseStatusCode(404);
    }

    public function testTrackTicketMissingFields(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets/track', [
            'email' => 'test@example.com',
        ]);

        $this->assertResponseStatusCode(422);
    }

    public function testTrackTicketWrongEmail(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'Real User',
            'email' => 'real@example.com',
            'title' => 'Real ticket',
            'description' => 'Test',
        ], ['X-Org-Slug' => 'techvision']);

        $ticketId = $this->getResponseData()['ticketId'];

        $this->jsonRequest('POST', '/api/public/tickets/track', [
            'email' => 'wrong@example.com',
            'ticketId' => $ticketId,
        ]);

        $this->assertResponseStatusCode(404);
    }
    public function testPublicUploadAttachment(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'Attach User',
            'email' => 'attach@example.com',
            'title' => 'Ticket with attachment',
            'description' => 'Need to attach file',
        ], ['X-Org-Slug' => 'techvision']);

        $ticketData = $this->getResponseData();
        $ticketId = $ticketData['ticketId'];
        $token = $ticketData['trackingToken'];

        $tmpFile = tempnam(sys_get_temp_dir(), 'pub_');
        file_put_contents($tmpFile, 'public attachment content');
        $uploadedFile = new UploadedFile($tmpFile, 'public-doc.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/public/tickets/' . $ticketId . '/attachments', [], [
            'file' => $uploadedFile,
        ], [
            'HTTP_X_TRACKING_TOKEN' => $token,
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('public-doc.txt', $data['originalName']);
    }

    public function testPublicUploadAttachmentInvalidToken(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'Token User',
            'email' => 'token@example.com',
            'title' => 'Token test ticket',
            'description' => 'Testing invalid token',
        ], ['X-Org-Slug' => 'techvision']);

        $ticketId = $this->getResponseData()['ticketId'];

        $tmpFile = tempnam(sys_get_temp_dir(), 'pub_');
        file_put_contents($tmpFile, 'content');
        $uploadedFile = new UploadedFile($tmpFile, 'file.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/public/tickets/' . $ticketId . '/attachments', [], [
            'file' => $uploadedFile,
        ], [
            'HTTP_X_TRACKING_TOKEN' => 'invalidtoken123456',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testPublicUploadAttachmentNoFile(): void
    {
        $this->jsonRequest('POST', '/api/public/tickets', [
            'name' => 'NoFile User',
            'email' => 'nofile@example.com',
            'title' => 'No file test',
            'description' => 'Testing no file',
        ], ['X-Org-Slug' => 'techvision']);

        $ticketData = $this->getResponseData();

        $this->client->request('POST', '/api/public/tickets/' . $ticketData['ticketId'] . '/attachments', [], [], [
            'HTTP_X_TRACKING_TOKEN' => $ticketData['trackingToken'],
        ]);

        $this->assertResponseStatusCode(400);
    }

    public function testPublicUploadAttachmentTicketNotFound(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pub_');
        file_put_contents($tmpFile, 'content');
        $uploadedFile = new UploadedFile($tmpFile, 'file.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/public/tickets/99999/attachments', [], [
            'file' => $uploadedFile,
        ], [
            'HTTP_X_TRACKING_TOKEN' => 'sometoken',
        ]);

        $this->assertResponseStatusCode(404);
    }
    public function testCheckSlugTaken(): void
    {
        $this->client->request('GET', '/api/public/check-slug/techvision');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertFalse($data['available']);
    }

    public function testCheckSlugAvailable(): void
    {
        $this->client->request('GET', '/api/public/check-slug/brand-new-org');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertTrue($data['available']);
    }

    public function testCheckSlugInvalid(): void
    {
        $this->client->request('GET', '/api/public/check-slug/ab');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertFalse($data['available']);
    }
    public function testOrgFaq(): void
    {
        $this->client->request('GET', '/api/public/org/techvision/faq');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    public function testOrgFaqNotFound(): void
    {
        $this->client->request('GET', '/api/public/org/nonexistent/faq');

        $this->assertResponseStatusCode(404);
    }
    public function testOrgCategories(): void
    {
        $this->client->request('GET', '/api/public/org/techvision/categories');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(4, count($data));
    }

    public function testOrgCategoriesNotFound(): void
    {
        $this->client->request('GET', '/api/public/org/nonexistent/categories');

        $this->assertResponseStatusCode(404);
    }
}
