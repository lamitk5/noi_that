<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditMiddleware
{
    /**
     * Sensitive input keys that must never be recorded into audit logs.
     *
     * @var array<int, string>
     */
    protected array $hiddenFields = [
        'password',
        'password_confirmation',
        '_token',
        'token',
        'secret',
        'api_key',
        'card_number',
        'cvv',
        'vnp_SecureHash',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $user = $request->user();
            $action = $request->route()?->getName() ?: $request->path();
            $sanitizedPayload = $this->sanitizePayload($request->all());

            AdminAuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'method' => strtoupper($request->method()),
                'route' => $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'payload' => $sanitizedPayload,
            ]);
        }

        return $response;
    }

    /**
     * Sanitize input payload removing sensitive keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizePayload(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->hiddenFields, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } elseif ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = [
                    'original_name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'mime_type' => $value->getMimeType(),
                ];
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
