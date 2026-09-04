<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\Ai\AiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_ai_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.ai-settings.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.ai-settings.index'))->assertOk();
    }

    public function test_key_is_encrypted_masked_and_never_rendered(): void
    {
        $admin = $this->admin();
        $secret = 'gemini-super-secret-AB12';
        $token = 'test-csrf-token';
        $this->actingAs($admin)->withSession(['_token' => $token])
            ->put(route('admin.ai-settings.update'), $this->payload(['api_key' => $secret, '_token' => $token]))->assertRedirect();
        $setting = AiSetting::query()->firstOrFail();
        $this->assertSame($secret, $setting->api_key);
        $this->assertNotSame($secret, DB::table('ai_settings')->value('api_key'));
        $this->actingAs($admin)->get(route('admin.ai-settings.index'))->assertOk()->assertDontSee($secret)->assertSee('••••AB12', false);
    }

    public function test_blank_key_keeps_existing_key_and_prompt_changes_create_versions(): void
    {
        $admin = $this->admin();
        $service = app(AiSettingsService::class);
        $service->update($this->payload(['api_key' => 'existing-secret-AB12']), $admin);
        $service->update($this->payload(['api_key' => '', 'technical_prompt' => 'Novo prompt técnico']), $admin);
        $setting = AiSetting::query()->firstOrFail();
        $this->assertSame('existing-secret-AB12', $setting->api_key);
        $this->assertSame('technical_evaluator_v3', $setting->technical_prompt_version);
        $this->assertDatabaseCount('ai_setting_versions', 2);
    }

    public function test_connection_diagnostic_does_not_create_business_records_or_reveal_key(): void
    {
        $admin = $this->admin();
        app(AiSettingsService::class)->update($this->payload(['api_key' => 'diagnostic-secret-AB12']), $admin);
        Http::fake([
            '*/v1/configuration/test' => Http::response(['status' => 'ready'], 200),
        ]);
        $token = 'diagnostic-csrf-token';

        $response = $this->actingAs($admin)->withSession(['_token' => $token])
            ->post(route('admin.ai-settings.test'), ['_token' => $token]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('ai_executions', 0);
        $this->assertDatabaseCount('opinions', 0);
        $this->assertDatabaseCount('scores', 0);
        $this->assertDatabaseCount('indications', 0);
        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/v1/configuration/test')
                && $request['api_key'] === 'diagnostic-secret-AB12';
        });
        $this->assertStringNotContainsString('diagnostic-secret-AB12', (string) $response->getSession()->get('success'));
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'provider' => 'gemini', 'model' => 'gemini-test', 'technical_prompt' => 'Prompt técnico',
            'selection_prompt' => 'Prompt estratégico', 'evaluation_enabled' => true,
            'selection_enabled' => true, 'technical_evaluator_id' => 1, 'selection_evaluator_id' => 1,
            'connect_timeout' => 5, 'timeout' => 60, 'tries' => 3, 'knowledge_version' => null,
        ];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        return $user->fresh();
    }
}
