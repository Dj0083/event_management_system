<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackActivity
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->user()?->id;
        $response = $next($request);

        if ($userId && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $response->getStatusCode() < 400) {
            $route = $request->route();
            $subject = collect($route?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'action' => $route?->getName() ?: strtolower($request->method()).'_'.$request->path(),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'details' => json_encode(['method' => $request->method(), 'path' => $request->path()]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}
