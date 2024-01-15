<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventImageController;
use App\Http\Controllers\EventStatusController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TreasurerController;
use App\Http\Controllers\TreasurerLiabilitiesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLoanController;
use Illuminate\Support\Facades\Route;
use Pusher\PushNotifications\PushNotifications;

Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:public']);

Route::group(['middleware' => 'auth'], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('profile', 'authProfile');
        Route::put('change_password', 'changePassword');
        Route::get('notifications/all', 'getNotifications');
        Route::get('notifications/mark_as_read', 'readNotifications');
        Route::get('unread_notifications_count', 'getNotificationCount');
        Route::get('pusher/beams_auth', 'authenticatePusher');
        Route::get('refresh', 'refreshUser');
        Route::get('logout', 'logout');
    });

    Route::get('designations/all', [DesignationController::class, 'index']);
    Route::get('events/statuses/all', [EventStatusController::class, 'index']);

    Route::controller(UserController::class)->group(function () {
        Route::get('users/all', 'index');
        Route::get('users/get/{id}', 'read');
        Route::post('users/create', 'create');
        Route::post('users/update', 'update');
        Route::get('users/change_status/{id}', 'updateStatus');
        Route::delete('users/delete/{id}', 'delete');
    });

    Route::controller(UserLoanController::class)->group(function () {
        Route::get('user_loans/all', 'index');
        Route::get('user_loans/summary', 'summary');
        Route::post('user_loans/create', 'create');
        Route::put('user_loans/update_status/{id}', 'updateStatus');
        Route::delete('user_loans/delete/{id}', 'delete');
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
        Route::get('events/include_users/all', 'getAll');
        Route::get('events/pending_events', 'pendingEvents');
        Route::post('events/create', 'create');
        Route::put('events/approve_lock', 'approveEventLock');

        Route::group(['middleware' => 'event.participant.checker:participant'], function () {
            Route::get('events/get/{id}', 'read');
            Route::get('events/participants/{id}', 'eventParticipants');
            Route::get('events/designation_gradings/{id}', 'eventDesignations');
            Route::get('events/expense_log/{id}', 'eventExpenseLog');
        });

        Route::get('events/images/{id}', 'getImages')->middleware('event.participant.checker:all');

        Route::group(['middleware' => ['event.participant.checker:participant', 'event.checker']], function () {
            Route::put('events/update/{id}', 'update');
            Route::put('events/change_status/{id}', 'updateStatus');
            Route::delete('events/delete/{id}', 'delete');
            Route::post('events/add_participants/{id}', 'addParticipants');
            Route::post('events/add_guests/{id}', 'addGuests');
            Route::put('events/remove_participants/{id}', 'removeParticipant');
        });
    });

    Route::controller(EventImageController::class)->group(function () {
        Route::group(['middleware' => 'event.participant.checker'], function () {
            Route::post('events/images/create/{id}', 'addImages');
            Route::delete('events/images/delete/{id}/{image_id}', 'deleteImage');
        });
    });

    Route::controller(ExpenseController::class)->group(function () {
        Route::get('expenses/get/{id}', 'read');
        Route::post('expenses/create', 'create');

        Route::group(['middleware' => 'expense.checker'], function () {
            Route::put('expenses/update/{id}', 'update');
            Route::delete('expenses/delete/{id}', 'delete');
        });
    });

    Route::controller(TreasurerController::class)->group(function () {
        Route::get('treasures/all', 'index');
        Route::post('treasures/create', 'create');
    });

    Route::get('treasures/change_status/{tl_id}', [TreasurerLiabilitiesController::class, 'updateStatus']);

    Route::controller(SystemController::class)->group(function () {
        Route::get('activity_logs/all', 'activities');
        Route::get('dashboard', 'dashboardData');
    });
});

Route::controller(SystemController::class)->group(function () {
    Route::post('send_random_notification', 'notifyRandomly');
    Route::get('refresh_system', 'refresh');
    Route::get('test', 'test');
});
