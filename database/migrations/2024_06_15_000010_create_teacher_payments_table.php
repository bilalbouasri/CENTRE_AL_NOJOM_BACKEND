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
        Schema::create('teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->integer('payment_month');
            $table->integer('payment_year');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['teacher_id', 'payment_month', 'payment_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_payments');
    }
};