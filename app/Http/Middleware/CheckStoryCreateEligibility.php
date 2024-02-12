<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoryCreateEligibility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $curTime = Carbon::now('Asia/Dhaka');

        // TODO: uncomment this after one week

//        $officeStarts = Carbon::createFromTime(10); // 10 am
//        $officeEnds   = Carbon::createFromTime(18); // 6 pm
//
//        if (in_array($request->ip(), ['103.205.71.148', '2400:3240:900a:17::1008', '127.0.0.1']))
//        {
//            return response()->json([
//                'status' => false,
//                'error'  => 'Currently at office? Try to concentrate on your pending works.'
//            ], Response::HTTP_LOCKED);
//        }
//
//        if (!$curTime->clone()->isFriday() && $curTime->clone()->between($officeStarts, $officeEnds))
//        {
//            return response()->json([
//                'status' => false,
//                'error'  => "Aren't you supposed to be at office now?"
//            ], Response::HTTP_LOCKED);
//        }

        $storiesAddedToday = auth()->user()->stories()->whereDate('created_at', $curTime->clone()->format('Y-m-d'))->count();

        if ($storiesAddedToday >= 3)
        {
            return response()->json([
                'status' => false,
                'error'  => 'You cannot upload more than 3 stories in one day.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
