<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth'], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('profile', 'authProfile');
        Route::put('change_password', 'changePassword');
        Route::get('logout', 'logout');
    });

    Route::get('designations/all', [DesignationController::class, 'index']);
    Route::get('events/statuses/all', [\App\Http\Controllers\EventStatusController::class, 'index']);

    Route::controller(UserController::class)->group(function () {
        Route::get('users/all', 'index');
        Route::get('users/get/{id}', 'read');
        Route::post('users/create', 'create');
        Route::post('users/update', 'update');
        Route::get('users/change_status/{id}', 'updateStatus');
        Route::delete('users/delete/{id}', 'delete');
    });

    Route::controller(EventCategoryController::class)->group(function () {
        Route::get('event_categories/all', 'index');
        Route::post('event_categories/create', 'create');
        Route::post('event_categories/update/{id}', 'update');
        Route::get('event_categories/change_status/{id}', 'updateStatus');
        Route::delete('event_categories/delete/{id}', 'delete');
    });

    Route::controller(ExpenseCategoryController::class)->group(function () {
        Route::get('expense_categories/all', 'index');
        Route::post('expense_categories/create', 'create');
        Route::post('expense_categories/update/{id}', 'update');
        Route::get('expense_categories/change_status/{id}', 'updateStatus');
        Route::delete('expense_categories/delete/{id}', 'delete');
    });

    Route::controller(EventController::class)->group(function () {
        Route::get('events/all', 'index');
        Route::get('events/get/{id}', 'read');
        Route::post('events/create', 'create');
        Route::put('events/approve_lock', 'approveEventLock');

        Route::group(['middleware' => 'event.checker'], function () {
            Route::put('events/update/{id}', 'update');
            Route::put('events/change_status/{id}', 'updateStatus');
            Route::delete('events/delete/{id}', 'delete');
            Route::post('events/add_participants/{id}', 'addParticipants');
            Route::put('events/remove_participants/{id}', 'removeParticipant');
        });
    });

    Route::controller(ExpenseController::class)->group(function () {
        Route::get('expenses/log/{event_id}', 'eventExpenseLog');
        Route::post('expenses/create', 'create');

        Route::group(['middleware' => 'expense.checker'], function () {
            Route::put('expenses/update/{id}', 'update');
            Route::delete('expenses/delete/{id}', 'delete');
        });
    });
});
