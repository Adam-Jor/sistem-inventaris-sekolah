<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan index performa untuk mempercepat query Dashboard, Statistik, dan Filter.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['status', 'loan_date'], 'loans_status_loan_date_idx');
            $table->index(['status', 'due_date'], 'loans_status_due_date_idx');
            $table->index('loan_date', 'loans_loan_date_idx');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->index(['condition', 'stock'], 'items_condition_stock_idx');
            $table->index('stock', 'items_stock_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_status_loan_date_idx');
            $table->dropIndex('loans_status_due_date_idx');
            $table->dropIndex('loans_loan_date_idx');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_condition_stock_idx');
            $table->dropIndex('items_stock_idx');
        });
    }
};

