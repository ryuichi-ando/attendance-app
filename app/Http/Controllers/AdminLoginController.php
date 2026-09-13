<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;


class AdminLoginController extends Controller
{
    /**
     * 管理者ログイン画面
     */
    public function create()
    {
        return view('admin.admin-login');
    }

    public function store(AdminLoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return back()
                ->withErrors([
                    'email' => 'メールアドレスまたはパスワードが正しくありません。',
                ])
                ->withInput();
        }

        $request->session()->regenerate();

        if (!Auth::user()->is_admin) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        return redirect()->route('admin.attendance.index');
    }

    /**
     * 管理者ログアウト
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
