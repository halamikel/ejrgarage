<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Chat Widget Module
 *
 * Exercises the full bot -> live-agent handoff across chat_handler.php,
 * get_chat_messages.php (customer side) and admin/get_chat_users.php,
 * admin/accept_session.php, admin/send_message.php, admin/end_session.php
 * (admin side), confirming both sides of the widget agree on the same
 * `ejr_chat` rows and `chat_session` state for a given user.
 *
 * Uses its own customer fixture (rather than the shared one) so that
 * ending its live session doesn't wipe chat history other tests may
 * depend on.
 */
class ChatWidgetIntegrationTest extends TestCase
{
    private ApiClient $api;
    private int $customerUserId;
    private string $customerJwt;
    private int $adminUserId;
    private string $adminJwt;

    protected function setUp(): void
    {
        $this->api = new ApiClient();

        $custEmail = 'integration_chat_customer_' . uniqid() . '@example.com';
        $this->customerUserId = TestDb::insertTestUser([
            'full_name' => 'Integration Chat Customer',
            'email'     => $custEmail,
            'role_id'   => 1,
        ]);
        $custLogin = $this->api->post('login.php', [
            'email'    => $custEmail,
            'password' => 'Password123!',
        ]);
        $this->assertSame('success', $custLogin['body']['status'] ?? null, $custLogin['raw']);
        $this->customerJwt = $custLogin['body']['token'];

        // Admin fixture — created directly, since register.php always
        // hardcodes role_id=1 and can't produce a role_id=2 account.
        $adminEmail = 'integration_admin_' . uniqid() . '@example.com';
        $this->adminUserId = TestDb::insertTestUser([
            'full_name' => 'Integration Test Admin',
            'email'     => $adminEmail,
            'role_id'   => 2,
        ]);
        $adminLogin = $this->api->post('login.php', [
            'email'    => $adminEmail,
            'password' => 'Password123!',
        ]);
        $this->assertSame('success', $adminLogin['body']['status'] ?? null, $adminLogin['raw']);
        $this->adminJwt = $adminLogin['body']['token'];
    }

    protected function tearDown(): void
    {
        TestDb::deleteChatMessagesByUserId($this->customerUserId);
        TestDb::deleteUserById($this->customerUserId);
        TestDb::deleteUserById($this->adminUserId);
    }

    public function test_bot_reply_is_logged_and_returned_through_get_chat_messages(): void
    {
        $reply = $this->api->post('chat_handler.php', [
            'message' => 'What are your business hours?',
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);

        $this->assertSame('success', $reply['body']['status'], $reply['raw']);
        $this->assertSame('bot', $reply['body']['mode']);
        $this->assertNotEmpty($reply['body']['reply']);

        $history = $this->api->get('get_chat_messages.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $history['body']['status']);
        $this->assertSame('bot', $history['body']['chat_session']);
    }

    public function test_live_chat_handoff_round_trips_between_customer_and_admin(): void
    {
        // 1. Customer requests a live agent.
        $request = $this->api->post('chat_handler.php', [
            'action' => 'request_live',
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $request['body']['status'], $request['raw']);
        $this->assertSame('waiting', $request['body']['mode']);

        // 2. Admin sees the waiting customer in the queue.
        $chatUsers = $this->api->get('admin/get_chat_users.php', ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $chatUsers['body']['status'], $chatUsers['raw']);
        $userIds = array_column($chatUsers['body']['users'], 'id');
        $this->assertContains($this->customerUserId, $userIds);

        // 3. Admin accepts the session.
        $accept = $this->api->post('admin/accept_session.php', [
            'user_id' => $this->customerUserId,
            'action'  => 'accept',
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $accept['body']['status'], $accept['raw']);

        // 4. Customer's next message is now routed to the live thread,
        //    not the bot.
        $liveMsg = 'Hi, I need help rescheduling my appointment (' . uniqid() . ')';
        $sendAsCustomer = $this->api->post('chat_handler.php', [
            'message' => $liveMsg,
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $sendAsCustomer['body']['status'], $sendAsCustomer['raw']);
        $this->assertSame('live', $sendAsCustomer['body']['mode']);

        // 5. Admin reads the thread and sees the customer's message.
        $adminView = $this->api->get(
            'admin/get_messages.php?user_id=' . $this->customerUserId,
            ['Authorization' => "Bearer {$this->adminJwt}"]
        );
        $this->assertSame('success', $adminView['body']['status'], $adminView['raw']);
        $this->assertSame('live', $adminView['body']['chat_session']);
        $texts = array_column($adminView['body']['messages'], 'message');
        $this->assertContains($liveMsg, $texts);

        // 6. Admin replies, and the customer sees it back.
        $adminReply = 'Sure — what date works best for you?';
        $sendAsAdmin = $this->api->post('admin/send_message.php', [
            'user_id' => $this->customerUserId,
            'message' => $adminReply,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $sendAsAdmin['body']['status'], $sendAsAdmin['raw']);

        $customerView = $this->api->get('get_chat_messages.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $customerView['body']['status']);
        $texts = array_column($customerView['body']['messages'], 'message');
        $this->assertContains($adminReply, $texts);

        // 7. Admin ends the session — chat clears and resets to bot mode.
        $end = $this->api->post('admin/end_session.php', [
            'user_id' => $this->customerUserId,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $end['body']['status'], $end['raw']);

        $afterEnd = $this->api->get('get_chat_messages.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $afterEnd['body']['status']);
        $this->assertSame('bot', $afterEnd['body']['chat_session']);
        $this->assertEmpty($afterEnd['body']['messages']);
    }

    public function test_non_admin_customer_is_forbidden_from_admin_chat_endpoints(): void
    {
        $chatUsers = $this->api->get('admin/get_chat_users.php', ['Authorization' => "Bearer {$this->customerJwt}"]);

        $this->assertSame('error', $chatUsers['body']['status']);
        $this->assertSame(403, $chatUsers['status']);
    }
}
