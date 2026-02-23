<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrganizationControllerTest extends ApiTestCase
{

    public function testShowOrganization(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/organization');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('TechVision', $data['name']);
        $this->assertSame('techvision', $data['slug']);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('primaryColor', $data);
    }

    public function testShowOrganizationAsUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/organization');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('TechVision', $data['name']);
    }

    public function testShowOrganizationUnauthenticated(): void
    {
        $this->client->request('GET', '/api/organization');

        $this->assertResponseStatusCode(401);
    }
    public function testUpdateOrganization(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('PUT', '/api/organization', [
            'description' => 'Updated description',
            'primaryColor' => '#FF5733',
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('Updated description', $data['description']);
        $this->assertSame('#FF5733', $data['primaryColor']);

        $this->jsonRequest('PUT', '/api/organization', [
            'description' => 'Firma zajmująca się rozwiązaniami IT',
            'primaryColor' => '#3B82F6',
        ]);
    }

    public function testUpdateOrganizationForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('PUT', '/api/organization', [
            'name' => 'Hacked Name',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testUpdateOrganizationForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('PUT', '/api/organization', [
            'name' => 'Hacked Name',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testUploadLogo(): void
    {
        $this->loginAsAdmin();

        $tmpFile = tempnam(sys_get_temp_dir(), 'logo_');
        file_put_contents($tmpFile, 'fake image data for testing');

        $uploadedFile = new UploadedFile($tmpFile, 'logo.png', 'image/png', null, true);

        $this->client->request('POST', '/api/organization/logo', [], [
            'logo' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertStringContainsString('/uploads/logos/', $data['logoPath']);
    }

    public function testUploadLogoNoFile(): void
    {
        $this->loginAsAdmin();
        $this->client->request('POST', '/api/organization/logo');

        $this->assertResponseStatusCode(400);
    }

    public function testUploadLogoForbiddenForAgent(): void
    {
        $this->loginAsAgent();

        $tmpFile = tempnam(sys_get_temp_dir(), 'logo_');
        file_put_contents($tmpFile, 'fake image');
        $uploadedFile = new UploadedFile($tmpFile, 'logo.png', 'image/png', null, true);

        $this->client->request('POST', '/api/organization/logo', [], [
            'logo' => $uploadedFile,
        ]);

        $this->assertResponseStatusCode(403);
    }
}
