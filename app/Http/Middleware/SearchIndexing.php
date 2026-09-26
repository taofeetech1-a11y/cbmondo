<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $publicRoutes = [...array_keys(config('seo.pages')), 'seo.sitemap', 'seo.robots'];
        if (! $request->routeIs(...$publicRoutes) || $response->getStatusCode() !== 200) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
