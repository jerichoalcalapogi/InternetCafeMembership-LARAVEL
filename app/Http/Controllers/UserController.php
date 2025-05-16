<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    
    public function getUsers()
    {
        $users = User::with(['role', 'userStatus'])->get();
        return response()->json($users);
    }

   
public function addUser(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string',
        'middle_name' => 'nullable|string',
        'last_name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'phone_number' => 'required|string',
        'username' => 'required|string|unique:users,username',
        'password' => ['required', 'string', 'min:8', 'same:confirm_password'],
        'confirm_password' => ['required', 'string'],
        'role_id' => 'required|exists:roles,id',
        'status_id' => 'required|exists:user_statuses,id',
    ]);

    $validated['password'] = Hash::make($validated['password']);
    unset($validated['confirm_password']);

    $user = User::create($validated);

    return response()->json(['message' => 'User added successfully', 'user' => $user], 201);
}



    
    public function editUser(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'first_name' => 'sometimes|required|string',
        'middle_name' => 'nullable|string',
        'last_name' => 'sometimes|required|string',
        'email' => 'sometimes|required|email|unique:users,email,' . $id,
        'phone_number' => 'sometimes|required|string',
        'username' => 'sometimes|required|string|unique:users,username,' . $id,
        'role_id' => 'sometimes|required|exists:roles,id',
        'status_id' => 'sometimes|required|exists:user_statuses,id',
    ]);

    

    $user->update($validated);

    return response()->json(['message' => 'User updated successfully', 'user' => $user]);
}
    
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
