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
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->id();
            $table->morphs('rateable');
            $table->decimal('amount', 10, 2);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_rates');
    }
};
