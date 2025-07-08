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
        Schema::table('back_orders', function (Blueprint $table) {
            // Drop the old foreign key constraints
            $table->dropForeign(['order_item_id']);
            $table->dropForeign(['transfer_item_id']);
            
            // Drop the old columns
            $table->dropColumn(['order_item_id', 'transfer_item_id']);
            
            // Add the new columns
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('back_orders', function (Blueprint $table) {
            // Drop the new foreign key constraints
            $table->dropForeign(['order_id']);
            $table->dropForeign(['transfer_id']);
            
            // Drop the new columns
            $table->dropColumn(['order_id', 'transfer_id']);
            
            // Add back the old columns
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('cascade');
            $table->foreignId('transfer_item_id')->nullable()->constrained('transfer_items')->onDelete('cascade');
        });
    }
}; 