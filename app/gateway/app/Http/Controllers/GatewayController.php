<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function forwardToAuth(Request $request, string $path = '')
    {
        $url = config('services.auth.url', 'http://localhost:8000').'/api';
        // Reconstruct the full path with 'auth' prefix
        $fullPath = 'auth/'.$path;

        return $this->forward($url, $fullPath, $request);
    }

    public function forwardToIp(Request $request, string $path = '')
    {
        $url = config('services.ip.url', 'http://localhost:8001').'/api';
        // Reconstruct the full path with 'ip-addresses' prefix
        $fullPath = 'ip-addresses/'.$path;

        return $this->forward($url, $fullPath, $request);
    }

    private function forward(string $baseUrl, string $path, Request $request)
    {
        // Remove trailing slash from path if present
        $path = rtrim($path, '/');
        $url = $baseUrl.'/'.$path;
        $method = strtolower($request->method());

        $httpRequest = Http::withoutRedirecting()
            ->withHeaders($this->getForwardHeaders($request));

        $httpRequest = $httpRequest->timeout(30);

        try {
            $response = match ($method) {
                'get' => $httpRequest->get($url, $request->query()),
                'post' => $httpRequest->post($url, $request->all()),
                'put' => $httpRequest->put($url, $request->all()),
                'patch' => $httpRequest->patch($url, $request->all()),
                'delete' => $httpRequest->delete($url, $request->all()),
                default => $httpRequest->get($url),
            };
        } catch (ConnectionException) {
            return response()->json([
                'success' => false,
                'message' => 'Service unavailable. Please try again later.',
            ], 503);
        }

        return response($response->body(), $response->status())
            ->withHeaders([
                'Content-Type' => $response->header('Content-Type') ?? 'application/json',
            ]);
    }

    private function getForwardHeaders(Request $request): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($request->hasHeader('Authorization')) {
            $headers['Authorization'] = $request->header('Authorization');
        }

        if ($request->hasHeader('X-Requested-With')) {
            $headers['X-Requested-With'] = $request->header('X-Requested-With');
        }

        return $headers;
    }
}
