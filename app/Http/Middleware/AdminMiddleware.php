<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập khu vực quản trị.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập với tài khoản Quản trị viên để truy cập.');
        }

        return $next($request);
    }
}
