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
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('month'); // Format: YYYY-MM
            $table->string('account'); // e.g., 'Sales', 'Cost of Goods Sold'
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['month', 'account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};
