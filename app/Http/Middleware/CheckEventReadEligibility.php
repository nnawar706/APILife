<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEventReadEligibility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event = Event::find($request->route('id'));

        if ($event) {
            if (!$event->is_public && $event->addParticipants()->where('user_id', auth()->user()->id)->doesntExist()) {
                return response()->json([
                    'status' => false,
                    'error'  => 'You are not allowed to perform any action on protected data.'
                ], Response::HTTP_FORBIDDEN);
            }
            return $next($request);
        }
        return response()->json([
            'status' => false,
            'error'  => 'Invalid event detected.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
