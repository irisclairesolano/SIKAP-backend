# SIKAP Production Deployment Checklist

This document tracks all the local development shortcuts, bypasses, and configurations we've used during development. **Before launching the app to production**, you must complete the items on this list.

## 1. Database Configuration
We temporarily switched from Supabase (PostgreSQL) to a local SQLite database for easier local development.
- [ ] In your production server's `.env` file, change `DB_CONNECTION` from `sqlite` to `pgsql`.
- [ ] Provide your live Supabase database credentials (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- [ ] Run `php artisan migrate --force` and `php artisan db:seed --force` on your production server.

## 2. User Approval & Admin Dashboard
During local development, we added a shortcut in `AuthController::login` that automatically approves pending accounts if `app.env === 'local'`.
- [ ] **Important:** This auto-approval shortcut will automatically disable itself in production (when `APP_ENV=production`).
- [ ] **Action Required:** Because it disables itself, you **must** build the Admin Dashboard feature so your admins have a way to manually view uploaded IDs and click "Approve" or "Reject". Without the Admin Dashboard, users will be permanently stuck on the "Pending Verification" screen in production.

## 3. Email Sending (OTP)
- [x] **Already Completed!** You have successfully configured Resend as your primary email provider in the `.env` file (`MAIL_MAILER=resend`, using `noreply@auth.sikap.xyz`). No further action needed here!

## 4. Frontend API URL
Your Expo React Native app is currently pointed to your local computer's IP address.
- [ ] In the `sikap` frontend folder, update the `.env` file (or your EAS Build secrets).
- [ ] Change `EXPO_PUBLIC_API_URL` to point to your live backend domain (e.g., `https://api.sikap-app.com/api/v1`).

## 5. Environment Variables
Ensure the backend production `.env` has the following set properly:
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://api.sikap-app.com`
- [ ] Ensure Supabase Storage buckets (for ID and selfie uploads) are properly configured and the `.env` variables for Supabase are set.

## 6. Social Authentication (Pending Feature)
- [ ] If Google/Facebook login is implemented, ensure you swap out the development Client IDs and Secrets for the production ones in the `.env` file. Add the live OAuth redirect URIs to the Google Cloud / Facebook Developer consoles.
