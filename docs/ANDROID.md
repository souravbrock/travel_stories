# Android app (Capacitor wrapper — no rewrite)

The site is a responsive PWA, so Android v1 = native shell around `https://tstory.reddevils.co.in`.

1. `npm i -g @capacitor/cli; npm init -y; npm i @capacitor/core @capacitor/android`
2. `npx cap init TStory com.reddevils.tstory --web-dir=public`
3. Point `server.url` to live subdomain during testing, then bundle for release:
```json
{ "server": { "url": "https://tstory.reddevils.co.in", "cleartext": false } }
```
4. `npx cap add android; npx cap open android` → build APK/AAB in Android Studio → Play Console.
5. For push/offline later: add `@capacitor/push-notifications` + service worker.

API uses `Access-Control-Allow-Origin: *` already, so WebView calls work.
