<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentControllerTest extends ApiTestCase
{
    private function getFirstTicketId(): int
    {
        $this->client->request('GET', '/api/tickets');
        $data = $this->getResponseData();

        return $data['data'][0]['id'];
    }

    private function createTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($path, 'test file content');

        return $path;
    }
    public function testUploadAttachmentToTicket(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'test.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('test.txt', $data['originalName']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testUploadAttachmentNoFile(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId);

        $this->assertResponseStatusCode(400);
    }

    public function testUploadAttachmentTicketNotFound(): void
    {
        $this->loginAsAgent();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'test.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/99999', [], [
            'file' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(404);
    }

    public function testUploadAttachmentUnauthenticated(): void
    {
        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'test.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/1', [], [
            'file' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(401);
    }
    public function testUploadAttachmentToComment(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $this->jsonRequest('POST', '/api/tickets/' . $ticketId . '/comments', [
            'content' => 'Comment for attachment test',
        ]);
        $commentId = $this->getResponseData()['id'];

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'doc.pdf', 'application/pdf', null, true);

        $this->client->request('POST', '/api/attachments/comment/' . $commentId, [], [
            'file' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('doc.pdf', $data['originalName']);
    }
    public function testDownloadAttachment(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'download-test.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);
        $attachmentId = $this->getResponseData()['id'];

        $this->client->request('GET', '/api/attachments/' . $attachmentId . '/download');

        $this->assertResponseStatusCode(200);
    }

    public function testDownloadAttachmentOrgIsolation(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'secret.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);
        $attachmentId = $this->getResponseData()['id'];

        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/attachments/' . $attachmentId . '/download');

        $this->assertResponseStatusCode(403);
    }
    public function testDeleteAttachmentAsUploader(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'to-delete.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);
        $attachmentId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/attachments/' . $attachmentId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteAttachmentAsAdmin(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'admin-delete.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);
        $attachmentId = $this->getResponseData()['id'];

        $this->loginAsAdmin();
        $this->client->request('DELETE', '/api/attachments/' . $attachmentId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteAttachmentForbiddenForOtherUser(): void
    {
        $this->loginAsAgent();
        $ticketId = $this->getFirstTicketId();

        $tmpFile = $this->createTempFile();
        $uploadedFile = new UploadedFile($tmpFile, 'not-yours.txt', 'text/plain', null, true);

        $this->client->request('POST', '/api/attachments/ticket/' . $ticketId, [], [
            'file' => $uploadedFile,
        ]);
        $attachmentId = $this->getResponseData()['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/attachments/' . $attachmentId);

        $this->assertResponseStatusCode(403);
    }
}
