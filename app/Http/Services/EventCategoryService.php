<?php

namespace App\Http\Services;

use Illuminate\Http\Request;
use App\Models\EventCategory;
use Illuminate\Database\QueryException;

class EventCategoryService
{
    private $model;

    public function __construct(EventCategory $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        // fetch all event categories with their corresponding event count
        return $this->model->orderByDesc('id')
            ->withCount('events')->get();
    }

    public function storeNewCategory(Request $request): void
    {
        $category = $this->model->create([
            'name' => $request->name
        ]);

        saveImage($request->file('icon'), '/images/event_categories_icons/', $category, 'icon_url', false);
    }

    public function updateInfo(Request $request, $id): bool
    {
        $category = $this->model->findOrFail($id);

        $category->update([
            'name' => $request->name
        ]);

        // if request has image file, delete previous icon and save new one
        if ($request->file('icon'))
        {
            deleteFile($category->icon_url);

            saveImage($request->file('icon'), '/images/event_categories_icons/', $category, 'icon_url', false);
        }

        // return if any changes were made to the model or not
        return $category->wasChanged();
    }

    public function updateCategoryStatus($id): void
    {
        $category = $this->model->findOrFail($id);

        // invert category's current status
        $category->status = !$category->status;
        $category->save();
    }

    public function removeCategory($id): bool
    {
        $category = $this->model->findOrFail($id);

        try {
            $category->delete();

            return true;
        } catch (QueryException $ex)
        {
            return false;
        }
    }
}
