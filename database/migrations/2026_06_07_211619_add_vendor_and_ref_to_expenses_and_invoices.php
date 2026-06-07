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
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('vendor')->nullable()->after('description');
            // Content-hash dedup key; the durable spine behind re-import idempotency
            // (the action-level idempotency cache is only a 24h replay guard).
            $table->string('ref')->nullable()->unique()->after('notes');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('ref')->nullable()->unique()->after('terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['ref']);
            $table->dropColumn(['vendor', 'ref']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['ref']);
            $table->dropColumn('ref');
        });
    }
};
