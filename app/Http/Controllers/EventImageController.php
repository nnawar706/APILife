<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventAddImagesRequest;
use App\Jobs\NotifyEventParticipants;
use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;

class EventImageController extends Controller
{
    public function addImages(EventAddImagesRequest $request, $id)
    {
        $event = Event::findOrFail($id);
        $error = null;

        foreach ($request->images as $image) {
            try {
                $img1 = Image::make($image);
                $img2 = Image::make($image);

                $compressedImage = $img1->orientate()
                    ->resize(1500, 1500, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $height = $compressedImage->height();
                $width = $compressedImage->width();

                $thumbnailImage = $img2->orientate()
                    ->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $image_name_c = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $compressedImage->save(public_path('/images/events/' . $image_name_c));

                $image_name_t = 'thumbnail-' . time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $thumbnailImage->save(public_path('/images/events/' . $image_name_t));

                $event->images()->create([
                    'image_url' => '/images/events/' . $image_name_c,
                    'thumbnail_url' => '/images/events/' . $image_name_t,
                    'width' => $width,
                    'height' => $height
                ]);
            }  catch (\Throwable $th)
            {
                $error = $th->getMessage();
            }
        }

        Cache::forget('event_images'.$id);

        dispatch(new NotifyEventParticipants(
            $event,
            auth()->user(),
            'pages/random-snaps',
            auth()->user()->name . ' shared some memories of ' . $event->title . '. 🌸',
            false,
            true
        ));

        if (!$error) {
            return response()->json(['status' => true], Response::HTTP_CREATED);
        }

        return response()->json([
            'status' => false,
            'error'  => $error
        ], Response::HTTP_BAD_REQUEST);

    }

    public function deleteImage($id, $image_id)
    {
        $img = EventImage::findOrFail($image_id);

        $img->delete();

        return response()->json(['status' => true], Response::HTTP_OK);
    }
}
