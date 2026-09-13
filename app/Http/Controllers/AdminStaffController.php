<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧を表示
     */
    public function index()
    {
        // 管理者以外のユーザーを取得
        $users = User::whereNull('is_admin')
            ->orderBy('id')
            ->get();

        return view('admin.staff-list', compact('users'));
    }
}
