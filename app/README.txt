Angel Granites Smartlink

Install:
1) Upload `index.php` to /app/ on your site (so it is served at https://theangelstones.com/app/).
2) (Optional) Upload `qr-smartlink.png` anywhere you like; it encodes https://theangelstones.com/app/.

How it works:
- iOS → redirects to App Store: https://apps.apple.com/us/app/angel-granites/id6748974666
- Android → redirects to Google Play: https://play.google.com/store/apps/details?id=com.angelgranites.app
- Desktop/unknown → shows a tiny landing page with both badges
- Pass-through any query string to the store URL (?src=email-mautic etc.)
- QA: add ?force=ios or ?force=android or ?force=landing

Email usage (MJML):
Point BOTH badges to https://theangelstones.com/app/
