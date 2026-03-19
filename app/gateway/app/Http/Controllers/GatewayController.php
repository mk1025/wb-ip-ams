<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    use ApiResponseTrait;

    public function forwardToAuth(Request $request, string $path = ''): Response|JsonResponse
    {
        $url = config('services.auth.url', 'http://localhost:8000').'/api';

        return $this->forward($url, 'auth/'.$path, $request);
    }

    public function forwardAuditToAuth(Request $request): Response|JsonResponse
    {
        return $this->forwardToAuth($request, 'audit-logs');
    }

    public function forwardToIp(Request $request, string $path = ''): Response|JsonResponse
    {
        $url = config('services.ip.url', 'http://localhost:8001').'/api';

        return $this->forward($url, 'ip-addresses/'.$path, $request);
    }

    public function forwardAuditToIp(Request $request): Response|JsonResponse
    {
        return $this->forwardToIp($request, 'audit-logs');
    }

    private function forward(string $baseUrl, string $path, Request $request): Response|JsonResponse
    {
        $url = $baseUrl.'/'.rtrim($path, '/');
        $method = strtolower($request->method());

        $httpRequest = Http::withoutRedirecting()
            ->withHeaders($this->getForwardHeaders($request))
            ->timeout(30);

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
            return $this->error('Service unavailable. Please try again later.', 503);
        }

        $headers = [
            'Content-Type' => $response->header('Content-Type') ?: 'application/json',
        ];

        if ($response->header('Set-Cookie')) {
            $headers['Set-Cookie'] = $response->header('Set-Cookie');
        }

        return response($response->body(), $response->status())
            ->withHeaders($headers);
    }

    /** @return array<string, string> */
    private function getForwardHeaders(Request $request): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Forwarded-Host' => $request->getHost(),
            'X-Forwarded-Proto' => $request->getScheme(),
            'X-Forwarded-Port' => (string) $request->getPort(),
            'X-Forwarded-For' => (string) $request->ip(),
        ];

        if ($request->hasHeader('Authorization')) {
            $headers['Authorization'] = (string) $request->header('Authorization');
        }

        if ($request->hasHeader('X-Requested-With')) {
            $headers['X-Requested-With'] = (string) $request->header('X-Requested-With');
        }

        if ($request->hasHeader('Cookie')) {
            $headers['Cookie'] = (string) $request->header('Cookie');
        }

        return $headers;
    }
}
