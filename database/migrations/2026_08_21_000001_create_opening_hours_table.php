<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            // slot settimanale ricorrente
            $table->tinyInteger('day_of_week')->unsigned()->nullable(); // 0=Lun..6=Dom
            // eccezione puntuale o festività annuale
            $table->date('specific_date')->nullable();
            $table->boolean('is_annual')->default(false); // true = si ripete ogni anno (ignora anno in specific_date)
            // orari
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_open')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};
