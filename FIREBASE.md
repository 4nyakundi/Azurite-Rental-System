# Firebase deployment

The cloud application lives in `firebase-app/` and deploys to **https://airbnb-sys.web.app**. The PHP/MariaDB application remains in `public/`, `includes/` and `database/` for local use.

## Runtime

- Firebase Hosting serves the bundled web application. No PHP files or private uploads are included in the deployment.
- Firebase Authentication handles email/password accounts, password resets and email verification.
- Cloud Firestore stores profiles, properties, bookings, availability and immutable booking events. The default database is in `africa-south1` (Johannesburg), using the free tier.
- Firebase Storage integration is implemented but **disabled** because this project has no billing account or bucket. Registration and booking do not require an identity upload while it is disabled.
- Analytics configuration is retained, but Analytics is not initialized automatically.

Old local usernames, passwords, bookings and private files are not imported. Cloud users create new accounts with email addresses. The local database's recovery limitation is documented in README.md.

## Administrator onboarding

The Google account used by the Firebase CLI has a private administrator invitation in Firestore. Register in the app using that same email, open the verification email, then select **I have verified my email** in Profile, or sign out and sign in again. A verified invited account can claim the administrator role. Other accounts cannot promote themselves.

No shared administrator password is deployed. Hosts may register their own accounts and manage their own properties. Public sample properties use a system owner and can be managed by the administrator.

## Development and checks

Use Node 22.12 or later, Firebase CLI and Java 21 or later.

```powershell
npm ci
npm run dev
npm run build
```

The development app opens at `http://127.0.0.1:5173`. To use emulators, set `VITE_USE_EMULATORS=true` before starting Vite and start the emulators:

```powershell
firebase emulators:start --only firestore,auth,storage --project demo-azurite
```

Run all security-rule tests with the emulators stopped first:

```powershell
npm run test:rules
```

Or, with the emulators already running:

```powershell
node --test tests/firebase-rules.test.mjs
```

These tests use `demo-azurite`, not production. They cover simultaneous reservations, overlapping dates, adjacent stays, forged prices, blocked nights, status changes, audit records, role escalation and document access.

## Booking integrity

Stays support 1–30 nights and up to four guests. Check-out is exclusive. Every reservation atomically updates a per-property availability ledger, creates the private booking and records an event. Firestore rules validate contiguous night keys, the property's actual price, caller ownership and the matching ledger/event writes.

Cancellation and completion release all nights in the same transaction. Terminal bookings cannot be reactivated. Hosts can block free nights and unblock their own blocks; they cannot overwrite a reservation. Public ledgers contain only occupied day numbers and the most recent opaque booking reference. Guest names, email addresses and phone numbers remain in private booking documents.

`tools/build-firebase-rules.py` generates the bounded 30-day rule checks because Firestore rules do not support loops. Run it after editing that source, then rerun the tests. The committed `firestore.rules` is what Firebase deploys.

## Publish

```powershell
npm run build
firebase deploy --only hosting,firestore --project airbnb-sys
```

`tools/firebase-project.mjs` uses the existing Firebase CLI login for setup and validation. It keeps credentials in memory, and never writes service-account keys or access tokens. It supports `inspect`, `seed`, `auth-config` and `smoke-auth`. Seeding adds only missing public sample records and never overwrites existing properties or bookings. `smoke-auth` creates and removes one disposable test identity.

Only the `dist/` bundle is served. Keep `node_modules/`, `.firebase/`, local configuration, runtime files and debug logs out of Git.

## Enable uploads later

1. Enable Blaze billing yourself in Firebase Console, then create the default bucket `airbnb-sys.firebasestorage.app`.
2. Deploy the prepared Storage rules: `firebase deploy --only storage --project airbnb-sys`.
3. Configure the bucket's CORS to allow GET requests from the app's two Hosting domains when using private document downloads.
4. In Firestore, set `settings/public.storageEnabled` to `true`.

Uploads accept JPG/PNG photos and JPG/PNG/PDF identity documents, up to 5 MB. Public property images use `photos/{uid}/…`; identity documents use `private/{uid}/…`. Private files are downloaded through the authenticated Storage SDK rather than a public download URL. Owners, administrators and hosts with a valid booking access grant can read identity files.

Do not enable uploads with permissive test-mode rules. No billing configuration is changed by this repository's deploy command.
