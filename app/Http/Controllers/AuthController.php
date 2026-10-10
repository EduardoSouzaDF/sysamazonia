<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Mostrar formulário de login
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Processar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $request->session()->forget('url.intended');
            $request->session()->forget('commission_guides');

            return redirect()->route('home')->with('success', 'Login realizado com sucesso!');
        }
        // ed89b3b6c8676aea612ee68dd1d3316c

        return back()->withErrors([
            'login' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    // /**
    //  * Mostrar formulário de registro
    //  */
    // public function showRegister()
    // {
    //     return view('auth.register');
    // }

    // /**
    //  * Processar registro
    //  */
    // public function register(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users',
    //         'password' => ['required', 'confirmed', PasswordRule::min(8)],
    //     ]);

    //     $user = User::create([
    //         'name' => $validated['name'],
    //         'email' => $validated['email'],
    //         'password' => Hash::make($validated['password']),
    //     ]);

    //     Auth::login($user);
    //     $request->session()->regenerate();

    //     return redirect()->intended('dashboard')->with('success', 'Conta criada com sucesso!');
    // }

    /**
     * Mostrar formulário de recuperação de senha
     */
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    /**
     * Enviar link de reset de senha
     */
    public function sendReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Processar logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logout realizado com sucesso!');
    }

    public function showReset($token, Request $request)
    {
        if ($this->resetCompletedInSession($request, $token, (string) $request->email)) {
            return redirect()->route('login')->with('status', __('passwords.reset', [], 'pt_BR'));
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);
        if ($this->resetCompletedInSession($request, $request->string('token')->toString(), $request->string('email')->toString())) {
            return redirect()->route('login')->with('status', __('passwords.reset', [], 'pt_BR'));
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Remember only a fingerprint, never the password or the reset token.
            $request->session()->put('password_reset_completed', [
                'fingerprint' => hash('sha256', $request->input('email').'|'.$request->input('token')),
                'completed_at' => now()->timestamp,
            ]);

            return redirect()->route('login')->with('status', __('passwords.reset', [], 'pt_BR'));
        }

        return back()->withErrors(['email' => __($status, [], 'pt_BR')]);
    }

    private function resetCompletedInSession(Request $request, string $token, string $email): bool
    {
        $completed = $request->session()->get('password_reset_completed');

        return is_array($completed)
            && ($completed['completed_at'] ?? 0) >= now()->subMinutes(10)->timestamp
            && hash_equals($completed['fingerprint'] ?? '', hash('sha256', $email.'|'.$token));
    }
}
