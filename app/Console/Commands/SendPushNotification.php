<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-push-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notifications = Notification::where('send_status', false)->get();

        if (count($notifications) !== 0)
        {
            $beamsClient = getBeamsClient();

            foreach ($notifications as $item)
            {
                try {
                    $data = json_decode($item->data, true);

                    $publishResponse = $beamsClient->publishToUsers(
                        array(strval($item->notifiable_id)),
                        array(
                            "fcm" => array(
                                "notification" => array(
                                    "title"     => $data['triggered_by'],
                                    "body"      => $data['message'],
                                    "deep_link" => 'https://lifeplus.selopian.us' . $data['link']
                                )
                            ),
                            "apns" => array("aps" => array(
                                "alert" => array(
                                    "title"     => $data['triggered_by'],
                                    "body"      => $data['message'],
                                    "deep_link" => 'https://lifeplus.selopian.us' . $data['link']
                                )
                            )),
                            "web" => array(
                                "notification" => array(
                                    "title"     => $data['triggered_by'],
                                    "body"      => $data['message'],
                                    "deep_link" => 'https://lifeplus.selopian.us/' . $data['link']
                                )
                            )
                        ));

                    $item->send_status = 1;
                    $item->save();
                }
                catch (Throwable $th) {
                    Log::error('push notification error: ' . $th->getMessage());
                }
            }
        }
    }
}
