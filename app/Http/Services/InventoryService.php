<?php

namespace App\Http\Services;

use App\Models\EventInventory;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private $model;

    public function __construct(EventInventory $model)
    {
        $this->model = $model;
    }

    public function addEventInventory(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $inventory = $this->model->create([
                'event_id'              => $id,
                'inventory_category_id' => $request->inventory_category_id,
                'title'                 => $request->title,
                'quantity'              => $request->quantity,
                'notes'                 => $request->notes
            ]);

            $inventory->participants()->sync($request->users);

            DB::commit();

            $inventory->inventoryParticipants()->each(function ($participant) use ($inventory) {
                $participant->user->notify(new UserNotification(
                    'pages/update-vaganza/' . $inventory->event_id,
                    auth()->user()->name . ' created an inventory to ' . $inventory->event->title . '.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            });

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function updateEventInventory(Request $request, $inventory_id)
    {
        $inventory = $this->model->findOrFail($inventory_id);

        DB::beginTransaction();

        try {
            $inventory->inventoryParticipants()->each(function ($participant) {
                $participant->deleteQuietly();
            });

            $inventory->participants()->sync($request->users);

            $inventory->update([
                'inventory_category_id' => $request->inventory_category_id,
                'title'                 => $request->title,
                'quantity'              => $request->quantity,
                'notes'                 => $request->notes,
                'updated_at'            => Carbon::now('Asia/Dhaka')
            ]);

            DB::commit();

            return null;
        } catch (QueryException $ex) {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function removeInventory($inventory_id): void
    {
        $inventory = $this->model->findOrFail($inventory_id);

        $inventory->inventoryParticipants()->each(function ($participant) {
            $participant->delete();
        });

        $inventory->delete();
    }

    public function getInventoryData($inventory_id)
    {
        return $this->model->with('category','participants','createdByInfo','updatedByInfo')->find($inventory_id);
    }

    public function assignedInventoryList()
    {
        return $this->model->whereHas('inventoryParticipants', function ($q) {
            return $q->where('user_id', auth()->user()->id);
        })->with('category','event','participants')->get();
    }

    public function changeInventoryStatus($status, $inventory_id): void
    {
        $inventory = $this->model->findOrFail($inventory_id);

        $inventory->inventoryParticipants()
            ->where('user_id', auth()->user()->id)
            ->update(['approval_status' => $status]);
    }
}
