<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zoho_bills', function (Blueprint $table) {
            $table->string('bill_number')->nullable()->after('bill_id');
            $table->string('vendor_name')->nullable()->after('bill_number');
            $table->string('vendor_id')->nullable()->after('vendor_name');
            $table->date('due_date')->nullable()->after('date');
            $table->decimal('sub_total', 15, 2)->default(0)->after('total');
            $table->decimal('tax_total', 15, 2)->default(0)->after('sub_total');
            $table->decimal('balance', 15, 2)->default(0)->after('tax_total');
            $table->string('currency_code', 10)->default('INR')->after('balance');
            $table->string('reference_number')->nullable()->after('currency_code');
            $table->text('description')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('zoho_bills', function (Blueprint $table) {
            $table->dropColumn([
                'bill_number', 'vendor_name', 'vendor_id',
                'due_date', 'sub_total', 'tax_total', 'balance',
                'currency_code', 'reference_number', 'description',
            ]);
        });
    }
};
