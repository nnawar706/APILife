<?php

namespace App\Http\Services;

use App\Models\EventCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class EventCategoryService
{
    private $model;

    public function __construct(EventCategory $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->latest()
            ->withCount('events')->get();
    }

    public function storeNewCategory(Request $request)
    {
        $category = $this->model->create([
            'name' => $request->name
        ]);

        saveImage($request->file('icon'), '/images/event_categories_icons/', $category, 'icon_url');
    }

    public function updateInfo(Request $request, $id)
    {
        $category = $this->model->findOrFail($id);

        $category->update([
            'name' => $request->name
        ]);

        if ($request->file('icon'))
        {
            deleteFile($category->icon_url);

            saveImage($request->file('icon'), '/images/event_categories_icons/', $category, 'icon_url');
        }

        return $category->wasChanged();
    }

    public function updateCategoryStatus($id)
    {
        $category = $this->model->findOrFail($id);

        $category->status = !$category->status;
        $category->save();
    }

    public function removeCategory($id)
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
