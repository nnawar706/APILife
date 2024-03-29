<?php

namespace App\Http\Services;

use App\Jobs\NotifyEventParticipants;
use App\Models\EventInventory;
use Illuminate\Http\Request;

class InventoryService
{
    private $model;

    public function __construct(EventInventory $model)
    {
        $this->model = $model;
    }

    public function addEventInventory(Request $request, $id): void
    {
        $this->model->create([
            'event_id'              => $id,
            'inventory_category_id' => $request->inventory_category_id,
            'assigned_to'           => $request->user_id,
            'title'                 => $request->title,
            'quantity'              => $request->quantity,
            'notes'                 => $request->notes
        ]);
    }

    public function updateEventInventory(Request $request, $inventory_id)
    {
        $inventory = $this->model->findOrFail($inventory_id);

        $inventory->update([
            'inventory_category_id' => $request->inventory_category_id,
            'assigned_to'           => $request->user_id,
            'title'                 => $request->title,
            'quantity'              => $request->quantity,
            'notes'                 => $request->notes,
            'approval_status'       => 0
        ]);

        return $inventory->wasChanged();
    }

    public function removeInventory($inventory_id): void
    {
        $inventory = $this->model->findOrFail($inventory_id);

        $inventory->delete();
    }

    public function getInventoryData($inventory_id)
    {
        return $this->model->with('category','assignedToInfo','createdByInfo','updatedByInfo')->find($inventory_id);
    }

    public function assignedInventoryList()
    {
        return $this->model->pending()->where('assigned_to', auth()->user()->id)
            ->with('category','event')->get();
    }

    public function changeInventoryStatus($status, $inventory_id)
    {
        $inventory = $this->model->findOrFail($inventory_id);

        if ($inventory->assigned_to != auth()->user()->id)
        {
            return 'You are not allowed to change the status.';
        }

        $inventory->approval_status = $status;
        $inventory->saveQuietly();

        $statusMessage = $status == 1 ? 'accepted' : 'declined';

        dispatch(new NotifyEventParticipants(
            $inventory->event,
            auth()->user(),
            'pages/update-vaganza/' . $inventory->event_id,
            auth()->user()->name . ' ' . $statusMessage . ' an assigned inventory: ' . $inventory->title,
            false,
            false
        ));

        return null;
    }
}
