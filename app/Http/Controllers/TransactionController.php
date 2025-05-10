<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Member;
use App\Models\Membership;
use App\Models\UserStatus;
use Carbon\Carbon; 

class TransactionController extends Controller
{
   public function getTransactions()
{
    $transactions = Transaction::with([
        'user:id,role_id,first_name,last_name,username',
        'member:id,first_name,last_name,account_balance',
        'membershipType:id,name',
        'membership:id,description'
    ])->get();

    $transactions->transform(function ($transaction) {
        $startTime = Carbon::parse($transaction->created_at);
        $endTime = $startTime->copy()->addHours($transaction->hours);
        $now = Carbon::now();

        $remaining = $now->diffAsCarbonInterval($endTime, false);

        
        $transaction->time_left = $remaining->invert == 1 
            ? '00:00:00'  
            : $remaining->cascade()->format('%H:%I:%S'); 

        if ($transaction->status === 'Active' && $remaining->invert == 1) {
            $transaction->status = 'Expired';
            $transaction->save();
        }

        return $transaction->only([
            'id', 'user_id', 'member_id', 'hours', 'total_price', 'status',
            'created_at', 'updated_at', 'time_left',
            'user', 'member', 'membershipType', 'membership'
        ]);
    });

    return response()->json(['transactions' => $transactions]);
}


    public function addTransaction(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'member_id' => ['required', 'exists:members,id'],
            'membership_type_id' => ['required', 'exists:membership_types,id'],
            'membership_id' => ['required', 'exists:memberships,id'],
            'hours' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string'],
        ]);

     
        $existingTransaction = Transaction::where('user_id', $request->user_id)
            ->where('status', 'Active')
            ->latest()
            ->first();

        if ($existingTransaction) {
            $startTime = Carbon::parse($existingTransaction->created_at);
            $endTime = $startTime->copy()->addHours($existingTransaction->hours);
            $now = Carbon::now();

            if ($now->lessThan($endTime)) {
                $timeLeft = $now->diffAsCarbonInterval($endTime)->cascade()->format('%H:%I:%S');

                return response()->json([
                    'error' => 'Cannot buy yet. Wait until your time is finished.',
                    'time_left' => $timeLeft
                ], 400);
            }

           
            $existingTransaction->status = 'Expired';
            $existingTransaction->save();
        }

        $membership = Membership::findOrFail($request->membership_id);
        $member = Member::findOrFail($request->member_id);

        $pricePerHour = $membership->price_per_hour;
        $totalPrice = $pricePerHour * $request->hours;

        if ($member->account_balance < $totalPrice) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        $member->account_balance -= $totalPrice;
        $member->save();

        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'member_id' => $request->member_id,
            'membership_type_id' => $request->membership_type_id,
            'membership_id' => $request->membership_id,
            'hours' => $request->hours,
            'total_price' => $totalPrice,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => $transaction
        ]);
    }
}
