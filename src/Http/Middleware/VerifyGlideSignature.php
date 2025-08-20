<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureInterface;
use Symfony\Component\HttpFoundation\Response;

class VerifyGlideSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            App::make(SignatureInterface::class)
                ->validateRequest($request->path(), $request->toArray());
        } catch (SignatureException) {
            abort(404);
        }
        return $next($request);
    }
}
