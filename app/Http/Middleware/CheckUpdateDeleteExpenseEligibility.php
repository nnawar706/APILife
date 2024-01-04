<?php

namespace App\Http\Middleware;

use App\Models\Expense;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUpdateDeleteExpenseEligibility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expense = Expense::find($request->route('id'));

        if ($expense) {
            if ($expense->event->event_status_id !== 4 && $expense->event->event_status_id !== 3) { // 4: completed, 3: approved
                return $next($request);
            } else {
                return response()->json([
                    'status' => false,
                    'error'  => 'Unable to make changes to expenses when the event is approved or completed.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'status' => false,
            'error'  => 'Invalid event detected'
        ], Response::HTTP_BAD_REQUEST);
    }
}
