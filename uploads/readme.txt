# FurEver Memories V2

This is the Version 2 PHP scaffold with added security features:
- CSRF tokens on forms
- email verification flow
- brute-force protection on login
- stricter audit logging
- client password change page
- message moderation page

## Install
1. Upload all files to your server
2. Run `composer install`
3. Import `database.sql`
4. Update `config/config.php`
5. Make sure `/uploads` is writable

## Default admin
- Email: `admin@furevermemories.com`
- Password: `Admin1234`

## Notes
- Email verification currently uses PHP `mail()` as a basic transport. On many shared hosts this may need SMTP/API replacement.
- If mail sending fails, the system shows a manual verification link after client creation/resend.
- QR image uses a remote QR image endpoint for convenience. If you want a fully local QR generator, you can swap it later with a PHP QR package.
