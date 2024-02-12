<?php

namespace App\Http\Services;

use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;
use App\Jobs\NotifyEventParticipants;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Database\QueryException;

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
                'is_public'         => $request->is_public ?? 0
            ]);

            $participants = $request->participants;

            // include auth user in the requested participant list if not present
            if(!in_array(auth()->user()->id, $participants))
            {
                $participants[] = auth()->user()->id;
            }

            // attach participants to newly created extravaganza
            $event->participants()->sync($participants);

            // add designation based grading entries
            foreach ($request->designation_gradings as $item)
            {
                $event->designationGradings()->create([
                    'designation_id' => $item['designation_id'],
                    'amount'         => $item['amount']
                ]);
            }

            DB::commit();

            // notify all participants and lead about new extravaganza
            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/extra-vaganza',
                'Mark your calendars for '. $event->title .' and join the party 🥳✨',
                true,
                false
            ));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            // return error, if any
            return $ex->getMessage();
        }
    }

    public function updateInfo(Request $request, $id)
    {
        $event = $this->model->findOrFail($id);

        // save extravaganza lead
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
                'is_public'         => $request->is_public ?? 0
            ]);

            foreach ($request->designation_gradings as $item)
            {
                // update each designation based grading
                $event->designationGradings()->updateOrCreate([
                    'designation_id' => $item['designation_id']
                ], [
                    'amount'         => $item['amount']
                ]);
            }

            DB::commit();

            // notify changes to participants
            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/update-vaganza/'.$event->id,
                auth()->user()->name . ' updated an extravaganza information.',
                // notify to lead only if lead user has changed
                $lead_id != $event->lead_user_id,
                false
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
            ->with('lead','participants','guests','images','category','status','rating',
                'inventories.category','inventories.assignedToInfo',
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

            $estimatedPayable = $totalBudget != 0 ? round($estimatedExpense * ($initialPayable / $totalBudget), 2) : 0;

            $totalPaid -= $totalSponsored;

            $payment_info[$key]['user_id']                = $item['id'];
            $payment_info[$key]['payable_percentage']     = $totalBudget != 0 ? round(($initialPayable / $totalBudget) * 100, 2) . '%' : '0%';
            $payment_info[$key]['prev_payable']           = $initialPayable; // designation based
            $payment_info[$key]['estimated_payable']      = $estimatedPayable; // expense based
            $payment_info[$key]['overflow']               = round($totalBearable - $totalPaid, 2); // if neg, returnable to that user, else remaining payable amount
            $payment_info[$key]['paid']                   = round($totalPaid, 2); // w/o sponsored amount
            $payment_info[$key]['sponsored']              = round($totalSponsored, 2);
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

        $rating_status = $event->eventParticipants()->where('user_id', auth()->user()->id)->first()->rated;

        return array(
            'additional_data' => array(
                'rated'                     => $rating_status,
                'budget'                    => $totalBudget, // w/o sponsored amount
                'budget_overflow'           => round($estimatedExpense - $totalBudget, 2), // if neg, no overflow
                'expense'                   => round($estimatedExpense, 2), // w/o sponsored amount
                'sponsored'                 => round($estimatedSponsored, 2),
                'paid'                      => round($paid, 2), // w/o sponsored amount
                'unpaid'                    => round($estimatedExpense - $paid, 2), // w/o sponsored amount
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
                $event->addParticipants()->firstOrCreate([
                    'user_id' => $user
                ]);
            }

            $event->eventGuests()->whereIn('user_id', $request->users)->delete();

            DB::commit();

            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' added new participants to ' . $event->title . '.',
                false,
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
        return $this->model
            // when request has a query parameter named status_id
            // value of status_id can only be 3
            ->when($request->has('status_id'), function ($q) use ($request) {
                // fetch those that has a status of approved
                return $q->where('event_status_id', $request->status_id)
                    // fetch those in which auth user has participated
                    ->where(function ($q) {
                        $q->whereHas('eventParticipants', function ($q1) {
                            return $q1->where('user_id', auth()->user()->id);
                        });
                    })
                    // fetch those that has not been treasured yet
                    ->whereDoesntHave('treasurer');
            })
            // when no query parameter is present
            ->when(!$request->has('status_id'), function ($q) use ($request) {
                // fetch public extravaganzas
                return $q->where('is_public', '=', 1)
                    // also fetch those where auth user has participated
                    ->orWhere(function ($q) {
                        $q->whereHas('eventParticipants', function ($q1) {
                            return $q1->where('user_id', auth()->user()->id);
                        });
                    });
            })
            // fetch lead, category and rating
            ->with('lead','category','rating')
            ->with(['participants' => function($q) {
                // fetch only id, name & photo of participants
                return $q->select('users.id','name','photo_url');
            }])
            ->with(['eventParticipants' => function($q) {
                // fetch only the row where auth user is present (to check if rated or not)
                return $q->where('user_id', auth()->user()->id);
            }])
            ->with(['guests' => function($q) {
                // fetch only id, name & photo of guests
                return $q->select('users.id','name','photo_url');
            }])
            // ongoing extravaganzas will show at first, then locked & approved, and so on
            ->orderBy('event_status_id')
            // extravaganzas that will occur early, will show first
            ->latest('from_date')->get();
    }

    public function getParticipantBasedEvents()
    {
        return $this->model
            // extravaganzas that will occur early, will show first
            ->latest('from_date')
            ->whereHas('eventParticipants', function ($q) {
                // fetch in which auth user has participated
                return $q->where('user_id', auth()->user()->id);
            })->orWhere(function ($q) {
                // also fetch those where auth user was guest
                return $q->whereHas('eventGuests', function ($q1) {
                    return $q1->where('user_id', auth()->user()->id);
                });
            })->get();
    }

    public function getPendingEvents()
    {
        return $this->model
            // fetch extravaganzas that are locked
            ->where('event_status_id', '=', 2)
            ->whereHas('eventParticipants', function ($q) {
                // fetch those which auth user has not approved
                return $q->where('user_id', auth()->user()->id)
                    ->where('approval_status', 0);
            })
            // fetch lead
            ->with('lead')
            // extravaganzas that has occurred early, will show first
            ->latest('from_date')
            ->get();
    }

    public function removeEventParticipant($user_id, $id): bool
    {
        $event = $this->model->findOrFail($id);

        //
        $participant = $event->addParticipants()->where('user_id', $user_id)->first();

        if ($participant)
        {
            $participant->delete();

            Cache::forget('event_participants'.$id);

            return true;
        }

        return false;
    }

    public function removeEventGuest($user_id, $id): bool
    {
        $event = $this->model->findOrFail($id);

        $guest = $event->eventGuests()
            ->where('user_id', $user_id)->first();

        if ($guest)
        {
            $guest->delete();

            return true;
        }

        return false;
    }

    public function removeEvent($id): bool
    {
        $event = $this->model->findOrFail($id);

        // event won't be deleted if payer data exists
        if ($event->expensePayers()->exists())
        {
            return false;
        }

        // delete event expenses
        $event->expenses()->delete();
        // delete each event image
        $event->images()->each(function ($image) {
            $image->delete();
        });

        // delete event
        $event->delete();

        return true;
    }

    public function changeApprovalStatus(Request $request): bool
    {
        $event = $this->model->findOrFail($request->event_id);

        $event_participant = $event->eventParticipants()
            ->where('user_id', '=', auth()->user()->id)->first();

        if ($event_participant)
        {
            $event_participant->update(['approval_status' => 1]);

            return $event_participant->wasChanged();
        }

        return false;
    }

    public function updateEventStatus($event_status_id, $id): bool
    {
        $event = $this->model->findOrFail($id);

        $event->update(['event_status_id' => $event_status_id]);

        if ($event->wasChanged())
        {
            if ($event_status_id == 1)
            {
                $event->addParticipants()->update(['approval_status' => 0]);
            }
            else if ($event_status_id == 2)
            {
                $participant = $event->addParticipants()->where('user_id', auth()->user()->id)->first();

                $participant?->update(['approval_status' => 1]);
            }
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

    public function getDesignationGradings($event_id)
    {
        return $this->model->findOrFail($event_id)->designationGradings;
    }

    public function getExpenseLog($event_id)
    {
        return $this->model
            ->with('category','expenses.bearers.user','expenses.payers.user','expenses.category',
            'expenses.createdByInfo','expenses.lastUpdatedByInfo')
            ->find($event_id);
    }

    public function getEventImages($id)
    {
        return $this->model->findOrFail($id)->images()->get();
    }

    public function addEventGuests(Request $request, $id)
    {
        $event = $this->model->findOrFail($id);

        DB::beginTransaction();

        try {
            foreach ($request->users as $user) {
                $event->eventGuests()->firstOrCreate([
                    'user_id' => $user
                ]);
            }

            DB::commit();

            dispatch(new NotifyEventParticipants(
                $event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' added new guests to ' . $event->title . '.',
                false,
                false
            ));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();
            return $ex->getMessage();
        }
    }

    public function addRating(Request $request, $id)
    {
        $event = $this->model->findOrFail($id);

        if (!in_array($event->event_status_id, [3,4]))
        {
            return 'You cannot rate an extravaganza before it gets approved.';
        }

        $participant = $event->eventParticipants()->where('user_id', auth()->user()->id)->first();

        if ($participant->rated)
        {
            return 'You can rate an extravaganza only once.';
        }

        $new_rating = round(($event->rating->rating + ($request->rating / 10)), 2);
        $rated_by   = $event->rating->rated_by + 1;

        if ($request->note){
            $notes = json_decode($event->rating->notes, true);
            $notes[] = $request->note;

            $event->rating->notes      = json_encode($notes);
        }

        $event->rating->rating     = $new_rating;
        $event->rating->rated_by   = $rated_by;
        $event->rating->avg_rating = round($new_rating / $rated_by, 2);

        $event->rating->save();

        $participant->rated_at = Carbon::now('Asia/Dhaka');
        $participant->rated    = 1;
        $participant->saveQuietly();

        return null;
    }

    public function storeEventImages(Request $request, $id)
    {
        $event = $this->model->findOrFail($id);
        $error = null;

        foreach ($request->images as $image) {
            try {
                $img1 = Image::make($image);
                $img2 = Image::make($image);

                $compressedImage = $img1->orientate()
                    ->resize(1500, 1500, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $height = $compressedImage->height();
                $width = $compressedImage->width();

                $thumbnailImage = $img2->orientate()
                    ->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $image_name_c = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $compressedImage->save(public_path('/images/events/' . $image_name_c));

                $image_name_t = 'thumbnail-' . time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $thumbnailImage->save(public_path('/images/events/' . $image_name_t));

                $event->images()->create([
                    'image_url' => '/images/events/' . $image_name_c,
                    'thumbnail_url' => '/images/events/' . $image_name_t,
                    'width' => $width,
                    'height' => $height
                ]);
            }  catch (\Throwable $th)
            {
                $error = $th->getMessage();
            }
        }

        Cache::forget('event_images'.$id);

        dispatch(new NotifyEventParticipants(
            $event,
            auth()->user(),
            'pages/random-snaps',
            auth()->user()->name . ' shared some memories of ' . $event->title . '. 🌸',
            false,
            true
        ));

        return $error;
    }

    public function removeEventImage($id, $image_id): bool
    {
        $event = $this->model->findOrFail($id);

        $image = $event->images()->where('id', $image_id)->first();

        if ($image) {
            $image->delete();

            return true;
        }

        return false;
    }
}
