<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Business;
use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Models\User;
use App\Services\Line\Ims\LineImsSettingsService;
use App\Services\Line\LineMessagingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class LineImsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $channelSecret = 'test-line-secret';

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.line.channel_secret', $this->channelSecret);
        config()->set('services.line.webhook_route_secret', null);
        config()->set('services.line.ims.default_business_id', $this->businessId);
        config()->set('services.line.ims.system_user_id', 1);
        config()->set('services.line.ims.auto_submit', true);
        config()->set('services.line.ims.reception_enabled', true);

        $this->seedBusiness();
        User::factory()->create(['id' => 1]);
    }

    public function test_guest_cannot_view_line_ims_settings(): void
    {
        $this->get(route('settings.line_ims.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_and_update_line_ims_settings(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('settings.line_ims.edit'))
            ->assertOk()
            ->assertSee('รับข้อความเพื่อสร้าง IMS')
            ->assertSee('เปิดรับข้อความเพื่อสร้าง IMS');

        $this->actingAs($user)
            ->put(route('settings.line_ims.update'), [])
            ->assertRedirect(route('settings.line_ims.edit'))
            ->assertSessionHas('success');

        $this->assertFalse(app(LineImsSettingsService::class)->receptionEnabled());
        $this->assertSame('0', AppSetting::query()->find(LineImsSettingsService::KEY_RECEPTION_ENABLED)?->value);

        $this->actingAs($user)
            ->put(route('settings.line_ims.update'), ['reception_enabled' => '1'])
            ->assertRedirect(route('settings.line_ims.edit'));

        $this->assertTrue(app(LineImsSettingsService::class)->receptionEnabled());
    }

    public function test_disabled_reception_ignores_start_command(): void
    {
        app(LineImsSettingsService::class)->save(['reception_enabled' => false]);

        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getGroupSummary')->andReturn(['groupName' => 'Test Group']);
            $mock->shouldReceive('notifyChat')->never();
            $mock->shouldReceive('replyText')->never();
            $mock->shouldReceive('pushText')->never();
        });

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-start-disabled',
                    'text' => '@ABBL Bot เริ่มเก็บข้อมูล',
                    'groupId' => 'group-ims-disabled',
                    'messageId' => 'message-start-disabled',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-disabled')->first();

        $this->assertNotNull($source);
        $this->assertFalse((bool) $source->is_collecting);
        $this->assertFalse((bool) ($source->form_state['awaiting_ims_confirmation'] ?? false));
        $this->assertSame(0, LineChatMessage::query()->count());
    }

    public function test_disabled_reception_still_allows_stop_while_collecting(): void
    {
        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getGroupSummary')->andReturn(['groupName' => 'Test Group']);
            $mock->shouldReceive('notifyChat')->andReturn(true);
            $mock->shouldReceive('replyText')->andReturn(true);
            $mock->shouldReceive('pushText')->andReturn(true);
        });

        $this->startCollecting('group-ims-stop-disabled');

        app(LineImsSettingsService::class)->save(['reception_enabled' => false]);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop-disabled',
                    'text' => '@ABBL Bot หยุดเก็บข้อมูล',
                    'groupId' => 'group-ims-stop-disabled',
                    'messageId' => 'message-stop-disabled',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-stop-disabled')->first();

        $this->assertFalse((bool) $source?->is_collecting);
        $this->assertNotNull($source?->stopped_at);
    }

    private function startCollecting(string $groupId): void
    {
        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => "event-start-{$groupId}",
                    'text' => '@ABBL Bot เริ่มเก็บข้อมูล',
                    'groupId' => $groupId,
                    'messageId' => "message-start-{$groupId}",
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => "event-confirm-{$groupId}",
                    'text' => 'สร้าง',
                    'groupId' => $groupId,
                    'messageId' => "message-confirm-{$groupId}",
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedWebhook(array $payload): TestResponse
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, $this->channelSecret, true));

        return $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LINE_SIGNATURE' => $signature,
        ], $rawBody);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function textEvent(array $overrides = []): array
    {
        return [
            'type' => 'message',
            'webhookEventId' => $overrides['webhookEventId'] ?? 'event-id',
            'replyToken' => $overrides['replyToken'] ?? 'reply-token',
            'timestamp' => $overrides['timestamp'] ?? 1783069200000,
            'source' => [
                'type' => 'group',
                'groupId' => $overrides['groupId'] ?? 'group-id',
                'userId' => $overrides['userId'] ?? 'user-id',
            ],
            'message' => [
                'type' => 'text',
                'id' => $overrides['messageId'] ?? 'message-id',
                'text' => $overrides['text'] ?? 'hello',
                'mention' => [
                    'mentionees' => [
                        ['isSelf' => $overrides['mentionsSelf'] ?? false],
                    ],
                ],
            ],
        ];
    }

    private function seedBusiness(): void
    {
        Business::unguarded(function (): void {
            Business::create([
                'id' => $this->businessId,
                'business_type' => 1,
                'business_vat_status' => 1,
                'business_branch_status' => 1,
                'business_branch_no' => 0,
                'business_branch_name' => 'สำนักงานใหญ่',
                'business_en_status' => 1,
                'business_name_en' => 'ABBL Automation Co-Work',
                'business_branch_no_en' => 0,
                'business_branch_name_en' => 'Head Office',
                'business_account_finance_year' => 12,
                'business_business_finance_year' => 12,
                'business_code' => 'ABBL',
                'business_name' => 'ABBL Automation Co-Work',
                'business_address1' => 'Bangkok',
                'business_status' => 1,
                'allow_issue' => true,
                'sales_target_amount' => 1000000.00,
            ]);
        });
    }
}
