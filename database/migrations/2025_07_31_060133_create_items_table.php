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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category_id');
            $table->decimal('price', 10, 2);
            $table->string('currency_id', 3);
            $table->integer('available_quantity');
            $table->string('buying_mode');
            $table->string('listing_type_id');
            $table->string('condition');
            $table->text('description');
            $table->string('video_id')->nullable();
            $table->json('pictures');
            $table->string('mercadolibre_id')->nullable()->unique();
            $table->string('status')->default('draft'); // draft, published, sold, deleted
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['status']);
            $table->index(['category_id']);
            $table->index(['mercadolibre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
