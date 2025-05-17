<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Member;
use App\Models\Membership;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function getTransactions(Request $request)
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

            if (cache()->has("transaction_pause_{$transaction->id}")) {
                $pauseStart = Carbon::parse(cache("transaction_pause_{$transaction->id}"));
                $pausedDuration = $now->diffInSeconds($pauseStart);
                $startTime->addSeconds($pausedDuration);
                $endTime = $startTime->copy()->addHours($transaction->hours);
            }

            $remaining = $now->diffAsCarbonInterval($endTime, false);

            $transaction->time_left = $remaining->invert == 1
                ? '00:00:00'
                : $remaining->cascade()->format('%H:%I:%S');

            if ($transaction->status === 'Running' && $remaining->invert == 1) {
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
    ]);

    $user = User::find($request->user_id);
    if (!$user) {
        return response()->json(['error' => 'User not found.'], 404);
    }
    if ($user->role_id == 2) {
        return response()->json(['error' => 'Access denied. Only Admins can create transactions. Cashiers are limited to handling payments and monitoring activities '], 403);
    }

    $existingTransaction = Transaction::where('user_id', $request->user_id)
        ->where('member_id', $request->member_id)
        ->whereIn('status', ['Running', 'Pause'])
        ->latest()
        ->first();

    if ($existingTransaction) {
        $startTime = Carbon::parse($existingTransaction->created_at);
        $endTime = $startTime->copy()->addHours($existingTransaction->hours);
        $now = Carbon::now();

        if (
            $existingTransaction->status === 'Pause' &&
            cache()->has("transaction_pause_{$existingTransaction->id}")
        ) {
            $pauseStart = Carbon::parse(cache("transaction_pause_{$existingTransaction->id}"));
            $pausedDuration = $now->diffInSeconds($pauseStart);
            $startTime->addSeconds($pausedDuration);
            $endTime = $startTime->copy()->addHours($existingTransaction->hours);
        }

        $remaining = $now->diffAsCarbonInterval($endTime, false);

        if ($remaining->invert == 1) {
            $existingTransaction->status = 'Expired';
            $existingTransaction->save();
        } else {
            $timeLeft = $remaining->cascade()->format('%H:%I:%S');

            return response()->json([
                'error' => 'Cannot create new transaction.',
                'active_transaction' => [
                    'id' => $existingTransaction->id,
                    'status' => $existingTransaction->status === 'Pause' ? 'Paused' : $existingTransaction->status,
                    'time_left' => $timeLeft
                ]
            ], 400);
        }
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
        'status' => 'Running',
    ]);

    $startTime = Carbon::parse($transaction->created_at);
    $endTime = $startTime->copy()->addHours($transaction->hours);
    $now = Carbon::now();
    $remaining = $now->diffAsCarbonInterval($endTime, false);

    $transaction->time_left = $remaining->invert == 1
        ? '00:00:00'
        : $remaining->cascade()->format('%H:%I:%S');

    return response()->json([
        'message' => 'Transaction created successfully',
        'transaction' => $transaction
    ]);
}


    public function editTransactions(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Running,Pause,Expired,Resume',
        ]);

        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status === 'Expired') {
            return response()->json(['message' => 'Transaction is already expired.'], 400);
        }

        if ($request->status === 'Pause') {
            if ($transaction->status !== 'Running') {
                return response()->json(['message' => 'Only running transactions can be paused.'], 400);
            }

            cache(["transaction_pause_{$id}" => Carbon::now()], now()->addMinutes(30));

            $transaction->status = 'Pause';
            $transaction->save();

            return response()->json(['message' => 'Transaction paused successfully.', 'transaction' => $transaction]);
        }

        if ($request->status === 'Resume') {
            if ($transaction->status !== 'Pause') {
                return response()->json(['message' => 'Only paused transactions can be resumed.'], 400);
            }

            if (!cache()->has("transaction_pause_{$id}")) {
                return response()->json(['message' => 'No pause time recorded.'], 400);
            }

            $pauseStart = Carbon::parse(cache("transaction_pause_{$id}"));
            $pausedDuration = Carbon::now()->diffInSeconds($pauseStart);

            $transaction->created_at = $transaction->created_at->addSeconds($pausedDuration);
            $transaction->status = 'Running';
            $transaction->save();

            cache()->forget("transaction_pause_{$id}");

            return response()->json(['message' => 'Transaction resumed successfully.', 'transaction' => $transaction]);
        }

        $transaction->status = $request->status;
        $transaction->save();

        return response()->json(['message' => 'Transaction status updated.', 'transaction' => $transaction]);
    }

    public function deleteTransaction($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
