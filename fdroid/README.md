# F-Droid submission pack

F-Droid builds and signs apps from source. This folder contains everything for
the submission merge request to
https://gitlab.com/fdroid/fdroiddata (new app procedure).

## 1. What we provide

- `com.reddevils.tstory.yml` — ready recipe draft (F-Droid builds the
  Capacitor/Gradle project in `mobile/` from the git tag).
- `../fastlane/metadata/android/en-US/` — title + descriptions (F-Droid picks
  these up automatically via Fastlane/Triple-T structure).

## 2. Submit (maintainer, needs a GitLab account)

1. Fork `fdroiddata`, copy `com.reddevils.tstory.yml` to `metadata/`.
2. Open a merge request titled e.g. `New App: Travel Stories`.
3. F-Droid reviewers build it on their infrastructure; respond to feedback
   (common ask: versionCode bump per release, changelog file).

## 3. Per-release checklist (so F-Droid + Obtanium keep working)

1. Bump `versionCode` (+1) and `versionName` in
   `mobile/android/app/build.gradle`. Both stores key off versionCode.
2. Commit, push, tag: `git tag vX.Y && git push origin vX.Y`.
3. GitHub Actions builds the APK and attaches it to the Release —
   Obtanium picks it up automatically, no action needed.

## Notes / honest caveats

- The app is a WebView shell over our own site (source in this repo under
  MIT). Reviewers may apply the `NonFreeNet`-style scrutiny for service
  dependence — approval is their call.
- Debug APKs on GitHub Releases are signed with the standard debug key and
  install fine for direct distribution; F-Droid re-signs with its own keys.
