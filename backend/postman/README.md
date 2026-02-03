# Postman Collections for Ecommerce API

This directory contains Postman collections and environments for testing the Ecommerce API.

## Files

- **Ecommerce_API.postman_collection.json** - Main API collection with all endpoints
- **Ecommerce_API.postman_environment.json** - Environment variables for local development

## Setup Instructions

### 1. Import Collection

1. Open Postman
2. Click **Import** button
3. Select `Ecommerce_API.postman_collection.json`
4. Select `Ecommerce_API.postman_environment.json`
5. Click **Import**

### 2. Select Environment

1. In Postman, select the **Ecommerce API - Local** environment from the dropdown (top right)
2. Verify `base_url` is set to `http://localhost:8000` (or your server URL)

### 3. Start Testing

1. **Login First**: Run the "Login" request in the Authentication folder
   - This will automatically save the token to the environment variable `auth_token`
   - Use `admin@test.com` / `password` for admin access
   - Use `customer@test.com` / `password` for customer access

2. **Test Protected Endpoints**: 
   - The collection uses Bearer token authentication
   - Token is automatically set from the login response
   - All protected endpoints will use `{{auth_token}}` from environment

## Test Users

- **Admin**: `admin@test.com` / `password` (has admin role)
- **Customer**: `customer@test.com` / `password` (has customer role)
- **Super Admin**: `superadmin@test.com` / `password` (has super_admin role)

## Collection Structure

- **Authentication** - Login endpoints
- **User** - User-related endpoints
- **Admin** - Admin-only endpoints (requires admin/super_admin role)

## Adding New Endpoints

When adding new API endpoints:

1. Add the request to the appropriate folder in the collection
2. Use environment variables for dynamic values (tokens, IDs, etc.)
3. Add test scripts if needed (e.g., to extract and save tokens)
4. Update this README if adding new folders or major features

## Environment Variables

- `base_url` - API base URL (default: http://localhost:8000)
- `auth_token` - Authentication token (auto-set after login)
- `user_id` - Current user ID (auto-set after login)
- `admin_email` / `admin_password` - Admin credentials
- `customer_email` / `customer_password` - Customer credentials

## Notes

- The Login request includes a test script that automatically saves the token
- All protected endpoints use Bearer token authentication
- Admin endpoints require the user to have `admin` or `super_admin` role
- Update the collection as new endpoints are added to the API
