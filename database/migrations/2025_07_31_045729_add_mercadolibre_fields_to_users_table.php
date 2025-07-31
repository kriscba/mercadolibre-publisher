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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('mercadolibre_id')->nullable()->unique();
            $table->string('mercadolibre_nickname')->nullable();
            $table->string('mercadolibre_password')->nullable();
            $table->string('mercadolibre_site_status')->nullable();
            $table->foreignId('mercadolibre_token_id')->nullable()->constrained('oauth_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mercadolibre_id',
                'mercadolibre_nickname',
                'mercadolibre_password',
                'mercadolibre_site_status',
                'mercadolibre_token_id'
            ]);
        });
    }
};
