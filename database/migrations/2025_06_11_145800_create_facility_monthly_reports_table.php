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
        Schema::create('facility_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->string('report_period', 7); // Format: YYYY-MM (e.g., 2025-06)
            
            // Report metadata
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraint to prevent duplicate reports
            $table->unique(['facility_id', 'report_period'], 'unique_facility_report');
            
            // Indexes for performance
            $table->index(['facility_id', 'report_period'], 'idx_facility_period');
            $table->index(['status'], 'idx_status');
            $table->index(['report_period'], 'idx_report_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_monthly_reports');
    }
};
