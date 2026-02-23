<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class FaqControllerTest extends ApiTestCase
{

    public function testListFaqArticles(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/faq');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, count($data));

        foreach ($data as $article) {
            $this->assertArrayHasKey('title', $article);
            $this->assertArrayHasKey('content', $article);
        }
    }

    public function testListFaqArticlesAsUser(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/api/faq');

        $this->assertResponseStatusCode(200);
    }

    public function testListFaqArticlesUnauthenticated(): void
    {
        $this->client->request('GET', '/api/faq');

        $this->assertResponseStatusCode(401);
    }

    public function testListFaqArticlesOrganizationIsolation(): void
    {
        $this->loginAsOtherOrgAdmin();
        $this->client->request('GET', '/api/faq');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertGreaterThanOrEqual(2, count($data));
    }
    public function testCreateFaqArticle(): void
    {
        $this->loginAsAdmin();
        $this->jsonRequest('POST', '/api/faq', [
            'title' => 'Test FAQ',
            'content' => 'Test FAQ content',
            'position' => 10,
            'isPublished' => true,
        ]);

        $this->assertResponseStatusCode(201);
        $data = $this->getResponseData();
        $this->assertSame('Test FAQ', $data['title']);
        $this->assertSame('Test FAQ content', $data['content']);
        $this->assertSame(10, $data['position']);
        $this->assertTrue($data['isPublished']);
    }

    public function testCreateFaqArticleForbiddenForAgent(): void
    {
        $this->loginAsAgent();
        $this->jsonRequest('POST', '/api/faq', [
            'title' => 'Should Fail',
            'content' => 'Nope',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testCreateFaqArticleForbiddenForUser(): void
    {
        $this->loginAsUser();
        $this->jsonRequest('POST', '/api/faq', [
            'title' => 'Should Fail',
            'content' => 'Nope',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testUpdateFaqArticle(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest('POST', '/api/faq', [
            'title' => 'FAQ to Update',
            'content' => 'Original content',
        ]);
        $articleId = $this->getResponseData()['id'];

        $this->jsonRequest('PUT', '/api/faq/' . $articleId, [
            'title' => 'Updated FAQ Title',
            'content' => 'Updated content',
            'isPublished' => false,
        ]);

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertSame('Updated FAQ Title', $data['title']);
        $this->assertSame('Updated content', $data['content']);
    }

    public function testUpdateFaqArticleCrossOrganization(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/faq');
        $articles = $this->getResponseData();
        $articleId = $articles[0]['id'];

        $this->loginAsOtherOrgAdmin();
        $this->jsonRequest('PUT', '/api/faq/' . $articleId, [
            'title' => 'Hacked',
        ]);

        $this->assertResponseStatusCode(403);
    }

    public function testUpdateFaqArticleForbiddenForAgent(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/faq');
        $articles = $this->getResponseData();
        $articleId = $articles[0]['id'];

        $this->loginAsAgent();
        $this->jsonRequest('PUT', '/api/faq/' . $articleId, [
            'title' => 'Should Fail',
        ]);

        $this->assertResponseStatusCode(403);
    }
    public function testDeleteFaqArticle(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest('POST', '/api/faq', [
            'title' => 'FAQ to Delete',
            'content' => 'Will be deleted',
        ]);
        $articleId = $this->getResponseData()['id'];

        $this->client->request('DELETE', '/api/faq/' . $articleId);

        $this->assertResponseStatusCode(204);
    }

    public function testDeleteFaqArticleCrossOrganization(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/faq');
        $articles = $this->getResponseData();
        $articleId = $articles[0]['id'];

        $this->loginAsOtherOrgAdmin();
        $this->client->request('DELETE', '/api/faq/' . $articleId);

        $this->assertResponseStatusCode(403);
    }

    public function testDeleteFaqArticleForbiddenForUser(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/faq');
        $articles = $this->getResponseData();
        $articleId = $articles[0]['id'];

        $this->loginAsUser();
        $this->client->request('DELETE', '/api/faq/' . $articleId);

        $this->assertResponseStatusCode(403);
    }
}
