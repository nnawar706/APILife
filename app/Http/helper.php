<?php

use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;

function saveImage($image, $path, $model, $field): void
{
    try {
        $image_name = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
        $image->move(public_path($path), $image_name);
        $model->$field = $path . $image_name;
        $model->save();
    } catch (Throwable $th) {
        Log::error('save image: ' . $th->getMessage());
    }
}

function deleteFile($filepath): void
{
    if (File::exists(public_path($filepath)))
    {
        File::delete(public_path($filepath));
    }
}

function clearCache(): void
{
    Artisan::call('cache:clear');
}


function getThresholds($max, $min): array
{
    $interval = ($max - $min) / 4;

    $numbers = [];

    // generate 4 numbers that divide the range
    for ($i = 1; $i <= 4; $i++) {
        $numbers[] = intval($min + $interval * $i);
    }

    return $numbers;
}

function sendNotification($user_ids, $link, $message)
{
    $notify_users = User::whereIn('id', $user_ids)->get();

    foreach ($notify_users as $user)
    {
        $user->notify(new UserNotification($link, $message));
    }
}


function pushNotification()
{
    try {
        $beamsClient = new PushNotifications(
            array(
                "instanceId" => env('PUSHER_INSTANCE_ID', ''),
                "secretKey" => env('PUSHER_SECRET_KEY', ''),
            )
        );

        $publishResponse = $beamsClient->publishToInterests(
            array("hello", "donuts"),
            array(
                "fcm" => array(
                    "notification" => array(
                        "title" => "Hi!",
                        "body" => "This is my first Push Notification!"
                    )
                ),
                "apns" => array("aps" => array(
                    "alert" => array(
                        "title" => "Hi!",
                        "body" => "This is my first Push Notification!"
                    )
                )),
                "web" => array(
                    "notification" => array(
                        "title" => "Hi!",
                        "body" => "This is my first Push Notification!"
                    )
                )
            ));

        return response()->json($publishResponse);

    } catch (Throwable $th)
    {
        return response()->json('push notification error: ' . $th->getMessage());
    }
}
