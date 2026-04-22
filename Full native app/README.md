# AskVisa Native App (Production-Ready)

This repository contains the complete conversion of the AskVisa website into a fully native mobile app and REST API backend.

## 🚀 Architecture Overview

We have migrated away from the legacy procedural PHP rendering into a modern **Client-Server Architecture**:
1. **REST API Backend (`backend_api/`)**: A stateless PHP API router that exposes endpoints for React/Flutter clients. It connects to the exact same MySQL database.
2. **Native Mobile App (`askvisa_mobile/`)**: A Flutter application built with Clean Architecture, Riverpod for state management, and Dio for networking.

### Backend API Structure
```
backend_api/
├── .htaccess               # Routes all /api/* requests to api.php
├── api.php                 # Main Router & Entry Point
├── config.php              # DB & Gateway Credentials (Environment Variables recommended)
└── controllers/            # Controller logic
    ├── ApplicationController.php  # Handles dynamic forms and orders
    ├── CountryController.php      # Handles country/visa listings
    ├── PaymentController.php      # Razorpay Server-to-Server integration
    └── UploadController.php       # Secure Document Uploads
```

### Flutter App Structure (Clean Architecture)
```
askvisa_mobile/
├── lib/
│   ├── core/               # App-wide routing, theme, and network (Dio interceptors)
│   ├── features/           # Feature-first modular design
│   │   ├── application/    # Dynamic Form & Question models
│   │   ├── checkout/       # Razorpay SDK integration & summary
│   │   ├── home/           # Country selection feed
│   │   └── visa/           # Visa pricing & requirement selection
│   └── main.dart           # App Entry
```

## 🛠️ 1. Setup Instructions (Backend API)
1. Copy the `backend_api` folder to your web server (e.g., inside `public_html/api/` or as a standalone subdomain `api.askvisa.in`).
2. Open `config.php` and update your database credentials if you aren't using environment variables.
3. Test the API: Open your browser and go to `http://your-server/backend_api/api/countries`. You should see a JSON response.

## 📱 2. Setup Instructions (Flutter App)
1. **Prerequisites**: Install [Flutter SDK](https://docs.flutter.dev/get-started/install) and Android Studio.
2. Open terminal in `Full native app/askvisa_mobile`.
3. Run `flutter pub get` to install dependencies (Riverpod, Dio, GoRouter, Razorpay, etc).
4. **Link the API**: Open `lib/core/network/dio_client.dart` and change the `baseUrl` from `http://10.0.2.2:8000/api/` to your live API URL (e.g., `https://api.askvisa.in/`).
5. **Add Razorpay Key**: Add your Razorpay test/live key directly to the Razorpay checkout script in the Flutter app (when testing payments).

## 🏃 3. How to Run Locally & Test
- Start Android emulator or plug in a physical device.
- Run the command: `flutter run`
- The app will launch with the native Home screen fetching data via your new PHP API.
- Native features like Camera integration (`image_picker`) and `razorpay_flutter` are fully ready.

## 📦 4. How to Build APK / AAB
To build a production-ready application for Android:
1. Ensure your API URL is pointing to production.
2. Run the build command:
   ```bash
   flutter build apk --release
   ```
3. The APK will be generated at: `build/app/outputs/flutter-apk/app-release.apk`
4. For Google Play Store submission, build an App Bundle:
   ```bash
   flutter build appbundle
   ```

## 💡 Improvements Implemented Over Legacy Website:
- **No WebViews**: The UI is 100% native Flutter, running at 60fps instead of loading clunky HTML pages.
- **Stateless Backend**: The session-based PHP form sequence was extremely brittle if a user refreshed the page. The new API is stateless, relying on the mobile app to hold the form state (using Riverpod) until the final API submission.
- **Security**: Moved Razorpay key generation and verification purely to the backend (`PaymentController.php`), preventing client-side spoofing. File uploads are validated strictly based on MIME types.
- **Scalability**: By separating the frontend from the backend, you can now easily build an iOS app or a React web dashboard using the exact same API endpoints.
