<?php

namespace App\Http\Services;

use App\Jobs\TreasurerLiabilitiesCalculation;
use App\Models\Treasurer;
use Carbon\Carbon;
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
            $treasurer = $this->model->create([
                'user_id'  => $request->user_id,
                'deadline' => $request->deadline
            ]);

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

    public function getAll()
    {
        $data = $this->model
            ->whereHas('liabilities', function ($q) {
                return $q->where('user_id', auth()->user()->id);
            })
            ->with('treasurer','events.event')
            ->latest('deadline')->get();

        $response = [];

        foreach ($data as $key => $item)
        {
            $liabilities = $item->liabilities;

            $response[$key] = $item;

            $deadline = Carbon::parse($item->deadline);

            foreach ($liabilities as $index => $value) {
                $response[$key]['liabilities'][$index] = $value;
                if ($value->amount > 0)
                {
                    $toDate = $value->status ? Carbon::parse($value->updated_at) :
                        Carbon::now('Asia/Dhaka');

                    if ($toDate->gt($deadline)) // deadline crossed
                    {
                        $diffInDays = Carbon::parse($toDate)->diffInDays($deadline);

                        $response[$key]['liabilities'][$index]['late_in_days'] = $diffInDays;

                        $response[$key]['liabilities'][$index]['fine'] = ($diffInDays > 0) ?
                            round($diffInDays * ($value->amount * 5 / 100), 2) : 0;
                    } else {
                        $response[$key]['liabilities'][$index]['late_in_days'] = 0;
                        $response[$key]['liabilities'][$index]['fine']         = 0;
                    }
                } else {
                    $response[$key]['liabilities'][$index]['late_in_days'] = 0;
                    $response[$key]['liabilities'][$index]['fine'] = 0;
                }
                $response[$key]['liabilities'][$index]['user'] = $value->user;
            }
        }

        return $response;
    }
}
