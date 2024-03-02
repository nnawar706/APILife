<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Pusher\PushNotifications\PushNotifications;

function saveImage($image, $path, $model, $field, $compress): void
{
    try {
        $img = Image::make($image);

        // generate a random image name
        $image_name = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();

        if ($compress)
        {
            // resize image to 1920x1080 with keeping the aspect ratio same
            $compressedImage = $img->orientate()
                ->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                });

            // save the compressed image to server
            $compressedImage->save(public_path($path . $image_name));
        } else {
            // save the actual image to server
            $image->move(public_path($path), $image_name);
        }

        // save the record
        $model->$field = $path . $image_name;
        $model->save();
    } catch (Throwable $th) {}
}

function deleteFile($filepath): void
{
    try {
        // delete file if exists
        if (File::exists(public_path($filepath))) {
            File::delete(public_path($filepath));
        }
    } catch (Throwable $th)
    {}
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

// return 4 quotes according to the percentage
function getQuotes($percentage): array
{
    if ($percentage > 75)
    {
        return [
            'Fresh paycheck? Every day is a Payday if you invest it right!',
            "Wallet so plump, I'm thinking 'duck and dine'; let's quack the budget a bit!",
            "Budgeting? More like 'buffet-ing' when there's extra dough in the wallet! Let the feast begin!",
            "They say money can't buy happiness, but it sure can buy a gourmet meal that's pretty darn close!",
        ];
    } else if ($percentage > 50)
    {
        return [
            "Don't let all of your saving leave the room. You're gonna need them soon.",
            "Wasting money again? It's okay 🤷🏻‍♂️ Money isn't the most important thing in life, but it's reasonably close to oxygen!",
            "My budget is currently on a 'mind the gap' adventure with only ". $percentage ."% left. But hey, who needs a full budget anyway?",
            "Money left: " . $percentage . "%. Feeling like a financial acrobat, balancing between wants and needs. Someone pass me the budget tightrope!",
        ];
    } else if ($percentage > 25)
    {
        return [
            "The piggy bank is shouting 'Enough is enough!'",
            "Don't send all of your savings off on vacation; keep a few in town to tackle any unexpected villains.",
            $percentage . '% spent? The safest way to double your money is to fold it over and put it in your pocket!',
            "Salary is vanishing like magic? Just a reminder, money is essential in case you don’t die tomorrow. RIP, I guess?",
        ];
    } else if ($percentage > 0)
    {
        return [
            "Budget status: ". $percentage ."% left, but I've got 100% determination to make it work.",
            "Broke but not broken. Who needs a money tree when you've got the humor to make it through the budget drought?",
            "Welcome to the broke club! Remember, the best things in life are free – especially when you can't afford anything else",
            "Expenses flooding the salary? Time for a dramatic survival plan: beg boss for night shifts or else quit wasting habit!",
        ];
    }
    return [
        "Bank account currently on life support. Try to save some from the next time!",
        "Broke but not broken. Who needs a money tree when you've got the humor to make it through the budget drought?",
        "Welcome to the broke club! Remember, the best things in life are free – especially when you can't afford anything else",
        "No money left? Time for a dramatic survival plan: beg boss for night shifts or else quit wasting habit!",
    ];
}
