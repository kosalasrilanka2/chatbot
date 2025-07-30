<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Update user's last activity
            User::where('id', Auth::id())->update(['updated_at' => now()]);
            
            // If user is also an agent, update agent's last_seen
            $agent = Agent::where('email', Auth::user()->email)->first();
            if ($agent) {
                $agent->update(['last_seen' => now()]);
            }
        }

        return $next($request);
    }
}
