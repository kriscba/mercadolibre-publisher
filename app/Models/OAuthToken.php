<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_tokens';
    
    protected $fillable = [
        'user_id',
        'client_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'expires_at',
        'scope',
        'grant_type',
        'redirect_uri',
        'code_verifier',
        'status',
        'last_used_at',
    ];

    protected $casts = [
        'scope' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'code_verifier',
    ];

    /**
     * Encrypt access token before saving
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt access token when retrieving
     */
    public function getAccessTokenAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    /**
     * Encrypt refresh token before saving
     */
    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['refresh_token'] = null;
        }
    }

    /**
     * Decrypt refresh token when retrieving
     */
    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isPast();
    }

    /**
     * Check if token is valid (not expired and active)
     */
    public function isValid(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke token
     */
    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }

    /**
     * Mark token as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Get active token for user and client
     */
    public static function getActiveToken(string $clientId, ?string $userId = null): ?self
    {
        $query = self::where('client_id', $clientId)
            ->where('status', 'active')
            ->where('expires_at', '>', now());

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->first();
    }

    /**
     * Create or update token from OAuth response
     */
    public static function createFromOAuthResponse(array $response): self
    {

        $grant_type = config('services.mercadolibre.grant_type');
        $client_id = config('services.mercadolibre.client_id');
        $client_secret = config('services.mercadolibre.client_secret');
        $redirect_uri = config('services.mercadolibre.redirect_uri');
        $app_code = config('services.mercadolibre.app_code');

        // Calculate expiration time
        $expiresAt = null;
        if (isset($response['expires_in'])) {
            $expiresAt = now()->addSeconds($response['expires_in']);
        }

        // Check if token already exists for this client/user
        $existingToken = self::where('client_id', $client_id)
            ->when(isset($response['user_id']), function ($query) use ($response) {
                return $query->where('user_id', $response['user_id']);
            })
            ->first();

        if ($existingToken) {
            // Update existing token
            $existingToken->update([
                'access_token' => $response['access_token'],
                'refresh_token' => $response['refresh_token'] ?? null,
                'token_type' => $response['token_type'] ?? 'Bearer',
                'expires_in' => $response['expires_in'] ?? null,
                'expires_at' => $expiresAt,
                'scope' => $response['scope'] ?? null,
                'status' => 'active',
                'last_used_at' => now(),
            ]);

            return $existingToken->fresh();
        }

        // Create new token
        return self::create([
            'user_id' => $response['user_id'] ?? null,
            'client_id' => $client_id,
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'token_type' => $response['token_type'] ?? 'Bearer',
            'expires_in' => $response['expires_in'] ?? null,
            'expires_at' => $expiresAt,
            'scope' => $response['scope'] ?? null,
            'grant_type' => $response['grant_type'] ?? 'authorization_code',
            'redirect_uri' => $redirect_uri,
            'code_verifier' => $app_code,
            'status' => 'active',
            'last_used_at' => now(),
        ]);
    }

}
