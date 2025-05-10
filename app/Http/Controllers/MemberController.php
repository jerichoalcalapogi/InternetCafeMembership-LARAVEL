<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;

class MemberController extends Controller
{
    public function getMembers()
    {
        $members = Member::with('user')->get();
        return response()->json(['members' => $members]);
    }

    public function addMember(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'pc_number' => ['nullable', 'string', 'max:255', 'unique:members'],
            'account_balance' => ['nullable', 'numeric'],
        ]);

        $member = Member::create([
            'user_id' => $request->user_id,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'pc_number' => $request->pc_number,
            'account_balance' => $request->account_balance,
        ]);

        return response()->json(['message' => 'Member added successfully', 'member' => $member]);
    }

    public function editMember(Request $request, $id)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'pc_number' => ['nullable', 'string', 'max:255', 'unique:members,pc_number,' . $id],
            'account_balance' => ['nullable', 'numeric'],
        ]);

        $member = Member::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->update([
            'user_id' => $request->user_id,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'pc_number' => $request->pc_number,
            'account_balance' => $request->account_balance,
        ]);

        return response()->json(['message' => 'Member updated successfully', 'member' => $member]);
    }

    public function deleteMember($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->delete();

        return response()->json(['message' => 'Member deleted successfully']);
    }
}
