import java.util.Properties

plugins {
    id("com.android.application")
    // Flutter 3.44 still needs its temporary KGP bridge for plugin modules.
    id("com.android.built-in-kotlin")
    id("com.google.devtools.ksp")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin plugins.
    id("dev.flutter.flutter-gradle-plugin")
    id("com.google.gms.google-services")
    id("com.google.firebase.crashlytics")
}

val keystoreProperties = Properties()
ksp {
    arg("appfunctions:aggregateAppFunctions", "true")
}

val keystorePropertiesFile = rootProject.file("key.properties")
if (keystorePropertiesFile.exists()) {
    keystorePropertiesFile.inputStream().use { input ->
        keystoreProperties.load(input)
    }
}

android {
    namespace = "com.angelgranites.app"
    compileSdk = 37
    ndkVersion = "29.0.14206865"

    sourceSets.named("main") {
        kotlin.directories += "src/appfunctions/kotlin"
    }

    signingConfigs {
        create("release") {
            val storeFilePath =
                keystoreProperties.getProperty("storeFile")
                    ?: System.getenv("ANDROID_KEYSTORE_PATH")
            val storePasswordValue =
                keystoreProperties.getProperty("storePassword")
                    ?: System.getenv("ANDROID_KEYSTORE_PASSWORD")
            val keyAliasValue =
                keystoreProperties.getProperty("keyAlias")
                    ?: System.getenv("ANDROID_KEY_ALIAS")
            val keyPasswordValue =
                keystoreProperties.getProperty("keyPassword")
                    ?: System.getenv("ANDROID_KEY_PASSWORD")

            if (!storeFilePath.isNullOrBlank()) {
                storeFile = file(storeFilePath)
            }
            if (!storePasswordValue.isNullOrBlank()) {
                storePassword = storePasswordValue
            }
            if (!keyAliasValue.isNullOrBlank()) {
                keyAlias = keyAliasValue
            }
            if (!keyPasswordValue.isNullOrBlank()) {
                keyPassword = keyPasswordValue
            }
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
        isCoreLibraryDesugaringEnabled = true
    }


    defaultConfig {
        multiDexEnabled = true
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.angelgranites.app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = 37

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
    implementation(platform("com.google.firebase:firebase-bom:34.9.0"))
    implementation("com.google.firebase:firebase-messaging")
    implementation("com.google.firebase:firebase-crashlytics")
    implementation("androidx.appfunctions:appfunctions:1.0.0-alpha10")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.10.2")
    add("ksp", "androidx.appfunctions:appfunctions-compiler:1.0.0-alpha10")
}
