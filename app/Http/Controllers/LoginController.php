<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('user.user-login');
    }

    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // メール認証済みなら勤怠画面へ
            if (Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('attendance.index');
            }

            // 未認証ならメール認証画面へ
            return redirect()->route('verification.notice');
        }

        return back()
            ->withErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ])
            ->withInput($request->only('email'));
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
