<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUpdateDeleteEventEligibility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event = Event::find($request->route('id'));

        if ($event)
        {
            if ($event->event_status_id == 1)
            {
                return $next($request);
            }

            return response()->json([
                'status' => false,
                'error'  => 'Unable to make changes to event when it is locked.'
            ], Response::HTTP_FORBIDDEN);
        }
        return response()->json([
            'status' => false,
            'error'  => 'Invalid event detected.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
