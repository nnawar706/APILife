<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Treasurer;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Services\EventService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
            // fetch events associated with that treasure hunt
            $events = $treasurer->events()->get();

            foreach ($events as $item)
            {
                // fetch event info
                $itemData = (new EventService(new Event()))->getInfo($item->event_id);

                DB::beginTransaction();

                try {
                    // extract payment info of that event
                    $itemData = $itemData['additional_data']['payment_info'];

                    // for each payment data
                    foreach ($itemData as $datum) {
                        // find if the user has any liability entry for that treasure hunt
                        $treasurer_user = $treasurer->liabilities()->where('user_id', $datum['user_id'])->first();

                        if ($treasurer_user) {
                            // if found, add current overflow with previous and save
                            $treasurer_user->amount += $datum['overflow'];
                            $treasurer_user->save();
                        } else {
                            // else, create new row with current overflow
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

            // fetch newly added liabilities
            $treasurer_liabilities = $treasurer->liabilities()->get();

            // count of total liabilities
            $treasurer_liabilities_count = $treasurer_liabilities->count();

            DB::beginTransaction();

            try {
                foreach ($treasurer_liabilities as $key => $liability) {
                    // mark liability as complete if absolute amount is less than 1
                    if (abs($liability->amount) <= 1) {
                        $liability->status = 1;
                        $liability->save();
                    }

                    // in the last loop, dispatch the job to check if the treasure hunt has been completed or not
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
