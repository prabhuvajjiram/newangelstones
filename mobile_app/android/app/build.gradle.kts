plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
    id("com.google.gms.google-services")
    id("com.google.firebase.crashlytics")
}

android {
    namespace = "com.angelgranites.app"
    compileSdk = 36
    ndkVersion = "29.0.14206865"

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
        isCoreLibraryDesugaringEnabled = true
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_21.toString()
    }

    defaultConfig {
        multiDexEnabled = true
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.angelgranites.app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = 35

        // Read version from pubspec.yaml
        val pubspecFile = File(project.projectDir.parentFile.parentFile, "pubspec.yaml")
        val pubspecContent = pubspecFile.readText()
        val versionMatch =
            Regex("""version:\s+([0-9]+\.[0-9]+\.[0-9]+)\+([0-9]+)""").find(pubspecContent)

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
dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.5")
    implementation(platform("com.google.firebase:firebase-bom:32.7.0"))
    implementation("com.google.firebase:firebase-messaging")
    implementation("com.google.firebase:firebase-crashlytics")
}


