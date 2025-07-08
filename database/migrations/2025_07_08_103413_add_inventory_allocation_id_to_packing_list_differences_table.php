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
        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->foreignId('inventory_allocation_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->dropForeign(['inventory_allocation_id']);
            $table->dropColumn('inventory_allocation_id');
        });
    }
};
