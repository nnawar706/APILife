<?php

namespace App\Http\Services;

use App\Jobs\TreasurerLiabilitiesCalculation;
use App\Models\Treasurer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreasurerService
{
    private $model;

    public function __construct(Treasurer $model)
    {
        $this->model = $model;
    }

    public function storeNewTreasurer(Request $request)
    {
        DB::beginTransaction();

        try {
            $treasurer = $this->model->create(['user_id' => $request->user_id]);

            foreach ($request->events as $item)
            {
                $treasurer->events()->create(['event_id' => $item]);
            }

            DB::commit();

            dispatch(new TreasurerLiabilitiesCalculation($treasurer->id));

            return null;
        } catch (QueryException $ex)
        {
            DB::rollback();

            return $ex->getMessage();
        }
    }

    public function getAll($includeAll)
    {
        return $this->model
            ->when($includeAll, function ($q) {
                return $q->with('treasurer');
            })
            ->when(!$includeAll, function ($q) {
                return $q->where('user_id', auth()->user()->id);
            })
            ->with('liabilities.user')
            ->latest()->get();
    }
}
