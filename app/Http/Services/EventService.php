<?php

namespace App\Http\Services;

use App\Jobs\NotifyEventParticipants;
use App\Models\Event;
use App\Models\ExpenseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventService
{
    private $model;

    public function __construct(Event $model)
    {
        $this->model = $model;
    }

    public function storeNewEvent(Request $request)
    {
        DB::beginTransaction();

        try {
            $event = $this->model->create([
                'event_category_id' => $request->event_category_id,
                'lead_user_id'      => $request->lead_user_id,
                'title'             => $request->title,
                'detail'            => $request->detail,
                'from_date'         => $request->from_date,
                'to_date'           => $request->to_date,
                'remarks'           => $request->remarks,
            ]);

            $participants = $request->participants;

            if(!in_array(auth()->user()->id, $participants))
            {
                $participants[] = auth()->user()->id;
            }

            $event->participants()->sync($participants);

            foreach ($request->designation_gradings as $item)
            {
                $event->designationGradings()->create([
                    'designation_id' => $item['designation_id'],
                    'amount'         => $item['amount']
                ]);
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
        $event = $this->model->findOrFail($id);

        $lead_id = $event->lead_user_id;

        DB::beginTransaction();

        try {
            $event->update([
                'event_category_id' => $request->event_category_id,
                'lead_user_id'      => $request->lead_user_id,
                'title'             => $request->title,
                'detail'            => $request->detail,
                'from_date'         => $request->from_date,
                'to_date'           => $request->to_date,
                'remarks'           => $request->remarks,
            ]);

            foreach ($request->designation_gradings as $item)
            {
                $event->designationGradings()->updateOrCreate([
                    'designation_id' => $item['designation_id']
                ], [
                    'amount'         => $item['amount']
                ]);
            }

            DB::commit();

            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' has updated an extravaganza information.',
                $lead_id != $event->lead_user_id
            ));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function getInfo($id)
    {
        $event = $this->model
            ->with('lead.designation','participants.designation','category','status',
                'designationGradings.designation','expenses.category','expenses.bearers',
                'expenses.payers','expenseBearers','expensePayers')
            ->find($id);

        if (!$event)
        {
            return null;
        }

        $data = json_decode($event, true);

        $totalBudget = 0;

        // designation based total budget
        foreach ($data['designation_gradings'] as $item)
        {
            $designationMatched = array_filter($data['participants'], function ($value) use ($item) {
                return $value['designation_id'] == $item['designation_id'];
            });

            $totalBudget += count($designationMatched) * $item['amount'];
        }

         // estimated total sponsored
        $estimatedSponsored = array_sum(array_column(array_filter($data['expense_bearers'], function ($value) {
            return $value['is_sponsored'];
        }), 'amount'));

        // estimated total expense (paid + unpaid, sponsors excluded)
        $estimatedExpense = array_sum(array_map(function ($value) {
            return $value['unit_cost'] * $value['quantity'];
        }, $data['expenses']));

        $estimatedExpense -= $estimatedSponsored;

        // total paid w/o sponsored amount
        $paid = array_sum(array_column($data['expense_payers'], 'amount'));

        $paid -= $estimatedSponsored;

        $payment_info = [];

        foreach ($data['participants'] as $key => $item)
        {
            $totalPaid = array_sum(array_column(array_filter($data['expense_payers'], function ($value) use ($item) {
                return $value['user_id'] === $item['id'];
            }), 'amount'));

            $designationRow = array_filter($data['designation_gradings'], function ($value) use ($item) {
                return $value['designation_id'] === $item['designation_id'];
            });

            // initial payable expense based on designation grading
            $initialPayable = array_sum(array_column($designationRow, 'amount'));

            // expenses that was bearable
            $totalBearable = array_sum(array_column(array_filter($data['expense_bearers'], function ($value) use ($item) {
                return !$value['is_sponsored'] && $value['user_id'] === $item['id'];
            }), 'amount'));

            // expenses that has been sponsored
            $totalSponsored = array_sum(array_column(array_filter($data['expense_bearers'], function ($value) use ($item) {
                return $value['is_sponsored'] && $value['user_id'] === $item['id'];
            }), 'amount'));

            $estimatedPayable = round($estimatedExpense * ($initialPayable / $totalBudget), 2);

            $totalPaid -= $totalSponsored;

            $payment_info[$key]['user_id']                = $item['id'];
            $payment_info[$key]['payable_percentage']     = round(($initialPayable / $totalBudget) * 100, 2) . '%';
            $payment_info[$key]['prev_payable']           = $initialPayable; // designation based
            $payment_info[$key]['estimated_payable']      = $estimatedPayable; // expense based
            $payment_info[$key]['overflow']               = round($estimatedPayable - $totalPaid, 2); // if neg, returnable to that user, else remaining payable amount
            $payment_info[$key]['paid']                   = $totalPaid; // w/o sponsored amount
            $payment_info[$key]['sponsored']              = $totalSponsored;
            $payment_info[$key]['bearable']               = round($totalBearable, 2);
        }

        unset($data['expense_bearers']);
        unset($data['expense_payers']);



        $expense_categories = ExpenseCategory::leftJoin('expenses','expense_categories.id','=','expenses.expense_category_id')
            ->leftJoin('expense_payers','expenses.id','=','expense_payers.expense_id')
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN expenses.event_id = '. $id .' THEN expense_payers.amount
                    ELSE 0 END
                ), 0) AS expense_amount,
                expense_categories.id,expense_categories.name,expense_categories.icon_url
            ')
            ->groupBy('expense_categories.id','expense_categories.name','expense_categories.icon_url')
            ->get();

        return array(
            'additional_data' => array(
                'budget'                    => $totalBudget, // w/o sponsored amount
                'budget_overflow'           => $estimatedExpense - $totalBudget, // if neg, no overflow
                'expense'                   => $estimatedExpense, // w/o sponsored amount
                'sponsored'                 => $estimatedSponsored,
                'paid'                      => $paid, // w/o sponsored amount
                'unpaid'                    => $estimatedExpense - $paid, // w/o sponsored amount
                'payment_info'              => $payment_info,
                'category_wise_expense_data'=> $expense_categories
            ),
            'event_data'                    => $data,
        );
    }

    public function addEventParticipants(Request $request, $id)
    {
        $event = $this->model->findOrFail($id);

        DB::beginTransaction();

        try {
            foreach ($request->users as $user) {
                $event->addParticipants()->firstOrCreate(['user_id' => $user]);
            }

            DB::commit();

            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' has added new participants to ' . $event->title . '.',
                false
            ));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();
            return $ex->getMessage();
        }
    }

    public function getAllEvents(Request $request)
    {
        return $this->model->latest()
            ->when($request->has('status_id'), function ($q) use ($request) {
                return $q->where('event_status_id', $request->status_id);
            })
            ->whereHas('participants', function ($q) {
                return $q->where('users.id', auth()->user()->id);
            })
            ->with('lead','category')
            ->with(['participants' => function($q) {
                return $q->select('users.id','name','photo_url');
            }])->get();
    }

    public function removeEventParticipant($user_id, $id): void
    {
        $event = $this->model->findOrFail($id);

        $participant = $event->addParticipants()->where('user_id', $user_id)->first();

        if ($participant)
        {
            $participant->delete();
        }
    }

    public function removeEvent($id): bool
    {
        $event = $this->model->findOrFail($id);

        if ($event->expensePayers()->exists())
        {
            return false;
        }

        $event->expenses()->delete();
        $event->delete();

        return true;
    }

    public function changeApprovalStatus(Request $request): bool
    {
        $event = $this->model->findOrFail($request->event_id);

        $event_participant = $event->eventParticipants()
            ->where('user_id', '=', auth()->user()->id)->first();

        $event_participant->update(['approval_status' => 1]);

        return $event_participant->wasChanged();
    }

    public function updateEventStatus($event_status_id, $id): bool
    {
        $event = $this->model->findOrFail($id);

        $event->update(['event_status_id' => $event_status_id]);

        if ($event->wasChanged() && $event_status_id == 1)
        {
            $event->addParticipants()->update(['approval_status' => 0]);
        }

        return $event->wasChanged();
    }

    public function getEventParticipantList($event_id)
    {
        $event = $this->model->find($event_id);

        if (!$event)
        {
            return null;
        }

        return $event->participants()->with('designation')->get();
    }

    public function getPendingEvents()
    {
        return $this->model
            ->where('event_status_id', '=', 2)
            ->whereHas('addParticipants', function ($q) {
                return $q->where('user_id', auth()->user()->id)->where('approval_status', 0);
            })
            ->with('lead')->get();
    }

    public function getDesignationGradings($event_id)
    {
        return $this->model->findOrFail($event_id)->designationGradings;
    }
}
