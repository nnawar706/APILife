<?php

namespace App\Http\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UserService
{
    private $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getAll(Request $request)
    {
        return $this->model
            // if status is present, filter active users
            ->when($request->has('status'), function ($q) {
                return $q->status();
            })
            ->orderBy('designation_id')
            ->with('designation')->with(['userBadge' => function($q) {
                return $q->with('badge')->whereMonth('created_at', Carbon::now('Asia/Dhaka')->format('n'));
            }])->get();
    }

    public function storeNewUser(Request $request): void
    {
        $user = $this->model->create([
            'designation_id'    => $request->designation_id,
            'name'              => $request->name,
            'phone_no'          => $request->phone_no,
            'birthday'          => $request->birthday,
            'password'          => $request->password,
        ]);

        // store profile image to the /users folder
        saveImage(request()->file('photo'), '/images/users/', $user, 'photo_url', true);
    }

    public function updateInfo(Request $request): bool
    {
        $user = $this->model->find(auth()->user()->id);

        $user->update([
            'designation_id'    => $request->designation_id,
            'name'              => $request->name,
            'phone_no'          => $request->phone_no,
            'birthday'          => $request->birthday,
        ]);

        if ($request->file('photo'))
        {
            deleteFile($user->photo_url);

            saveImage($request->file('photo'), '/images/users/', $user, 'photo_url', true);
        }

        return $user->wasChanged();
    }

    public function removeUser($id): bool
    {
        $user = $this->model->findOrFail($id);

        try {
            $user->delete();

            return true;
        } catch (QueryException $ex)
        {
            return false;
        }
    }

    public function updateUserStatus($id): void
    {
        $user = $this->model->findOrFail($id);

        $user->status = !$user->status;
        $user->save();
    }
}
