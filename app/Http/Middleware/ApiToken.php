<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) env('SCANNER_API_TOKEN');
        if ($expected === '' || !hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
