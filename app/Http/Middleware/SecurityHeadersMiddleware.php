<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $viteOrigins = $this->resolveViteDevOrigins();
        $viteHttp = $viteOrigins[0] ?? null;
        $viteWs = $viteOrigins[1] ?? null;

        $scriptSrc = "script-src 'self' 'unsafe-inline' 'unsafe-eval'" . ($viteHttp ? " {$viteHttp}" : '');
        $styleSrc = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net" . ($viteHttp ? " {$viteHttp}" : '');
        $fontSrc = "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net" . ($viteHttp ? " {$viteHttp}" : '');
        $imgSrc = "img-src 'self' data: https: blob:" . ($viteHttp ? " {$viteHttp}" : '');
        $connectSrc = "connect-src 'self'" . ($viteHttp && $viteWs ? " {$viteHttp} {$viteWs}" : '');

        $cspDirectives = [
            "default-src 'self'",
            $scriptSrc,
            $styleSrc,
            $fontSrc,
            $imgSrc,
            $connectSrc,
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Resolve the active Vite development server origin if hot reloading is active.
     *
     * @return array{0: string, 1: string}|null Returns [$httpOrigin, $wsOrigin] or null
     */
    protected function resolveViteDevOrigins(): ?array
    {
        if (app()->environment('production')) {
            return null;
        }

        $hotPath = public_path('hot');
        if (! is_file($hotPath)) {
            return null;
        }

        $hotContent = trim((string) @file_get_contents($hotPath));
        if ($hotContent === '') {
            return null;
        }

        $parsed = parse_url($hotContent);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = (string) $parsed['host'];
        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;

        // Disallow semicolons or whitespace to prevent header injection
        if (preg_match('/[;\s]/', $host) || ($port !== null && ($port < 1 || $port > 65535))) {
            return null;
        }

        $portPart = $port !== null ? ":{$port}" : '';
        $httpOrigin = "{$scheme}://{$host}{$portPart}";

        $wsScheme = $scheme === 'https' ? 'wss' : 'ws';
        $wsOrigin = "{$wsScheme}://{$host}{$portPart}";

        return [$httpOrigin, $wsOrigin];
    }
}
