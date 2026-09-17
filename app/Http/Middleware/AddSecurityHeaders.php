<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $this->setIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setIfMissing($response, 'X-Frame-Options', 'DENY');
        $this->setIfMissing($response, 'Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->setIfMissing(
            $response,
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        if ($this->isHtml($response) && ! $response->headers->has('Content-Security-Policy')) {
            $policy = (string) config('security.headers.content_security_policy');

            if ($request->secure() && config('security.https.force', false)) {
                $policy .= '; upgrade-insecure-requests';
            }

            $response->headers->set('Content-Security-Policy', $policy);
        }

        return $response;
    }

    private function isHtml(Response $response): bool
    {
        return str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html');
    }

    private function setIfMissing(Response $response, string $name, string $value): void
    {
        if (! $response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }
}
