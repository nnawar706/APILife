<?php

namespace App\Http\Services;

use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $event->participants()->sync($request->participants);

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
                'event_status_id'   => $request->event_status_id
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
            ->with('participants.designation','category','status',
                'designationGradings.designation','expenses.bearers','expenseBearers')
            ->find($id);

        if (!$event)
        {
            return null;
        }

        // all paid event bearer objects
        $eventPaidBearer = $event->expenseBearers()->whereHas('expense', function ($q) {
            return $q->whereNotNull('paid_at');
        })->get();

        // all unpaid event bearer objects
        $eventUnPaidBearer = $event->expenseBearers()->whereHas('expense', function ($q) {
            return $q->whereNull('paid_at');
        })->get();

        $data = json_decode($event, true);
        $eventPaidBearer = json_decode($eventPaidBearer, true);
        $eventUnPaidBearer = json_decode($eventUnPaidBearer, true);

        $totalBudget = 0;

        // designation based total budget
        foreach ($data['designation_gradings'] as $key => $item)
        {
            $designationMatched = array_filter($data['participants'], function ($value) use ($item) {
                return $value['designation_id'] == $item['designation_id'];
            });

            $totalBudget += count($designationMatched) * $item['amount'];
        }

        // estimated total expense (paid + unpaid, sponsors excluded)
        $estimatedExpense = array_sum(array_column(array_filter($data['expense_bearers'], function ($value) {
            return !$value['is_sponsored'];
        }), 'amount'));

        // estimated total sponsored
        $estimatedSponsored = array_sum(array_column(array_filter($data['expense_bearers'], function ($value) {
            return $value['is_sponsored'];
        }), 'amount'));

        // total paid
        $paid = array_sum(array_column(array_filter($eventPaidBearer, function ($value) {
            return !$value['is_sponsored'];
        }), 'amount'));

        // total sponsored
        $sponsored = array_sum(array_column(array_filter($eventPaidBearer, function ($value) {
            return $value['is_sponsored'];
        }), 'amount'));

        $payment_info = [];

        foreach ($data['participants'] as $item)
        {
//            $payment = array_filter($data['expense_bearers'], function ($value) use ($item) {
//                return ($value['paid_by_id'] == $item['id'] || ($value['user_id'] == $item['id'] && is_null($value['paid_by_id'])));
//            });


//            $totalPaid = array_sum(array_column(array_filter($payment, function ($value) {
//                return $value['is_sponsored'] == false && $value['paid_at'];
//            }), 'amount'));
//
//

            // initial payable expense based on designation grading
            $totalPayable = array_sum(array_column(array_filter($data['designation_gradings'], function ($value) use ($item) {
                return $value['designation_id'] == $item['designation_id'];
            }), 'amount'));

            // total paid expense
            $totalPaid = array_sum(array_column(array_filter($eventPaidBearer, function ($value) use ($item) {
                return  ($value['paid_by_id'] == $item['id'] || (($value['user_id'] == $item['id'] && is_null($value['paid_by_id']) && !$value['is_sponsored'])));
            }), 'amount'));

//             expenses that has been estimated earlier but not paid
//            $totalUnPaid = array_sum(array_column(array_filter($eventUnPaidBearer, function ($value) use ($item) {
//                return ($value['paid_by_id'] == $item['id'] || ($value['user_id'] == $item['id'] && is_null($value['paid_by_id']) && !$value['is_sponsored']));
//            }), 'amount'));

//            $totalUnpaid = array_sum(array_column(array_filter($eventUnPaidBearer, function ($value) use ($item) {
//
//            }), 'amount'));

            // expenses that has been sponsored
            $totalSponsored = array_sum(array_column(array_filter($eventPaidBearer, function ($value) use ($item) {
                return $value['is_sponsored'] && $value['user_id'] == $item['id'];
            }), 'amount'));



            $payment_info[$item['id']]['payable']           = $totalPayable;
//            $payment_info[$item['id']]['estimated_payable'] = $totalPaid + $totalUnPaid;
            $payment_info[$item['id']]['paid']              = $totalPaid;
//            $payment_info[$item['id']]['unpaid']            = $totalUnPaid;
            $payment_info[$item['id']]['sponsored']         = $totalSponsored;
            $payment_info[$item['id']]['overflow']          = $totalPaid - $totalPayable; // if negative, no overflow
//            $payment_info[$item['id']]['remaining']     = max($payment, 0);
//            $payment_info[$item['id']]['returnable']    = $payment < 0 ? abs($payment) : 0;
        }

        unset($data['expense_bearers']);

        return array(
            'additional_data' => array(
                'budget'                 => $totalBudget,
                'budget_overflow'        => $estimatedExpense - $totalBudget, // if negative, no overflow
                'estimated_expense'      => $estimatedExpense,
                'estimated_sponsored'    => $estimatedSponsored,
                'total_paid'             => $paid,
                'total_unpaid'           => $estimatedExpense - $paid,
                'total_sponsored'        => $sponsored,
                'payment_info'           => $payment_info
            ),
            'event_data'      => $data,
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

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();
            return $ex->getMessage();
        }
    }

    public function getAllEvents()
    {
        return $this->model->latest()
            ->with('lead','category')->withCount('participants')->get();
    }

    public function removeEventParticipant($user_id, $id)
    {
        $event = $this->model->findOrFail($id);

        $event->participants()->detach([$user_id]);
    }
}
