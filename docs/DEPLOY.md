# Deploy on DomainAdda cPanel → tstory.reddevils.co.in

Your panel (kaveri.domainadda.com:2083, user `reddevil`): confirmed tools — Subdomains/Domains, Manage My Databases + phpMyAdmin, Email Accounts, SSL/TLS, SSH Access, Git Version Control, File Manager, FTP Accounts, Setup Node.js App (not needed), MultiPHP Manager.

## 1. Create subdomain
1. cPanel → **Domains** (or Subdomains) → Create: subdomain `tstory`, domain `reddevils.co.in` → docroot `public_html/tstory`.
2. cPanel → **SSL/TLS Certificates** → AutoSSL / Let's Encrypt → include `tstory.reddevils.co.in`. Wait for Active.

## 2. Database
1. cPanel → **Manage My Databases** → create DB `reddevil_tstory` + user `reddevil_tstory` (all privileges), note password.
2. **phpMyAdmin** → select DB → Import `database/schema.sql`, then `database/seed.sql`.
3. Import order matters (schema first).

## 3. Upload code (pick one)

**Option A — Git Version Control (recommended, syncs with GitHub):**
1. Push this folder to `https://github.com/souravbrock/travel_stories` (see below).
2. cPanel → **Git Version Control** → Clone `https://github.com/souravbrock/travel_stories` into `/home/reddevil/travel_stories`.
3. SSH in: `ssh -i C:\Users\soura\.ssh\cpanel-deploy reddevil@kaveri.domainadda.com`
4. `cp -R ~/travel_stories/public/* ~/public_html/tstory/` and `cp ~/travel_stories/api/* ~/public_html/tstory/api/` (or keep api outside docroot and adjust `public/.htaccess`).

Simplest reliable layout on shared hosting: **everything under docroot**:
```
~/public_html/tstory/index.php, assets/, api/, config/ (with 640 perms), storage/
```
So: `cp -R ~/travel_stories/public/* ~/public_html/tstory/ && cp -R ~/travel_stories/api ~/public_html/tstory/ && mkdir -p ~/public_html/tstory/storage ~/public_html/tstory/uploads`

**Option B — File Manager/FTP:** zip repo, upload to `public_html/tstory`, extract.

## 4. Config
1. Copy `config/config.sample.php` → `config/config.php` (on server), fill: DB name/user/pass, `app_url=https://tstory.reddevils.co.in`, `AUTH_SECRET` (random 32+ chars), mail from `noreply@reddevils.co.in`.
2. cPanel → **Email Accounts** → `no-reply@tstory.reddevils.co.in` (registration + general mail) and `booking@tstory.reddevils.co.in` (all booking mail, incl. platform-booking leads) already exist. Passwords are with the site owner.
3. cPanel → **MultiPHP Manager** → set `tstory` to PHP 8.1/8.2.
4. Test: `https://tstory.reddevils.co.in/api/health.php` → `{"ok":true...}`.

## 5. GitHub sync (local, first push)
You have no `git` on PATH. Install Git for Windows, then:
```powershell
cd C:\Users\soura\travel_stories
git init; git add .; git commit -m "Travel Stories MVP"
git branch -M main; git remote add origin https://github.com/souravbrock/travel_stories.git
git push -u origin main
```
Then cPanel Git tool can **Pull** on each push, or add webhook. After pull, re-run the `cp` step (or automate via `.cpanel.yml`).

## 6. Troubleshooting
- Unstyled page / 403 on `/assets/*`: directories uploaded via scp default to `700`, which the web server user can't traverse. Fix: `find ~/public_html/tstory -type d -exec chmod 755 {} \;` and files `644` (keep `config/` + `storage/` at `750`, `config.php` at `640`).
- 500 + "config.php missing": you didn't copy sample → config on server.
- DB connect fail: check `DB_HOST=localhost` (DomainAdda shared uses localhost), user has privileges, password correct.
- OTP mail in spam: add SPF `v=spf1 +a +mx include:domainadda.com ~all` in **Zone Editor**, enable DKIM in **Email Deliverability**.
- Blank map polygons: datameet GeoJSON CDN blocked → state cards still work (fallback built in).
