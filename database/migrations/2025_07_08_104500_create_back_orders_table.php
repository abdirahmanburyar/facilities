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
        Schema::create('back_orders', function (Blueprint $table) {
            $table->id();
            $table->string('back_order_number')->unique();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('cascade');
            $table->foreignId('transfer_item_id')->nullable()->constrained('transfer_items')->onDelete('cascade');
            $table->date('back_order_date');
            $table->integer('total_items')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->string('source_type')->nullable();
            $table->json('attach_documents')->nullable();
            $table->string('reported_by')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('back_orders');
    }
}; 