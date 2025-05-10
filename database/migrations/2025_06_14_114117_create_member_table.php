<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
       Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('first_name');
    $table->string('middle_name')->nullable();
    $table->string('last_name');
    $table->string('pc_number')->nullable()->unique();
    $table->decimal('account_balance', 15, 2)->nullable();
    $table->timestamps();
});
    }

   
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
