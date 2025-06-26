# Angel Stones Mobile App

This folder contains a Flutter project that provides a basic mobile application for Angel Stones. The code is intentionally lightweight so it can be edited without a full Flutter installation.

## Project Structure

- `lib/` – Dart source files
  - `main.dart` – entry point with route setup
  - `models/` – data models used in the app
  - `services/` – API and local storage helpers
  - `screens/` – UI screens such as home, detail, cart, and contact
- `test/` – example widget test

## Running the App

1. Install Flutter on your development machine.
2. From the `mobile_app` directory run:
   ```bash
   flutter pub get
   flutter run
   ```

## Building for Release

To generate an APK for Android:
```bash
flutter build apk --release
```

To generate an IPA for iOS (requires macOS):
```bash
flutter build ios --release
```

