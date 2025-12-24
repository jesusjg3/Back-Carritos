<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function all()
    {
        return User::all();
    }

    public function find($id)
    {
        return User::findOrFail($id);
    }

    public function findByEmail($email)
    {
        return User::where('email', $email)->with('role')->first();
    }

    public function create(array $data)
    {
        $data["password"] = Hash::make($data["password"]);
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);

        if (!empty($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }

        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        return User::findOrFail($id)->delete();
    }
}
