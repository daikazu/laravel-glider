<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Http\Middleware\VerifyGlideSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('passes the request to the next middleware when the signature is valid', function (): void {
    // Arrange: bind a mock SignatureInterface that validates successfully
    $mock = Mockery::mock(SignatureInterface::class);
    $mock->shouldReceive('validateRequest')
        ->once()
        ->with('images/test.jpg', [
            'w' => '100',
            'h' => '100',
            's' => 'valid-signature',
        ])
        ->andReturnNull();

    App::instance(SignatureInterface::class, $mock);

    $request = Request::create('/images/test.jpg', 'GET', [
        'w' => '100',
        'h' => '100',
        's' => 'valid-signature',
    ]);

    $middleware = new VerifyGlideSignature;

    // Act: run the middleware
    $response = $middleware->handle($request, function (Request $req): Response {
        return new Response('ok', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(200);
});

it('aborts with 404 when the signature is invalid', function (): void {
    // Arrange: bind a mock SignatureInterface that throws SignatureException
    $mock = Mockery::mock(SignatureInterface::class);
    $mock->shouldReceive('validateRequest')
        ->once()
        ->with('images/invalid.jpg', [
            'w' => '50',
            'h' => '50',
            's' => 'bad-signature',
        ])
        ->andThrow(new SignatureException('Invalid'));

    App::instance(SignatureInterface::class, $mock);

    $request = Request::create('/images/invalid.jpg', 'GET', [
        'w' => '50',
        'h' => '50',
        's' => 'bad-signature',
    ]);

    $middleware = new VerifyGlideSignature;

    // Act + Assert: abort(404) throws NotFoundHttpException
    expect(fn () => $middleware->handle($request, fn () => new Response('should not reach', 200)))
        ->toThrow(NotFoundHttpException::class);
});
