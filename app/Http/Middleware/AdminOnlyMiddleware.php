<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Restrict a route to users with the admin role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Chức năng này chỉ dành cho Quản trị viên.',
                ], 403);
            }

            if (Auth::check()) {
                return redirect()->route('admin.chats.index')
                    ->with('error', 'Bạn không có quyền truy cập chức năng này. Chỉ Quản trị viên mới được sử dụng.');
            }

            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập với tài khoản Quản trị viên.');
        }

        return $next($request);
    }
}
