plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "com.angelgranites.app"
    compileSdk = 35
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
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.angelgranites.app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = 23
        targetSdk = 35
        
        // Read version from pubspec.yaml
        val pubspecFile = File(project.projectDir.parentFile.parentFile, "pubspec.yaml")
        val pubspecContent = pubspecFile.readText()
        val versionMatch = Regex("""version:\s+([0-9]+\.[0-9]+\.[0-9]+)\+([0-9]+)""").find(pubspecContent)
        
        if (versionMatch != null) {
            versionName = versionMatch.groupValues[1]
            versionCode = versionMatch.groupValues[2].toInt()
        } 
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
            isMinifyEnabled = true
            isShrinkResources = true
        }
    }
}

flutter {
    source = "../.."
}


