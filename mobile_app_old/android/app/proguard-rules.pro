# Keep annotations used by Flutter Secure Storage and other dependencies
-keep class com.google.errorprone.annotations.** { *; }
-keep class javax.annotation.** { *; }
-dontwarn com.google.errorprone.annotations.**
-dontwarn javax.annotation.**

# Keep Tink crypto library classes
-keep class com.google.crypto.tink.** { *; }
-dontwarn com.google.crypto.tink.**
