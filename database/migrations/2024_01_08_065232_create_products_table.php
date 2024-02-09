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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique();
            $table->string('upc')->nullable();
            $table->string('sku')->nullable();
            $table->string('asin')->nullable();
            $table->string('title')->nullable();
            $table->longtext('description')->nullable();
            $table->longtext('images')->nullable();
            $table->string('brand')->nullable();
            $table->longtext('ingredients')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->longtext('category')->nullable();
            $table->string('weight')->nullable();
            $table->string('dimension')->nullable();
            $table->decimal('price')->default(0);
            $table->decimal('lowest_recorded_price')->default(0);
            $table->decimal('highest_recorded_price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
