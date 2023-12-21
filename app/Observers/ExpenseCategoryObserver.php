<?php

namespace App\Observers;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Cache;

class ExpenseCategoryObserver
{
    /**
     * Handle the ExpenseCategory "created" event.
     */
    public function created(ExpenseCategory $model): void
    {
        Cache::forget('expense_categories');
    }

    /**
     * Handle the ExpenseCategory "updated" event.
     */
    public function updated(ExpenseCategory $model): void
    {
        Cache::forget('expense_categories');
    }

    /**
     * Handle the ExpenseCategory "deleted" event.
     */
    public function deleted(ExpenseCategory $model): void
    {
        Cache::forget('expense_categories');
        deleteFile($model->icon_url);
    }

    /**
     * Handle the ExpenseCategory "restored" event.
     */
    public function restored(ExpenseCategory $expenseCategory): void
    {
        //
    }

    /**
     * Handle the ExpenseCategory "force deleted" event.
     */
    public function forceDeleted(ExpenseCategory $expenseCategory): void
    {
        //
    }
}
