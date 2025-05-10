<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'first_name',
    'middle_name',
    'last_name',
    'pc_number',
    'account_balance', 
];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(UserStatus::class);
    }

    public function membershipType()
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }
}
