# Travel Stories — tstory.reddevils.co.in

Responsive web app + PWA (wrappable as Android app via Capacitor) + PHP/MySQL API, designed for DomainAdda shared cPanel hosting.

Live target: `https://tstory.reddevils.co.in`
GitHub: `https://github.com/souravbrock/travel_stories`
cPanel host: `kaveri.domainadda.com` (user `reddevil`, primary domain `reddevils.co.in`)

## Stack (chosen for your cPanel)

- **Frontend:** vanilla JS SPA + Leaflet (no build step, works on plain Apache). PWA-ready.
- **Backend:** PHP 8.1+ + MySQL (PDO). No Node daemon needed — safest on shared hosting.
  - Your cPanel *does* have "Setup Node.js App", but PHP is more reliable on DomainAdda shared plans with limited processes/memory (your panel shows 1GB RAM, 20 entry processes).
- **Auth:** name + email + phone → 5-digit email OTP → set password. Roles: `customer`, `vendor`, `admin`.
- **Maps:** Level 1 India states GeoJSON, Level 2 district drill-down (seeded West Bengal districts + list fallback for other states), spot distance matrix via Haversine (AC1–AC4).

## Repo layout

```
public/              ← subdomain document root (point tstory.reddevils.co.in here)
  index.php          ← front controller + router include
  .htaccess          ← pretty URLs + caching + HTTPS force (optional)
  assets/css/app.css
  assets/js/app.js   ← SPA, map, builder, auth, vendor/admin panels
  manifest.webmanifest
  data/india-states.geojson  (lightweight; full file fetched from CDN at runtime)
api/                 ← PHP JSON API (called as /api/<name>.php)
config/config.php    ← DB + SMTP + app settings (copy from config.sample.php)
database/schema.sql  ← all tables
database/seed.sql    ← demo states/spots/vehicles/admin
.cpanel.yml          ← auto-deploy on cPanel Git pull
scripts/deploy.sh    ← SSH deploy helper
docs/DEPLOY.md       ← subdomain + DB + Git + SSL steps for DomainAdda
docs/ANDROID.md      ← wrap PWA as Android app
```

## Quick start (local preview, no PHP needed for UI)

You have Node 24 locally. This previews the frontend only (API mocked):

```powershell
cd C:\Users\soura\travel_stories\public
npx serve -l 8080
# open http://localhost:8080
```

Full stack needs Apache+PHP+MySQL — use cPanel or XAMPP.

## First-time setup on cPanel — summary

Full guide: `docs/DEPLOY.md`. TL;DR:

1. cPanel → Subdomains → create `tstory` for `reddevils.co.in` (docroot `public_html/tstory`).
2. cPanel → Manage My Databases → create `reddevil_tstory`, user, import `database/schema.sql` + `seed.sql`.
3. cPanel → Git Version Control → clone `https://github.com/souravbrock/travel_stories`, or upload via File Manager.
4. Copy `api/`, `public/*`, `config/` per `docs/DEPLOY.md`, edit `config/config.php` with DB + SMTP creds.
5. cPanel → SSL/TLS → AutoSSL for subdomain. cPanel → Email Accounts → create `noreply@reddevils.co.in` for OTP mail.
6. Test: `https://tstory.reddevils.co.in/api/health.php`

Default admin: register `admin@reddevils.co.in` via the site, then run `UPDATE users SET role='admin' WHERE email='admin@reddevils.co.in';` in phpMyAdmin.

## Auth flow (as specified)

1. `Register`: name, email, phone → server creates unverified user + 5-digit OTP (15 min expiry), sends email.
2. `Verify`: user enters OTP → marked verified.
3. `Set password`: user sets password → account active, JWT-less token session.
4. All routes except landing/auth preview require login (frontend gate + API 401).

Dev fallback: if SMTP not configured, OTP is written to `storage/otp.log` and (dev only) returned in API response.

## Roles

| Role | Access |
|---|---|
| customer | map, packages, builder, bookings, inquiries |
| vendor (travel_agent/hotel/homestay/transport/ticketing) | dashboard to enlist packages, vehicles, properties + photos |
| admin | full CRUD: spots, hotels, homestays, agents, cars, transport companies, users |

## Android app

The web app is a PWA. To ship Android: see `docs/ANDROID.md` (Capacitor wrapper, 15 min). No separate codebase needed for v1.
