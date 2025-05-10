<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Creating the 'membership_types' table
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Inserting membership types
        DB::table('membership_types')->insert([
            [
                'name' => 'REGULAR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        // Dropping the 'membership_types' table
        Schema::dropIfExists('membership_types');
    }
};
