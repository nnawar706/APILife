<?php

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
    try {
        if (File::exists(public_path($filepath))) {
            File::delete(public_path($filepath));
        }
    } catch (Throwable $th)
    {}
}

function clearCache(): void
{
    Artisan::call('cache:clear');
}


function getThresholds($max, $min): array
{
    $interval = ($max - $min) / 5;

    $numbers = [];

    // generate 4 numbers that divide the range
    for ($i = 1; $i <= 5; $i++) {
        $numbers[] = intval($min + $interval * $i);
    }

    return $numbers;
}

function getBeamsClient()
{
    return new PushNotifications(
        array(
            "instanceId" => env('PUSHER_INSTANCE_ID', ''),
            "secretKey" => env('PUSHER_SECRET_KEY', ''),
        )
    );
}

function getQuotes($percentage)
{
    if ($percentage > 75)
    {
        return [
            "Budgeting? More like 'buffet-ing' when there's extra dough in the wallet! Let the feast begin!",
            'Yay, the saving is full! Time to treat my taste buds like the royalty they are!',
            "They say money can't buy happiness, but it sure can buy a gourmet meal that's pretty darn close!",
            "Wallet so plump, I'm thinking 'duck and dine'; let's quack the budget a bit!"
        ];
    } else if ($percentage > 50)
    {
        return [
            "Money left: " . $percentage . "%. Feeling like a financial acrobat, balancing between wants and needs. Someone pass me the budget tightrope!",
            "Don't let your money sneak out the back door; it's got plans for a brighter future!",
            "My budget is currently on a 'mind the gap' adventure with only ". $percentage ."% left. But hey, who needs a full budget anyway?",
            "Don't let all of your saving leave the room. You're gonna need them soon."
        ];
    } else if ($percentage > 25)
    {
        return [
            "Don't let your money sneak out the back door; it's got plans for a brighter future!",
            "The piggy bank is shouting 'Enough is enough!'",
            "Don't send all of your savings off on vacation; keep a few in town to tackle any unexpected villains.",
            "Your savings are like breadcrumbs on the path to financial freedom. Don't throw the whole loaf away; save some for a future feast!"
        ];
    }
    return [
        "Welcome to the broke club! Remember, the best things in life are free – especially when you can't afford anything else",
        "Broke but not broken. Who needs a money tree when you've got the humor to make it through the budget drought?",
        "The piggy bank suggests 'see no spending, hear no spending, speak no spending' diet.",
        "Budget status: ". $percentage ."% left, but I've got 100% determination to make it work."
    ];
}
