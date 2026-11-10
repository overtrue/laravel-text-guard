<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Overtrue\TextGuard\Http\Middleware\MergeFilteredInput;
use Tests\TestCase;

class MergeFilteredInputMiddlewareTest extends TestCase
{
    public function test_middleware_only_merges_declared_fields(): void
    {
        $middleware = new MergeFilteredInput;
        $request = Request::create('/', 'POST', [
            'name' => '  Ann  ',
            'bio' => '  Long bio  ',
        ]);

        $response = $middleware->handle($request, fn () => new Response('ok'), 'name:safe');

        $this->assertSame('Ann', $request->input('name'));
        $this->assertSame('  Long bio  ', $request->input('bio'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_middleware_pair_syntax_is_validated(): void
    {
        $middleware = new MergeFilteredInput;
        $request = Request::create('/', 'POST', ['name' => 'x']);

        $this->expectException(\InvalidArgumentException::class);
        $middleware->handle($request, fn () => new Response, 'name');
    }

    public function test_middleware_alias_is_registered_for_opt_in_routes(): void
    {
        $aliases = $this->app['router']->getMiddleware();
        $this->assertArrayHasKey('text-guard.merge', $aliases);
        $this->assertSame(MergeFilteredInput::class, $aliases['text-guard.merge']);
    }
}
