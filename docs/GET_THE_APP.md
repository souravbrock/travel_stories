# Get the app without Google Play

## Option A — GitHub Releases (simplest)

1. Open https://github.com/souravbrock/travel_stories/releases
2. Download the latest `travel-stories-v*.apk`, install (allow
   "install unknown apps" once).

## Option B — Obtanium (auto-updates, recommended)

Obtanium tracks GitHub Releases and notifies you of new versions.

1. Install Obtanium (https://github.com/ImranR98/Obtainium).
2. Add app → paste `https://github.com/souravbrock/travel_stories`
   (Obtanium detects the release APKs automatically).
3. Install + enable update notifications. New tagged releases (`v*`) with a
   bumped `versionCode` appear as updates.

## Option C — F-Droid

Pending review: recipe draft is in `fdroid/`. Once accepted, the app appears
in the F-Droid client with automatic updates. Status will be linked here.

## For maintainers: cutting a release

1. Bump `versionCode` (+1) and `versionName` in
   `mobile/android/app/build.gradle` (required — Android + Obtanium detect
   updates via versionCode).
2. Commit + push, then `git tag vX.Y && git push origin vX.Y`.
3. GitHub Actions (`.github/workflows/android.yml`) builds the APK and
   attaches it to the Release automatically.
