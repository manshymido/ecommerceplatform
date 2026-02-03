# Testing Guide - Phase 1: User & Identity

## Postman Collection (Recommended)

The easiest way to test APIs is using the **Postman collection**:

1. Import `postman/Ecommerce_API.postman_collection.json` and `postman/Ecommerce_API.postman_environment.json` into Postman
2. Select the "Ecommerce API - Local" environment
3. Run the "Login" request - it will automatically save your token
4. All other requests will use the saved token automatically

See `postman/README.md` for detailed setup instructions.

## Test Users Created

The following test users have been seeded:

- **Admin**: `admin@test.com` / `password` (has `admin` role)
- **Customer**: `customer@test.com` / `password` (has `customer` role)
- **Super Admin**: `superadmin@test.com` / `password` (has `super_admin` role)

## Testing Steps

### 1. Start the Laravel Server

```bash
php artisan serve
```

The server will run at `http://localhost:8000`

### 2. Test API Login (Sanctum)

**Login as Admin:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'
```

You should receive a response with a `token` and `user` object.

**Login as Customer:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@test.com","password":"password"}'
```

### 3. Test Protected User Endpoint

Use the token from step 2:

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 4. Test Admin Dashboard (API)

**As Admin (should work):**
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE" \
  -H "Accept: application/json"
```

**As Customer (should fail with 403):**
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer CUSTOMER_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 5. Test Web Admin Route

Visit in browser (requires web session auth):
- `http://localhost:8000/admin/dashboard`

You'll need to log in via web session first (you can create a simple login form or use Tinker).

### 6. Test with Tinker

```bash
php artisan tinker
```

Then:
```php
// Get admin user
$admin = User::where('email', 'admin@test.com')->first();

// Check roles
$admin->roles; // Should show 'admin' role

// Check if has role
$admin->hasRole('admin'); // Should return true
$admin->hasRole('customer'); // Should return false

// Create a token
$token = $admin->createToken('test-token')->plainTextToken;
echo $token; // Use this token in API calls
```

## Expected Results

✅ **Login endpoint** should return token and user data  
✅ **Protected /api/user** should return authenticated user  
✅ **Admin dashboard (API)** should work for admin/super_admin, fail for customer  
✅ **Roles** should be properly assigned and checked  
✅ **Sanctum tokens** should authenticate API requests  

## Troubleshooting

- If routes not found: Run `php artisan route:clear && php artisan route:cache`
- If permission errors: Make sure migrations ran: `php artisan migrate`
- If roles not working: Re-seed roles: `php artisan db:seed --class=RoleSeeder`
