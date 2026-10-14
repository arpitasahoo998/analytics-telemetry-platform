<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 64)->unique();
                $table->string('event_name', 128)->index();
                $table->string('timestamp', 32);
                $table->unsignedBigInteger('timestamp_epoch')->index();
                $table->string('source', 16)->index();
                $table->string('anonymous_id', 64)->index();
                $table->string('session_id', 64)->nullable()->index();
                $table->string('user_id', 64)->nullable();
                $table->json('properties')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['event_name', 'timestamp_epoch']);
            });
        } else if (!Schema::hasColumn('events', 'updated_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
