<?php

namespace App\Jobs;

use App\Http\Services\EventService;
use App\Models\Event;
use App\Models\Treasurer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TreasurerLiabilitiesCalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $treasurer_id;

    /**
     * Create a new job instance.
     */
    public function __construct($treasurer_id)
    {
        $this->treasurer_id = $treasurer_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $treasurer = Treasurer::find($this->treasurer_id);

        if ($treasurer)
        {
            $events = $treasurer->events()->get();

            foreach ($events as $item)
            {
                $itemData = (new EventService(new Event()))->getInfo($item->event_id);

                DB::beginTransaction();

                try {
                    $itemData = $itemData['additional_data']['payment_info'];

                    foreach ($itemData as $datum) {
                        $treasurer_user = $treasurer->liabilities()->where('user_id', $datum['user_id'])->first();

                        if ($treasurer_user) {
                            $treasurer_user->amount += $datum['overflow'];
                            $treasurer_user->save();
                        } else {
                            $treasurer->liabilities()->create([
                                'user_id' => $datum['user_id'],
                                'amount'  => $datum['overflow']
                            ]);
                        }
                    }

                    DB::commit();
                } catch (QueryException $ex) {
                    DB::rollback();
                }
            }

            $treasurer_liabilities = $treasurer->liabilities()->get();

            $treasurer_liabilities_count = $treasurer_liabilities->count();

            DB::beginTransaction();

            try {
                foreach ($treasurer_liabilities as $key => $liability) {
                    if (abs($liability->amount) <= 1) {
                        $liability->status = 1;
                        $liability->save();
                    }

                    if ($treasurer_liabilities_count - $key == 1) {
                        dispatch(new TreasurerCompletion($liability));
                    }
                }
                DB::commit();
            } catch (QueryException $ex)
            {
                DB::rollback();
            }
        }
    }
}
