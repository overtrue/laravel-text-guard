<?php

namespace Overtrue\TextGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in route middleware: filter declared request fields and merge cleaned values.
 *
 * Register an alias (for example text-guard.merge) in the application; this
 * package does not register global middleware.
 *
 * Parameters: field:preset pairs, e.g. name:safe,bio:nickname.
 */
class MergeFilteredInput
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$pairs): Response
    {
        $map = [];

        foreach ($pairs as $pair) {
            if (! str_contains($pair, ':')) {
                throw new InvalidArgumentException("TextGuard middleware pair [{$pair}] must use field:preset syntax.");
            }

            [$field, $preset] = explode(':', $pair, 2);
            $map[$field] = $preset;
        }

        $request->mergeFiltered($map);

        return $next($request);
    }
}
