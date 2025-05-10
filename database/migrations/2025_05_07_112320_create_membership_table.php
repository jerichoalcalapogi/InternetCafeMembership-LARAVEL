<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable(); 
            $table->decimal('price_per_hour', 8, 2)->default(0); 
            $table->timestamps();
        });

      // Inserting sample data with only description and price
// Inserting sample data with detailed descriptions and prices
DB::table('memberships')->insert([
    ['description' => 'Ryzen 3 3200, 8 GB RAM, RTX 2 GB GPU', 'price_per_hour' => 10.00, 'created_at' => now(), 'updated_at' => now()],
    ['description' => 'Ryzen 5 3600, 16 GB RAM, GTX 4 GB GPU', 'price_per_hour' => 15.00, 'created_at' => now(), 'updated_at' => now()],
    ['description' => 'Ryzen 7 3700X, 32 GB RAM, RTX 6 GB GPU', 'price_per_hour' => 20.00, 'created_at' => now(), 'updated_at' => now()],
    ['description' => 'Ryzen 9 3900X, 64 GB RAM, RTX 8 GB GPU', 'price_per_hour' => 30.00, 'created_at' => now(), 'updated_at' => now()],
]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
