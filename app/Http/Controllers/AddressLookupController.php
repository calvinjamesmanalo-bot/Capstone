<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressLookupController extends Controller
{
    private const BASE_URL = 'https://psgc.cloud/api/v2';

    public function provinces(): JsonResponse
    {
        $items = $this->fetch('/provinces');
        $items[] = ['code' => 'NCR', 'name' => 'Metro Manila (NCR)', 'region' => ['code' => '1300000000']];

        return response()->json($items);
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate(['province_code' => ['required', 'string', 'max:20', 'regex:/^(NCR|[0-9]{4,10})$/']]);
        $code = $validated['province_code'];
        $path = $code === 'NCR'
            ? '/regions/1300000000/cities-municipalities'
            : '/provinces/'.rawurlencode($code).'/cities-municipalities';

        return response()->json($this->fetch($path));
    }

    public function barangays(Request $request): JsonResponse
    {
        $validated = $request->validate(['city_code' => ['required', 'string', 'max:20', 'regex:/^[0-9]{4,10}$/']]);

        return response()->json($this->fetch('/cities-municipalities/'.rawurlencode($validated['city_code']).'/barangays'));
    }

    private function fetch(string $path): array
    {
        return Cache::remember('psgc:'.sha1($path), now()->addDay(), function () use ($path): array {
            $response = Http::acceptJson()->timeout(10)->retry(2, 200)->get(self::BASE_URL.$path);

            abort_unless($response->successful() && is_array($response->json()), 503, 'The Philippine address directory is temporarily unavailable.');

            return $response->json();
        });
    }
}
