<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public static function profiles(): array
    {
        return [
            'administrador' => ['admin', false, false],
            'leitor' => ['leitor', false, false],
            'comissão' => ['comissao', false, false],
            'jurado' => ['jurado', false, false],
            'julgador' => [null, true, false],
            'organizador' => [null, false, true],
            'sem perfil' => [null, false, false],
        ];
    }

    #[DataProvider('profiles')]
    public function test_every_profile_can_access_home_and_is_redirected_there_after_login(?string $role, bool $judge, bool $organizer): void
    {
        $user = User::factory()->create(['is_judge' => $judge, 'is_organizer' => $organizer]);
        if ($role) {
            $user->roles()->attach(Role::firstOrCreate(['name' => $role], ['active' => true]));
        }

        $this->withSession(['url.intended' => route('dashboard'), '_token' => 'home-test-token'])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password', '_token' => 'home-test-token'])
            ->assertRedirect(route('home'))
            ->assertSessionMissing('url.intended');

        $this->assertAuthenticatedAs($user);
        $this->get(route('home'))->assertOk()
            ->assertSee($judge
                ? 'Boas-vindas ao Sistema de Julgamento do Prêmios.'
                : 'Boas-vindas ao Sistema de Inscrições, Avaliação e Julgamento do Prêmios.')
            ->assertSee($user->name)
            ->assertSee(asset('images/lg_Premios_alt.webp'))
            ->assertSee('Início');
        $this->get(route('login'))->assertRedirect(route('home'));

        if (! in_array($role, ['admin', 'leitor'], true)) {
            $this->get(route('dashboard'))->assertForbidden();
        }
    }

    public function test_guest_must_login_to_access_home(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_impersonation_and_return_to_admin_open_home(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'], ['active' => true]));
        $judge = User::factory()->judge()->create();

        $this->actingAs($admin)->get(route('admin.users.login-as', $judge))
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($judge);
        $this->get(route('home'))->assertOk();

        $this->get(route('admin.users.return-to-admin'))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }
}
