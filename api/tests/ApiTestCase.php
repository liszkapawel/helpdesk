<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected const ADMIN_EMAIL = 'admin@techvision.pl';
    protected const ADMIN_PASSWORD = 'admin123';

    protected const AGENT_EMAIL = 'anna.nowak@techvision.pl';
    protected const AGENT_PASSWORD = 'agent123';

    protected const USER_EMAIL = 'jan.zielinski@techvision.pl';
    protected const USER_PASSWORD = 'user123';

    protected const OTHER_ORG_ADMIN_EMAIL = 'admin@medicare.pl';
    protected const OTHER_ORG_ADMIN_PASSWORD = 'admin123';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function createAuthenticatedClient(string $email, string $password): KernelBrowser
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $data['token']);

        return $this->client;
    }

    protected function loginAsAdmin(): KernelBrowser
    {
        return $this->createAuthenticatedClient(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);
    }

    protected function loginAsAgent(): KernelBrowser
    {
        return $this->createAuthenticatedClient(self::AGENT_EMAIL, self::AGENT_PASSWORD);
    }

    protected function loginAsUser(): KernelBrowser
    {
        return $this->createAuthenticatedClient(self::USER_EMAIL, self::USER_PASSWORD);
    }

    protected function loginAsOtherOrgAdmin(): KernelBrowser
    {
        return $this->createAuthenticatedClient(self::OTHER_ORG_ADMIN_EMAIL, self::OTHER_ORG_ADMIN_PASSWORD);
    }

    protected function jsonRequest(string $method, string $uri, array $data = [], array $headers = []): void
    {
        $serverHeaders = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $key => $value) {
            $serverHeaders['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
        }

        $this->client->request($method, $uri, [], [], $serverHeaders, $data ? json_encode($data) : null);
    }

    protected function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    protected function assertResponseStatusCode(int $expectedCode): void
    {
        $this->assertSame($expectedCode, $this->client->getResponse()->getStatusCode());
    }
}
