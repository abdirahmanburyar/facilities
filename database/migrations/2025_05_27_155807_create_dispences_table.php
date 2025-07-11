<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispences', function (Blueprint $table) {
            $table->id();
            $table->string('dispence_number');
            $table->date('dispence_date');
            $table->string('patient_name');
            $table->integer('patient_age');
            $table->enum('patient_gender', ['male', 'female']);
            $table->string('patient_phone');
            $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
            $table->foreignId('dispenced_by')->constrained('users');
            $table->text('diagnosis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispences');
    }
};
