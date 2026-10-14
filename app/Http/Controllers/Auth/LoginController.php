<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        $user = $request->user();
        $allowed = Portal::isAdmin() ? $user->isAdmin() : $user->isCandidate();

        if (Portal::isSeparated() && ! $allowed) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $url = Portal::baseUrl($user->isAdmin() ? Portal::ADMIN : Portal::CANDIDATE);
            $message = $user->isAdmin()
                ? '管理者アカウントは管理画面からログインしてください: '.$url
                : '候補者アカウントは求人サイトからログインしてください: '.$url;

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
