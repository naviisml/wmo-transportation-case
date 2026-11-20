<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditApiAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->user()) {
            DB::table('audit_entries')->insert(['user_id' => $request->user()->id, 'action' => $request->method().' '.$request->route()->uri().' '.$response->getStatusCode()]);
        }

        return $response;
    }
}
