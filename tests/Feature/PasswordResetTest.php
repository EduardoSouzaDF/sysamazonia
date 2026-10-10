<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_link_updates_password_and_displays_success_with_check(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.resetpost', $token), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ])->assertRedirect(route('login'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Senha alterada com sucesso.');

        $this->assertTrue(Hash::check('NovaSenha123!', $user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->get(route('login'))->assertOk()
            ->assertSee('Senha alterada com sucesso.')
            ->assertSee('kt-alert-success')
            ->assertSee('lucide-check');
    }

    public function test_invalid_or_expired_link_shows_portuguese_error_without_changing_password(): void
    {
        // The authentication page uses Portuguese even with a different server locale.
        app()->setLocale('fr');
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $token = Password::createToken($user);
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        foreach ([$token, 'invalid-token'] as $invalidToken) {
            $this->from(route('password.reset', $invalidToken, ['email' => $user->email]))
                ->post(route('password.resetpost', $invalidToken), [
                    'token' => $invalidToken,
                    'email' => $user->email,
                    'password' => 'NovaSenha123!',
                    'password_confirmation' => 'NovaSenha123!',
                ])->assertSessionHasErrors([
                    'email' => 'Este link de redefinição de senha é inválido ou expirou. Solicite um novo link.',
                ])->assertSessionMissing('status');

            $this->assertSame($originalPassword, $user->fresh()->password);
            $this->get(route('password.reset', ['token' => $invalidToken, 'email' => $user->email]))
                ->assertOk()->assertDontSee('passwords.token')
                ->assertSee('Este link de redefinição de senha é inválido ou expirou. Solicite um novo link.')
                ->assertDontSee('Senha alterada com sucesso.');
        }
    }

    public function test_repeated_submission_and_return_to_used_link_preserve_confirmed_success(): void
    {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ];

        $this->post(route('password.resetpost', $token), $payload)
            ->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $passwordHash = $user->fresh()->password;
        $this->get(route('login'))->assertSee('lucide-check');

        $this->post(route('password.resetpost', $token), $payload)
            ->assertRedirect(route('login'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Senha alterada com sucesso.');
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Senha alterada com sucesso.');
        $this->assertSame($passwordHash, $user->fresh()->password);
        Event::assertDispatchedTimes(PasswordReset::class, 1);

        // A different browser has no confirmation of the completed reset.
        $this->flushSession();
        $this->post(route('password.resetpost', $token), $payload)
            ->assertSessionHasErrors('email')->assertSessionMissing('status');
        $this->assertSame($passwordHash, $user->fresh()->password);
    }

    public function test_success_confirmation_does_not_apply_to_another_link_or_after_ten_minutes(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ];
        $this->post(route('password.resetpost', $token), $payload)->assertRedirect(route('login'));
        $this->get(route('login'));

        $this->post(route('password.resetpost', 'another-token'), array_replace($payload, ['token' => 'another-token']))
            ->assertSessionHasErrors('email')->assertSessionMissing('status');
        $this->travel(11)->minutes();
        $this->post(route('password.resetpost', $token), $payload)
            ->assertSessionHasErrors('email')->assertSessionMissing('status');
        $this->assertTrue(Hash::check('NovaSenha123!', $user->fresh()->password));
    }
}
