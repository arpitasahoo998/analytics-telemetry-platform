<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 64)->unique();
                $table->string('customer_name', 255);
                $table->string('customer_email', 255);
                $table->string('table_or_delivery', 255)->nullable();
                $table->decimal('total_amount', 10, 2);
                $table->string('currency', 8)->default('INR');
                $table->json('items');
                $table->string('status', 32)->default('completed');
                $table->string('anonymous_id', 64)->nullable();
                $table->string('session_id', 64)->nullable();
                $table->timestamps();
            });
        } else if (!Schema::hasColumn('orders', 'updated_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
