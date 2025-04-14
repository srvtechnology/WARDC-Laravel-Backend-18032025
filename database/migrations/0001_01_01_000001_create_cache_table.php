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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
            $table->timestamps(); // Adds created_at and updated_at
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
