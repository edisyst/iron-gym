<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plate_inventory', function (Blueprint $table) {
            $table->id();
            $table->decimal('weight_kg', 5, 2)->unique();
            $table->tinyInteger('quantity_pairs')->unsigned()->default(4);
            $table->string('color', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plate_inventory');
    }
};
