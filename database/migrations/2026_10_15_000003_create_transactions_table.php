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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('expense'); // expense, income, transfer
            $table->unsignedBigInteger('amount'); // Nominal Rupiah utuh (BIGINT)
            $table->string('notes')->nullable();
            $table->string('source', 30)->default('web_dashboard'); // telegram_bot, web_dashboard
            $table->string('external_message_id', 100)->nullable()->index(); // Untuk tracking Telegram undo
            $table->dateTime('transacted_at')->index(); // Tanggal kejadian transaksi
            $table->timestamps();

            // Index komposit untuk query performa analitik & dashboard
            $table->index(['user_id', 'transacted_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
