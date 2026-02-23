<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class NotificationControllerTest extends ApiTestCase
{

    public function testListNotifications(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/notifications');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('unreadCount', $data);
        $this->assertIsArray($data['data']);
    }

    public function testListNotificationsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/notifications');

        $this->assertResponseStatusCode(401);
    }
    public function testMarkNotificationAsRead(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets?assignedTo=unassigned');
        $tickets = $this->getResponseData();

        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];

            $this->client->request('GET', '/api/admin/users');
            $users = $this->getResponseData();
            $agentId = null;
            foreach ($users as $user) {
                if ($user['email'] === self::AGENT_EMAIL) {
                    $agentId = $user['id'];
                    break;
                }
            }

            $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
                'assignedTo' => $agentId,
            ]);
        }

        $this->loginAsAgent();
        $this->client->request('GET', '/api/notifications');
        $data = $this->getResponseData();

        if (count($data['data']) > 0) {
            $notificationId = $data['data'][0]['id'];

            $this->client->request('PUT', '/api/notifications/' . $notificationId . '/read');
            $this->assertResponseStatusCode(200);

            $responseData = $this->getResponseData();
            $this->assertTrue($responseData['isRead']);
        } else {
            $this->markTestSkipped('No notifications available');
        }
    }

    public function testMarkNotificationAsReadNotOwned(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/api/tickets');
        $tickets = $this->getResponseData();
        if ($tickets['meta']['total'] > 0) {
            $ticketId = $tickets['data'][0]['id'];
            $this->jsonRequest('PUT', '/api/tickets/' . $ticketId, [
                'status' => 'open',
            ]);
        }

        $this->loginAsAgent();
        $this->client->request('GET', '/api/notifications');
        $data = $this->getResponseData();

        if (count($data['data']) > 0) {
            $notificationId = $data['data'][0]['id'];

            $this->loginAsUser();
            $this->client->request('PUT', '/api/notifications/' . $notificationId . '/read');

            $this->assertResponseStatusCode(404);
        } else {
            $this->markTestSkipped('No notifications available');
        }
    }
    public function testMarkAllNotificationsAsRead(): void
    {
        $this->loginAsAgent();
        $this->client->request('PUT', '/api/notifications/read-all');

        $this->assertResponseStatusCode(200);
        $data = $this->getResponseData();
        $this->assertTrue($data['success']);
    }

    public function testMarkAllNotificationsAsReadUnauthenticated(): void
    {
        $this->client->request('PUT', '/api/notifications/read-all');

        $this->assertResponseStatusCode(401);
    }
}
