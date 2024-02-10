<?php

namespace App\Rules;

use Closure;
use FFMpeg\FFMpeg;
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
        $extension = $value->getClientOriginalExtension();

        if ($extension == 'mp4') {
            try {
                $video = new GetId3($value);

                $duration = $video->getPlaytimeSeconds();

                if ($duration > 30) {
                    $fail('Uploaded video must be less than 30 seconds in duration.');
                }
            } catch (\Throwable $th) {
                $fail($th->getMessage());
            }
        }
    }
}
