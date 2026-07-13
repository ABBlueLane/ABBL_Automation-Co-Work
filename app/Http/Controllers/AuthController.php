<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(Request $request): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'redirectTo' => (string) $request->query('url', ''),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'red' => ['nullable', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = $this->findUserByLogin((string) $validated['login']);

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()
                ->withErrors(['login' => 'อีเมล เบอร์โทร หรือรหัสผ่านไม่ถูกต้อง'])
                ->withInput($request->only('login', 'red'));
        }

        if ($user->status !== 'active') {
            return back()
                ->withErrors(['login' => 'บัญชีนี้ถูกปิดใช้งาน'])
                ->withInput($request->only('login', 'red'));
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $redirectTo = $this->sanitizeRedirect((string) ($validated['red'] ?? ''));

        return redirect()->to($redirectTo ?: route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function findUserByLogin(string $login): ?User
    {
        $normalizedLogin = trim($login);
        $normalizedPhone = preg_replace('/\D+/', '', $normalizedLogin);

        return User::query()
            ->where(function ($query) use ($normalizedLogin, $normalizedPhone) {
                $query->whereRaw('LOWER(email) = ?', [Str::lower($normalizedLogin)]);

                if ($normalizedPhone !== '') {
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(phone_no, ''), '-', ''), ' ', ''), '+', '') = ?", [$normalizedPhone]);
                }
            })
            ->first();
    }

    private function sanitizeRedirect(string $redirect): string
    {
        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return '';
        }

        return $redirect;
    }
}
