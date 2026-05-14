<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zoho_invoices', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('invoice_id');
            $table->string('customer_name')->nullable()->after('invoice_number');
            $table->string('customer_id')->nullable()->after('customer_name');
            $table->string('email')->nullable()->after('customer_id');
            $table->date('due_date')->nullable()->after('date');
            $table->decimal('sub_total', 15, 2)->default(0)->after('total');
            $table->decimal('tax_total', 15, 2)->default(0)->after('sub_total');
            $table->decimal('balance', 15, 2)->default(0)->after('tax_total');
            $table->string('currency_code', 10)->default('INR')->after('balance');
            $table->string('reference_number')->nullable()->after('currency_code');
            $table->text('notes')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('zoho_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number', 'customer_name', 'customer_id', 'email',
                'due_date', 'sub_total', 'tax_total', 'balance',
                'currency_code', 'reference_number', 'notes',
            ]);
        });
    }
};
