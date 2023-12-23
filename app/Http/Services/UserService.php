<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UserService
{
    private $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->latest()->with('designation')->get();
    }

    public function getUserData($id)
    {
        return $this->model->with('designation')
            ->withCount('events')
            ->withCount('leadEvents')
            ->withCount('collectedTreasures')
            ->withSum('expenses', 'amount')
            ->withSum('payments', 'amount')
            ->withSum('sponsors', 'amount')
            ->find($id);
    }

    public function storeNewUser(Request $request)
    {
        $user = $this->model->create([
            'designation_id'    => $request->designation_id,
            'name'              => $request->name,
            'phone_no'          => $request->phone_no,
            'birthday'          => $request->birthday,
            'password'          => $request->password,
        ]);

        saveImage(request()->file('photo'), '/images/users/', $user, 'photo_url');
    }

    public function updateInfo(Request $request)
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

            saveImage($request->file('photo'), '/images/users/', $user, 'photo_url');
        }

        return $user->wasChanged();
    }

    public function removeUser($id)
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

    public function updateUserStatus($id)
    {
        $user = $this->model->findOrFail($id);

        $user->status = !$user->status;
        $user->save();
    }
}
