buildscript {
    repositories {
        google()
        mavenCentral()
    }
    dependencies {
        // Add the Android Gradle plugin
        classpath("com.android.tools.build:gradle:8.3.0")
        // Add the Kotlin Gradle plugin
        classpath("org.jetbrains.kotlin:kotlin-gradle-plugin:1.9.22")
        // Add the Google services Gradle plugin
        classpath("com.google.gms:google-services:4.4.1")
    }
}
