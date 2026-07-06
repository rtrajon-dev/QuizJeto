## Session 403 Error Fix — cPanel Deployment Guide

### What Was Fixed

The 403 "not_registered" error occurred because **PHP sessions weren't persisting** between requests on cPanel shared hosting.

#### Root Cause

- Default cPanel PHP session configuration sometimes fails to maintain session data across requests
- Session files may be stored in a system temp directory with permissions issues

#### Solution Implemented

All PHP files now include **automatic session directory configuration**:

```php
// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) {
        @mkdir(__DIR__ . '/sessions', 0755, true);
    }
}
session_start();
```

### Files Updated

✅ `index.php` - Home page & registration  
✅ `quiz.php` - Quiz page  
✅ `quiz_api.php` - Quiz API  
✅ `account.php` - User account  
✅ `register_user.php` - Session registration  
✅ `logout.php` - Logout  
✅ `bdapps/verify_otp.php` - OTP verification  
✅ `bdapps/unsubscribe.php` - Unsubscribe API  
✅ `bdapps/check_subscription.php` - Subscription check

### New Assets Created

- `sessions/` - Directory for storing session files locally
- `sessions/.htaccess` - Security rules to block web access to session files
- `sessions/.gitkeep` - Placeholder to track the directory in git

### Deployment Steps on cPanel

1. **Upload the updated project**

   ```bash
   git push origin main
   # Then pull/deploy on cPanel
   ```

2. **Set directory permissions** (via cPanel File Manager)

   ```
   sessions/ → 755 (read, write, execute for owner; read/execute for others)
   ```

3. **Verify PHP version** (cPanel > PHP Selector)
   - Requires PHP 7.0+ (your app uses 7.4+)
   - Verify `session.use_cookies` is enabled

4. **Test**
   - User registers with phone number
   - OTP is verified
   - User enters name and clicks "কুইজ শুরু করুন"
   - Should see quiz, NOT 403 error

### If 403 Still Occurs After Deployment

**Check the following in cPanel:**

#### File Permissions

```bash
# Via SSH (if available):
chmod -R 755 sessions/
chmod 644 sessions/.htaccess
```

#### PHP Configuration

- Go to **cPanel > PHP Configuration Editor**
- Ensure `session.auto_start = Off` (default)
- Check that `open_basedir` doesn't restrict access to the `sessions/` directory

#### Session Handler

- Verify `session.save_handler = files` (default)
- Verify `session.save_path = /path/to/sessions` (our code sets this)

#### Clear Old Sessions

```bash
# Via SSH or File Manager:
# Delete all files in sessions/ directory
rm sessions/*
```

#### Database Connection

Verify `db.php` can connect to the SQLite database:

```bash
chmod 644 database/schema.sql
chmod 644 database/seed.sql
chmod 755 database/
```

### Debugging

If issues persist, add temporary logging to `quiz_api.php`:

```php
error_log('Session phone: ' . ($_SESSION['phone'] ?? 'NOT SET'));
error_log('Session save path: ' . ini_get('session.save_path'));
error_log('Session ID: ' . session_id());
```

### Production Notes

- Session files are stored locally in `sessions/` (not in system temp)
- Session timeout follows PHP's default (usually 24 minutes of inactivity)
- Automatic directory creation with `@mkdir()` if it doesn't exist
- `.htaccess` prevents direct web access to session files for security

---

**Last Updated:** July 6, 2026
