plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "com.angelgranites.app"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = "27.0.12077973"
    
    signingConfigs {
        create("release") {
            storeFile = file("../upload-keystore.jks")
            storePassword = "AngelStones@2025"
            keyAlias = "upload"
            keyPassword = "AngelStones@2025"
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_21
        targetCompatibility = JavaVersion.VERSION_21
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_21.toString()
    }

    defaultConfig {
        applicationId = "com.angelgranites.app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
            isMinifyEnabled = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }
}

flutter {
    source = "../.."
}

dependencies {
    // Add missing annotation dependencies
    implementation("com.google.errorprone:error_prone_annotations:2.23.0")
    implementation("javax.annotation:javax.annotation-api:1.3.2")
}
