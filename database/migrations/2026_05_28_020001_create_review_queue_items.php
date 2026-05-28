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
        Schema::create('review_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_execution_id')->constrained('agent_executions')->cascadeOnDelete();
            $table->string('action_type');
            $table->json('action_payload');
            $table->text('description');
            $table->string('status')->default('pending');
            $table->string('risk_level');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_queue_items');
    }
};
