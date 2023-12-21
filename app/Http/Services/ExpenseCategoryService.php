<?php

namespace App\Http\Services;

use App\Models\ExpenseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ExpenseCategoryService
{
    private $model;

    public function __construct(ExpenseCategory $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->latest()->get();
    }

    public function storeNewCategory(Request $request)
    {
        $category = $this->model->create([
            'name' => $request->name
        ]);

        saveImage($request->file('icon'), '/images/expense_categories_icons/', $category, 'icon_url');
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

            saveImage($request->file('icon'), '/images/expense_categories_icons/', $category, 'icon_url');
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
