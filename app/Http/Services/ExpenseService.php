<?php

namespace App\Http\Services;

use App\Jobs\NotifyEventParticipants;
use App\Models\Expense;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    private $model;

    public function __construct(Expense $model)
    {
        $this->model = $model;
    }

    public function storeNewExpense(Request $request)
    {
        DB::beginTransaction();

        try {
            $expense = $this->model->create([
                'expense_category_id' => $request->expense_category_id,
                'event_id'            => $request->event_id,
                'title'               => $request->title,
                'unit_cost'           => $request->unit_cost,
                'quantity'            => $request->quantity,
                'remarks'             => $request->remarks ?? null
            ]);

            foreach ($request->bearers as $item)
            {
                $expense->bearers()->create([
                    'user_id'       => $item['user_id'],
                    'amount'        => $item['amount'],
                    'is_sponsored'  => $item['is_sponsored'],
                ]);
            }

            if ($request->payers) {
                foreach ($request->payers as $item) {
                    $expense->payers()->create([
                        'user_id' => $item['user_id'],
                        'amount' => $item['amount']
                    ]);
                }
            }

            DB::commit();

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function updateInfo(Request $request, $id)
    {
        $expense = $this->model->findOrFail($id);

        DB::beginTransaction();

        try {
            $expense->update([
                'expense_category_id' => $request->expense_category_id,
                'title'               => $request->title,
                'unit_cost'           => $request->unit_cost,
                'quantity'            => $request->quantity,
                'remarks'             => $request->remarks,
                'last_updated_by'     => auth()->user()->id
            ]);

            if ($request->has('bearers'))
            {
                $expense->bearers()->delete();

                foreach ($request->bearers as $item)
                {
                    $expense->bearers()->create([
                        'user_id'       => $item['user_id'],
                        'paid_by_id'    => $item['paid_by_id'] ?? null,
                        'amount'        => $item['amount'],
                        'is_sponsored'  => $item['is_sponsored'],
                    ]);
                }
            }

            if ($request->has('payers'))
            {
                $expense->payers()->delete();

                foreach ($request->payers as $item)
                {
                    $expense->payers()->create([
                        'user_id'  => $item['user_id'],
                        'amount'   => $item['amount']
                    ]);
                }
            }

            if ($expense->event->event_status_id == 2)
            {
                $expense->event->addParticipants()->update(['approval_status' => 0]);
            }

            DB::commit();

            Cache::forget('event_expenses'.$expense->event_id);

            dispatch(new NotifyEventParticipants(
                $expense->event,
                auth()->user(),
                'pages/timeline-page/'.$expense->event_id,
                auth()->user()->name . ' has updated expense data for ' . $expense->event->title,
                false
            ));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function removeExpense($id): bool
    {
        $expense = $this->model->findOrFail($id);

        try {
            $expense->delete();

            if ($expense->event->event_status_id == 2)
            {
                $expense->event->addParticipants()->update(['approval_status' => 0]);
            }

            return true;
        } catch (QueryException $ex) {
            return false;
        }
    }

    public function getExpenseInfo($id)
    {
        return $this->model
            ->with('bearers.user','payers.user','category','createdByInfo','lastUpdatedByInfo')->find($id);
    }
}
