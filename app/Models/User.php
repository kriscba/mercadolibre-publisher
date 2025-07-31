<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mercadolibre_id',
        'mercadolibre_nickname',
        'mercadolibre_password',
        'mercadolibre_site_status',
        'mercadolibre_token_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mercadolibre_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the MercadoLibre OAuth token for the user.
     */
    public function mercadolibreToken(): BelongsTo
    {
        return $this->belongsTo(OAuthToken::class, 'mercadolibre_token_id');
    }

    /**
     * Update user with MercadoLibre data
     *
     * @param array $mercadolibreData
     * @return void
     */
    public function updateMercadoLibreData(array $mercadolibreData): void
    {
        $this->update([
            'mercadolibre_id' => $mercadolibreData['id'] ?? null,
            'mercadolibre_nickname' => $mercadolibreData['nickname'] ?? null,
            'mercadolibre_password' => $mercadolibreData['password'] ?? null,
            'mercadolibre_site_status' => $mercadolibreData['site_status'] ?? null,
        ]);
    }

    /**
     * Update MercadoLibre OAuth token
     *
     * @param OAuthToken $token
     * @return void
     */
    public function updateMercadoLibreToken(OAuthToken $token): void
    {
        $this->update([
            'mercadolibre_token_id' => $token->id,
        ]);
    }

    /**
     * Check if MercadoLibre access token is valid
     *
     * @return bool
     */
    public function hasValidMercadoLibreToken(): bool
    {
        return $this->mercadolibreToken && 
               $this->mercadolibreToken->status === 'active' && 
               $this->mercadolibreToken->expires_at && 
               $this->mercadolibreToken->expires_at->isFuture();
    }

    /**
     * Get MercadoLibre access token
     *
     * @return string|null
     */
    public function getMercadoLibreAccessToken(): ?string
    {
        return $this->hasValidMercadoLibreToken() ? $this->mercadolibreToken->access_token : null;
    }

    /**
     * Get MercadoLibre OAuth token
     *
     * @return OAuthToken|null
     */
    public function getMercadoLibreOAuthToken(): ?OAuthToken
    {
        return $this->hasValidMercadoLibreToken() ? $this->mercadolibreToken : null;
    }

    public static function createFromOAuthResponse(array $userData, OAuthToken $token): self
    {
        return self::create([
            'name' => $userData['nickname'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'mercadolibre_id' => $userData['id'],
            'mercadolibre_nickname' => $userData['nickname'],
            'mercadolibre_password' => $userData['password'],
            'mercadolibre_site_status' => $userData['site_status'],
            'mercadolibre_token_id' => $token->id,
        ]);
    }
}
