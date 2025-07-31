# OAuth Token Storage Solution

This document describes the database-based OAuth token storage implementation for the MercadoLibre OAuth integration.

## Why Database Storage?

### Advantages over Session Storage:
- ✅ **Persistence**: Tokens survive server restarts and user sessions
- ✅ **Security**: Encrypted storage with proper access control
- ✅ **Scalability**: Works across multiple servers/instances
- ✅ **Audit Trail**: Complete history of token usage
- ✅ **Refresh Management**: Easy token refresh cycle handling
- ✅ **Multi-user Support**: Store tokens for multiple users/clients

## Database Schema

### OAuth Tokens Table

```sql
CREATE TABLE oauth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NULL,                    -- MercadoLibre user ID
    client_id VARCHAR(255) NOT NULL,              -- MercadoLibre app client ID
    access_token TEXT NOT NULL,                   -- Encrypted access token
    refresh_token TEXT NULL,                      -- Encrypted refresh token
    token_type VARCHAR(255) DEFAULT 'Bearer',
    expires_in INT NULL,                          -- Token expiration in seconds
    expires_at TIMESTAMP NULL,                    -- Calculated expiration timestamp
    scope JSON NULL,                              -- Token scopes
    grant_type VARCHAR(255) DEFAULT 'authorization_code',
    redirect_uri VARCHAR(255) NULL,
    code_verifier VARCHAR(255) NULL,              -- PKCE code verifier
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_client_id (client_id),
    INDEX idx_user_id (user_id),
    INDEX idx_client_status (client_id, status),
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires_at (expires_at)
);
```

## Security Features

### 1. **Encryption**
- Access tokens are encrypted using Laravel's `Crypt` facade
- Refresh tokens are encrypted before storage
- Automatic encryption/decryption via model accessors

### 2. **Token Status Management**
- `active`: Valid and usable tokens
- `expired`: Tokens that have passed their expiration
- `revoked`: Manually revoked tokens

### 3. **Automatic Expiration**
- Calculated expiration timestamps
- Automatic status updates
- Cleanup functionality for expired tokens

## API Endpoints

### Token Exchange & Storage
```http
POST /oauth/exchange-token
POST /oauth/exchange-token-direct
```

**Response includes:**
```json
{
  "success": true,
  "message": "Token exchanged and saved successfully",
  "token_id": 1,
  "user_id": "123456789",
  "expires_at": "2024-01-15T10:30:00Z",
  "status": "active",
  "oauth_response": { ... }
}
```

### Token Management
```http
GET /oauth/tokens                    # List all tokens (with filters)
GET /oauth/tokens/{id}              # Get specific token
DELETE /oauth/tokens/{id}           # Revoke specific token
POST /oauth/tokens/cleanup          # Cleanup expired tokens
```

### Query Parameters for Filtering
- `client_id`: Filter by MercadoLibre app client ID
- `user_id`: Filter by MercadoLibre user ID  
- `status`: Filter by token status (active, expired, revoked)

## Model Features

### OAuthToken Model Methods

```php
// Check if token is expired
$token->isExpired();

// Check if token is valid (not expired and active)
$token->isValid();

// Mark token as used
$token->markAsUsed();

// Revoke token
$token->revoke();

// Mark token as expired
$token->markAsExpired();

// Get active token for client/user
OAuthToken::getActiveToken($clientId, $userId);

// Create/update token from OAuth response
OAuthToken::createFromOAuthResponse($response, $requestData);
```

### Model Scopes
```php
// Get only active tokens
OAuthToken::active()->get();

// Get only valid (not expired) tokens
OAuthToken::valid()->get();

// Get expired tokens
OAuthToken::expired()->get();
```

## Web Interfaces

### 1. OAuth Test Interface
- **URL**: `GET /oauth/test`
- **Purpose**: Test OAuth token exchange
- **Features**: Form-based testing with both service and direct cURL methods

### 2. Token Management Interface
- **URL**: `GET /oauth/manage`
- **Purpose**: Manage stored tokens
- **Features**: 
  - View all tokens with filtering
  - Token statistics
  - Revoke tokens
  - Cleanup expired tokens
  - Detailed token information

## Usage Examples

### 1. Exchange and Store Token
```php
// Via API
$response = Http::post('/oauth/exchange-token', [
    'grant_type' => 'authorization_code',
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'code' => 'authorization-code',
    'redirect_uri' => 'your-redirect-uri',
    'code_verifier' => 'pkce-code-verifier'
]);

// Via Model
$token = OAuthToken::createFromOAuthResponse($oauthResponse, $requestData);
```

### 2. Retrieve Active Token
```php
$token = OAuthToken::getActiveToken('client-id', 'user-id');
if ($token && $token->isValid()) {
    $accessToken = $token->access_token;
    $token->markAsUsed();
}
```

### 3. Cleanup Expired Tokens
```php
// Via API
Http::post('/oauth/tokens/cleanup');

// Via Model
$expiredTokens = OAuthToken::expired()->get();
foreach ($expiredTokens as $token) {
    $token->markAsExpired();
}
```

## Migration and Setup

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Verify Table Creation
```bash
php artisan tinker
>>> Schema::hasTable('oauth_tokens')
```

## Best Practices

### 1. **Token Security**
- Always use HTTPS in production
- Regularly rotate client secrets
- Implement proper access controls
- Monitor token usage patterns

### 2. **Token Management**
- Set up scheduled cleanup of expired tokens
- Monitor token expiration dates
- Implement token refresh logic
- Log token usage for audit trails

### 3. **Performance**
- Use database indexes for frequent queries
- Implement caching for frequently accessed tokens
- Monitor database performance
- Clean up old tokens regularly

### 4. **Error Handling**
- Handle token expiration gracefully
- Implement retry logic for failed requests
- Log all OAuth-related errors
- Provide clear error messages

## Scheduled Tasks

### Cleanup Expired Tokens
Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup expired tokens daily
    $schedule->call(function () {
        $expiredTokens = OAuthToken::expired()->get();
        foreach ($expiredTokens as $token) {
            $token->markAsExpired();
        }
    })->daily();
}
```

## Monitoring and Logging

### 1. **Token Usage Logging**
```php
Log::info('OAuth token used', [
    'token_id' => $token->id,
    'client_id' => $token->client_id,
    'user_id' => $token->user_id,
    'usage_count' => $token->usage_count
]);
```

### 2. **Error Monitoring**
```php
Log::error('OAuth token error', [
    'error' => $e->getMessage(),
    'token_id' => $token->id ?? null,
    'client_id' => $request->input('client_id')
]);
```

## Troubleshooting

### Common Issues

1. **Token Not Found**
   - Check if token exists in database
   - Verify token status is 'active'
   - Check if token has expired

2. **Encryption Errors**
   - Ensure APP_KEY is set in .env
   - Check if encryption keys are consistent
   - Verify database connection

3. **Performance Issues**
   - Check database indexes
   - Monitor query performance
   - Consider implementing caching

## File Structure

```
app/
├── Models/
│   └── OAuthToken.php              # Token model with encryption
├── Http/Controllers/
│   └── OAuthController.php         # Updated with storage methods
├── Services/
│   └── MercadoLibreOAuthService.php # OAuth service
database/migrations/
└── create_oauth_tokens_table.php   # Database migration
resources/views/
├── oauth-test.blade.php           # OAuth testing interface
└── token-management.blade.php     # Token management interface
routes/
└── web.php                        # Updated routes
docs/
└── token-storage.md               # This documentation
```

This database-based token storage solution provides a robust, secure, and scalable way to manage OAuth tokens for your MercadoLibre integration. 