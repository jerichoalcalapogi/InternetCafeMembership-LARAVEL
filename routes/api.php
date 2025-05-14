<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;



Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/login', [AuthenticationController::class, 'login']);
Route::middleware('auth:sanctum')->group( function () {

    Route::get('/get-member', [MemberController::class, 'getMembers']);
    Route::post('/add-member', [MemberController::class, 'addMember']);
    Route::put('/edit-member/{id}', [MemberController::class, 'editMember']);

    Route::delete('/delete-member/{id}', [MemberController::class, 'deleteMember']);
    Route::post('/add-transaction', [TransactionController::class, 'addTransaction']);
    Route::get('/get-transaction', [TransactionController::class, 'getTransactions']);
    Route::put('/edit-transaction/{id}', [TransactionController::class, 'editTransactions']);
    Route::delete('/delete-transaction/{id}', [TransactionController::class, 'deleteTransaction']);
    Route::post('/logout', [AuthenticationController::class, 'logout']);

});
