<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalSecret
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.internal_secret');

        if (! $secret || ! hash_equals($secret, (string) ($request->header('X-Internal-Secret') ?? ''))) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
