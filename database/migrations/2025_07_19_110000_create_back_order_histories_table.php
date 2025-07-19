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
        Schema::create('back_order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_list_id')->nullable()->constrained('packing_lists')->onDelete('cascade');
            $table->foreignId('transfer_item_id')->nullable()->constrained('transfer_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('cascade');
            $table->string('uom')->nullable();
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->foreignId('back_order_id')->nullable()->constrained('back_orders')->onDelete('cascade');
            $table->integer('quantity');
            $table->string('status'); // Missing, Damaged, etc.
            $table->text('note')->nullable();
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->string('batch_number')->nullable();
            $table->string('barcode')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('back_order_histories');
    }
}; 