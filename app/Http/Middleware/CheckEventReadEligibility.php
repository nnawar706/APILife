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
    public function handle(Request $request, Closure $next, $user_type): Response
    {
        // fetch event
        $event = Event::find($request->route('id'));

        if ($event) {
            // check if auth user is in the event participant list
            $argumentParticipant = $event->eventParticipants()->where('user_id', auth()->user()->id)
                ->doesntExist();

            // check if auth user is in the event guest list
            $argumentGuest = $event->eventGuests()->where('user_id', auth()->user()->id)
                ->doesntExist();

            /** if the event is private, user check type is participant but user not present in participant list
             * or user check type is all and not present in either guest or participant list, return error
             **/
            if (($user_type == 'participant' && $argumentParticipant) ||
                    ($user_type == 'all' && $argumentGuest && $argumentParticipant))
            {
                return response()->json([
                    'status' => false,
                    'error'  => 'You are not allowed to perform any action on protected extravaganza.'
                ], Response::HTTP_FORBIDDEN);
            }

            return $next($request);
        }

        // if event not found, return error
        return response()->json([
            'status' => false,
            'error'  => 'Invalid event detected.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
