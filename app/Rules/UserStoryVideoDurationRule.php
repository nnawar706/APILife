<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Owenoj\LaravelGetId3\GetId3;

class UserStoryVideoDurationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // get file extension
        $extension = strtolower($value->getClientOriginalExtension());

        if (!in_array($extension, ['jpeg','jpg','png','gif','mp4','mov','avi','wmv','webm','flv']))
        {
            $fail('Unsupported mime type detected.');
        }

        // if file mime is .mp4, check video duration
        if (!in_array($extension, ['jpeg','jpg','png','gif'])) {
            try {
                $video = new GetId3($value);

                $duration = $video->getPlaytimeSeconds();

                // if duration is greater than 30 secs, return error
                if ($duration > 30) {
                    $fail('Uploaded video must be less than 30 seconds in duration.');
                }
            } catch (\Throwable $th) {
                $fail($th->getMessage());
            }
        }
    }
}
