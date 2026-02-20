# Security Best Practices

## SQL Injection Protection

✅ **Always use Eloquent or Query Builder** - Never use raw queries with user input
✅ **Use parameter binding** if raw queries are necessary

### Good (Protected):
```php
// Eloquent
User::where('email', $request->email)->first();

// Query Builder
DB::table('users')->where('email', $request->email)->first();

// Raw query with binding
DB::select('SELECT * FROM users WHERE email = ?', [$request->email]);
```

### Bad (Vulnerable):
```php
// NEVER DO THIS
DB::select("SELECT * FROM users WHERE email = '{$request->email}'");
```

## XSS Protection

✅ Blade automatically escapes output with `{{ }}`
✅ Vue.js automatically escapes data binding

### Good:
```blade
{{ $user->name }}  <!-- Escaped -->
```

### Bad:
```blade
{!! $user->name !!}  <!-- Not escaped - only use for trusted HTML -->
```

## CSRF Protection

✅ All POST/PUT/DELETE requests require CSRF token
✅ Inertia.js handles this automatically

## Authentication

✅ Passwords are hashed with bcrypt
✅ Session data is encrypted
✅ Remember tokens are secure

## File Upload Security

When implementing file uploads:
- ✅ Validate file types
- ✅ Limit file sizes
- ✅ Store outside public directory
- ✅ Generate random filenames
- ✅ Scan for malware

## Environment Variables

✅ Never commit `.env` file
✅ Keep secrets in `.env`
✅ Use strong database passwords
✅ Rotate API keys regularly

## Headers Security

Recommended security headers (configure in web server):
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
