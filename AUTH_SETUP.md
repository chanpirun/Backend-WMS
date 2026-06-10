# Backend Authentication Setup Guide

This guide covers the authentication system setup for the Laravel backend.

## Changes Made

### 1. User Model Update
**File:** `app/Models/User.php`

Added `role` field to the mass assignable attributes:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role', // Added
];
```

### 2. Database Migration
**File:** `database/migrations/0001_01_01_000000_create_users_table.php`

Added `role` column to users table:
```php
$table->string('role')->default('assistant'); // 'assistant' or 'director'
```

### 3. API Routes
**File:** `routes/api.php`

Added authentication endpoints:
- `POST /api/login` - Authenticate user
- `POST /api/register` - Register new user
- `POST /api/logout` - Logout user
- `GET /api/me` - Get current user info

### 4. Database Seeder
**File:** `database/seeders/DatabaseSeeder.php`

Created test users:
- Assistant: `assistant@example.com` (password: `password123`)
- Director: `director@example.com` (password: `password123`)

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate:fresh
```

This will:
- Create the users table with the new `role` field
- Create other default tables

### 2. Seed the Database
```bash
php artisan db:seed
```

This will create test users with different roles.

### 3. Verify Setup
Check that test users exist:
```bash
php artisan tinker
```

Then in tinker:
```php
App\Models\User::all();
```

## API Endpoints

### Login
```
POST /api/login
Content-Type: application/json

{
  "email": "assistant@example.com",
  "password": "password123"
}

Response:
{
  "user": {
    "id": 1,
    "name": "Admin Assistant",
    "email": "assistant@example.com",
    "role": "assistant",
    ...
  },
  "token": "..."
}
```

### Register
```
POST /api/register
Content-Type: application/json

{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "password123",
  "role": "assistant" // or "director"
}
```

### Logout
```
POST /api/logout
Authorization: Bearer {token}
```

### Get Current User
```
GET /api/me
Authorization: Bearer {token}
```

## Security Notes

⚠️ **Important for Production:**

1. **Enable CORS** for your frontend domain:
   ```php
   // config/cors.php
   'allowed_origins' => ['http://localhost:3000', 'your-domain.com'],
   ```

2. **Use HTTPS** in production

3. **Implement JWT Tokens** instead of session-based auth
   - Consider using `tymon/jwt-auth` package
   - Install: `composer require tymon/jwt-auth`

4. **Hash Passwords** - Already done via Laravel's `Hash::make()`

5. **Validate Input** - Add proper validation rules

6. **Rate Limiting** - Add login attempt rate limiting

7. **Environment Variables** - Store sensitive data in `.env`

## Creating Additional Users via Artisan

```bash
php artisan tinker

# Create a new user
$user = App\Models\User::create([
    'name' => 'User Name',
    'email' => 'user@example.com',
    'password' => bcrypt('password123'),
    'role' => 'director' // or 'assistant'
]);
```

## User Roles

### Assistant
- Can access basic admin dashboard
- Limited permissions
- Cannot manage users or system settings

### Director
- Full admin access
- Can manage users
- Can access system settings
- Can view audit logs

## CORS Configuration

The frontend at `http://localhost:3000` needs to communicate with the API. Ensure CORS is properly configured in `config/cors.php`:

```php
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

## Testing

Test the login endpoint:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "assistant@example.com",
    "password": "password123"
  }'
```

## Troubleshooting

### Users table doesn't have role column
- Run migrations: `php artisan migrate:fresh`
- Re-seed: `php artisan db:seed`

### Test users not found
- Run seeder: `php artisan db:seed`
- Or create manually via tinker

### Login returns 401
- Verify credentials in database
- Check password is correct
- Ensure user exists

### CORS errors
- Configure CORS in backend
- Ensure frontend URL is in allowed_origins

## Next Steps

1. Implement JWT token-based authentication
2. Add email verification
3. Implement password reset
4. Add two-factor authentication
5. Set up logging and monitoring
6. Create role-based policies for resources

## Files Modified

- `app/Models/User.php` - Added role field
- `database/migrations/0001_01_01_000000_create_users_table.php` - Added role column
- `routes/api.php` - Added auth endpoints
- `database/seeders/DatabaseSeeder.php` - Added test users

## Support

For Laravel documentation: https://laravel.com/docs
For authentication best practices: https://owasp.org/www-community/attacks/authentication_cheat_sheet
