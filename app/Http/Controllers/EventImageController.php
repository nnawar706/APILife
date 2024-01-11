<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventAddImagesRequest;
use App\Models\Event;
use App\Models\EventImage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;

class EventImageController extends Controller
{
    public function addImages(EventAddImagesRequest $request, $id)
    {
        $event = Event::findOrFail($id);

        try {
            foreach ($request->images as $image) {
                $img = Image::make($image);

                $compressedImage = $img->resize(1500, 1500, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $height = $compressedImage->height();
                $width  = $compressedImage->width();

                $thumbnailImage = $img->resize(500, 500);

                $image_name_c = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $compressedImage->save(public_path('/images/events/' . $image_name_c));

                $image_name_t = time() . rand(100, 9999) . '.' . $image->getClientOriginalExtension();
                $thumbnailImage->save(public_path('/images/events/' . $image_name_t));

                $event->images()->create([
                    'event_id'      => $event->id,
                    'image_url'     => '/images/events/' . $image_name_c,
                    'thumbnail_url' => '/images/events/' . $image_name_t,
                    'width'         => $width,
                    'height'        => $height
                ]);
            }

            return response()->json(['status' => true], Response::HTTP_CREATED);
        } catch (\Throwable $th)
        {
            return response()->json([
                'status' => false,
                'error'  => $th->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function deleteImage($id, $image_id)
    {
        $img = EventImage::findOrFail($image_id);

        $img->delete();

        return response()->json(['status' => true], Response::HTTP_OK);
    }
}
