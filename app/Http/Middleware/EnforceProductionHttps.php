<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class EnforceProductionHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.https.force', false)) {
            return $next($request);
        }

        if (! $request->secure()) {
            // A 308 redirect preserves the request method and body. The target
            // host comes from APP_URL rather than the user-controlled Host header.
            return redirect()->away($this->secureUrl($request), Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $response = $next($request);

        if (config('security.https.hsts.enabled', true)) {
            $header = 'max-age='.max(0, (int) config('security.https.hsts.max_age', 31536000));

            if (config('security.https.hsts.include_subdomains', false)) {
                $header .= '; includeSubDomains';
            }

            $response->headers->set('Strict-Transport-Security', $header);
        }

        return $response;
    }

    private function secureUrl(Request $request): string
    {
        $configuredUrl = parse_url((string) config('app.url'));
        $host = $configuredUrl['host'] ?? null;

        if (! is_string($host) || $host === '') {
            throw new RuntimeException('APP_URL must contain a valid host when HTTPS enforcement is enabled.');
        }

        $port = isset($configuredUrl['port']) && ! in_array((int) $configuredUrl['port'], [80, 443], true)
            ? ':'.(int) $configuredUrl['port']
            : '';

        return 'https://'.$host.$port.$request->getRequestUri();
    }
}
