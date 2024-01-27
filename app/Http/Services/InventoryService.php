<?php

namespace App\Http\Services;

use App\Models\EventInventory;
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

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }
}
