<?php

namespace Tests\Feature;

use App\Livewire\Logs\Index;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('logs.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_logs_monitoring_page(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->get(route('logs.index'));

        $response->assertOk();
        $response->assertSee('Monitoring Log Aktivitas');
    }

    public function test_non_developer_role_cannot_view_logs_monitoring_page(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get(route('logs.index'))->assertForbidden();
    }

    public function test_default_filter_is_today_and_shows_todays_logs(): void
    {
        $user = $this->createDeveloperUser();

        // Create log today
        ActivityLog::forceCreate([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE',
            'level' => 'info',
            'message' => 'Log dibuat hari ini',
            'created_at' => Carbon::now(),
        ]);

        // Create log from 10 days ago
        ActivityLog::forceCreate([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE',
            'level' => 'info',
            'message' => 'Log dibuat 10 hari lalu',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSet('activePreset', 'today')
            ->assertSet('startDate', Carbon::today()->format('Y-m-d'))
            ->assertSet('endDate', Carbon::today()->format('Y-m-d'))
            ->assertSee('Log dibuat hari ini')
            ->assertDontSee('Log dibuat 10 hari lalu');
    }

    public function test_date_preset_filter_shows_past_logs(): void
    {
        $user = $this->createDeveloperUser();

        ActivityLog::forceCreate([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE',
            'level' => 'info',
            'message' => 'Log dibuat 3 hari lalu',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setPreset', '7days')
            ->assertSee('Log dibuat 3 hari lalu');
    }

    public function test_level_filter_and_search_filter(): void
    {
        $user = $this->createDeveloperUser();

        ActivityLog::create([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE_FAILED',
            'level' => 'error',
            'message' => 'Terjadi error fatal pada database',
            'created_at' => Carbon::now(),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE',
            'level' => 'info',
            'message' => 'Post baru berhasil dibuat dengan sukses',
            'created_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterLevel', 'error')
            ->assertSee('Terjadi error fatal pada database')
            ->assertDontSee('Post baru berhasil dibuat dengan sukses')
            ->set('filterLevel', '')
            ->set('search', 'database')
            ->assertSee('Terjadi error fatal pada database')
            ->assertDontSee('Post baru berhasil dibuat dengan sukses');
    }

    public function test_show_detail_dispatches_modal_event(): void
    {
        $user = $this->createDeveloperUser();

        $log = ActivityLog::create([
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'UPDATE',
            'level' => 'info',
            'message' => 'Post updated',
            'context' => ['title' => 'Sample Context'],
            'created_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('showDetail', $log->id)
            ->assertSet('selectedLogId', $log->id)
            ->assertDispatched('open-modal', id: 'logDetailModal')
            ->assertSee('Sample Context');
    }

    public function test_standard_logging_masks_sensitive_data_and_captures_exceptions(): void
    {
        $logger = app(ActivityLogService::class);

        $exception = new \RuntimeException('Database connection lost');

        $log = $logger->error(
            action: 'PAYMENT_FAILED',
            module: 'payment',
            message: 'Payment gateway timeout',
            context: [
                'order_id' => 'ORD-1234',
                'password' => 'secret123',
                'token' => 'jwt.token.here',
                'email' => 'fahmi@example.com',
                'phone' => '081234567890',
            ],
            exception: $exception,
            durationMs: 450
        );

        $this->assertNotNull($log);
        $this->assertEquals('error', $log->level);
        $this->assertEquals('Payment gateway timeout', $log->message);
        $this->assertNotEmpty($log->request_id);

        $context = $log->context;
        $this->assertEquals('ORD-1234', $context['order_id']);
        $this->assertEquals('********', $context['password']);
        $this->assertEquals('********', $context['token']);
        $this->assertEquals('f****@example.com', $context['email']);
        $this->assertEquals('0812****7890', $context['phone']);
        $this->assertEquals(450, $context['duration_ms']);
        $this->assertArrayHasKey('error', $context);
        $this->assertEquals('RuntimeException', $context['error']['type']);
        $this->assertEquals('[redacted]', $context['error']['message']);
    }

    public function test_activity_log_level_badge_class_accessor(): void
    {
        $errorLog = new ActivityLog(['level' => 'error']);
        $this->assertEquals('badge badge-danger', $errorLog->level_badge_class);

        $warnLog = new ActivityLog(['level' => 'warning']);
        $this->assertEquals('badge badge-warning', $warnLog->level_badge_class);

        $debugLog = new ActivityLog(['level' => 'debug']);
        $this->assertEquals('badge badge-secondary', $debugLog->level_badge_class);

        $infoLog = new ActivityLog(['level' => 'info']);
        $this->assertEquals('badge badge-info', $infoLog->level_badge_class);
    }
}
