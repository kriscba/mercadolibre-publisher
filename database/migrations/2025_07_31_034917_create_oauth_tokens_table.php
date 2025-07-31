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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable()->index(); // MercadoLibre user ID
            $table->string('client_id')->index(); // MercadoLibre app client ID
            $table->text('access_token'); // Encrypted access token
            $table->text('refresh_token')->nullable(); // Encrypted refresh token
            $table->string('token_type')->default('Bearer');
            $table->integer('expires_in')->nullable(); // Token expiration in seconds
            $table->timestamp('expires_at')->nullable(); // Calculated expiration timestamp
            $table->json('scope')->nullable(); // Token scopes
            $table->string('grant_type')->default('authorization_code');
            $table->string('redirect_uri')->nullable();
            $table->string('code_verifier')->nullable(); // PKCE code verifier
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
