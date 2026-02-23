<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class CategoryControllerTest extends ApiTestCase
{

    public function testListCategories(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $this->assertArrayHasKey('name', $data[0]);
    }

    public function testListCategoriesOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/categories');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    public function testListCategoriesUnauthenticated(): void
    {
        $this->client->request('GET', '/api/categories');

        $this->assertResponseStatusCode(401);
    }
    public function testCreateCategory(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/categories', [
            'name' => 'Test Category',
            'description' => 'A test category',
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('Test Category', $data['name']);
        $this->assertSame('A test category', $data['description']);
    }

    public function testCreateCategoryForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('POST', '/api/categories', [
            'name' => 'Should Fail',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testCreateCategoryForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/categories', [
            'name' => 'Should Fail',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testShowCategory(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();
        $categoryId = $categories[0]['id'];

        $this->client->request('GET', '/api/categories/' . $categoryId);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame($categoryId, $data['id']);
    }

    public function testShowCategoryNotFound(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories/99999');

        $this->assertResponseStatusCode(404);
    }
    public function testUpdateCategory(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest('POST', '/api/categories', [
            'name' => 'Category to Update',
        ]);
        $categoryId = $this->getResponseData()['id'];

        $this->jsonRequest('PUT', '/api/categories/' . $categoryId, [
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('Updated Category Name', $data['name']);
    }

    public function testUpdateCategoryForbiddenForAgent(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();
        $categoryId = $categories[0]['id'];

        $this->loginAsAgent();
        $this->jsonRequest('PUT', '/api/categories/' . $categoryId, [
            'name' => 'Should Fail',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testDeleteCategory(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest('POST', '/api/categories', [
            'name' => 'Category to Delete',
        ]);
        $categoryId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/categories/' . $categoryId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteCategoryForbiddenForUser(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/categories');
        $categories = $this->getResponseData();
        $categoryId = $categories[0]['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/categories/' . $categoryId);

        $this->assertResponseStatusCode(403);
    }
}
