import 'package:flutter/material.dart';

class AppTheme {
  static const Color primaryColor = Color(0xFF121212);
  static const Color accentColor = Color(0xFFFFD700);
  static const Color cardColor = Color(0xFF1E1E1E);
  static const Color textPrimary = Colors.white;
  static const Color textSecondary = Color(0xFFBDBDBD);

  // Gradient for cards
  static BoxDecoration get cardGradient => BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            cardColor.withValues(alpha: 0.8),
            cardColor.withValues(alpha: 0.6),
          ],
        ),
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      );

  // Text styles using local fonts
  static const TextStyle displayLarge = TextStyle(
    fontFamily: 'Poppins',
    fontSize: 28,
    fontWeight: FontWeight.bold,
    color: textPrimary,
  );

  static const TextStyle displayMedium = TextStyle(
    fontFamily: 'Poppins',
    fontSize: 24,
    fontWeight: FontWeight.w600,
    color: textPrimary,
  );

  static const TextStyle headlineMedium = TextStyle(
    fontFamily: 'PlayfairDisplay',
    fontSize: 22,
    fontWeight: FontWeight.w500,
    color: textPrimary,
  );

  static const TextStyle bodyLarge = TextStyle(
    fontFamily: 'OpenSans',
    fontSize: 16,
    color: textPrimary,
    height: 1.5,
  );

  static const TextStyle bodyMedium = TextStyle(
    fontFamily: 'OpenSans',
    fontSize: 14,
    color: textSecondary,
    height: 1.5,
  );

  static ThemeData get darkTheme {
    return _buildDarkTheme(highContrast: false);
  }

  /// A deliberately simple, maximum-contrast variant used when Windows or
  /// another host platform enables its high-contrast accessibility setting.
  static ThemeData get highContrastDarkTheme {
    return _buildDarkTheme(highContrast: true);
  }

  static ThemeData _buildDarkTheme({required bool highContrast}) {
    final background = highContrast ? Colors.black : primaryColor;
    final surface = highContrast ? Colors.black : cardColor;
    final secondaryText = highContrast ? Colors.white : textSecondary;
    final outline = highContrast ? Colors.white : const Color(0xFF757575);

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: background,
      focusColor: accentColor.withValues(alpha: highContrast ? 0.45 : 0.25),
      hoverColor: accentColor.withValues(alpha: highContrast ? 0.30 : 0.12),
      highlightColor: accentColor.withValues(alpha: 0.20),
      materialTapTargetSize: MaterialTapTargetSize.padded,
      visualDensity: VisualDensity.standard,
      colorScheme: ColorScheme.dark(
        primary: accentColor,
        secondary: accentColor,
        surface: surface,
        onSurface: Colors.white,
        outline: outline,
        error: const Color(0xFFFF6B6B),
      ),
      fontFamily: 'OpenSans', // Default font for the app
      appBarTheme: AppBarTheme(
        backgroundColor: highContrast ? Colors.black : Colors.transparent,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 24,
          fontWeight: FontWeight.bold,
          color: textPrimary,
        ),
        iconTheme: const IconThemeData(color: accentColor),
      ),
      textTheme: TextTheme(
        displayLarge: displayLarge,
        displayMedium: displayMedium,
        headlineMedium: headlineMedium,
        bodyLarge: bodyLarge,
        bodyMedium: bodyMedium.copyWith(color: secondaryText),
      ),
      cardTheme: ThemeData.dark().cardTheme.copyWith(
            color: surface,
            elevation: 4,
            margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: highContrast
                  ? const BorderSide(color: Colors.white, width: 1.5)
                  : BorderSide.none,
            ),
          ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: accentColor,
          foregroundColor: primaryColor,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          textStyle: const TextStyle(
            fontFamily: 'Poppins',
            fontWeight: FontWeight.w600,
            fontSize: 16,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: highContrast ? Colors.black : cardColor,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: accentColor, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        labelStyle: bodyMedium,
        hintStyle: bodyMedium.copyWith(
          color: secondaryText,
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: ButtonStyle(
          minimumSize: WidgetStateProperty.all(const Size(48, 48)),
          foregroundColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.disabled)) {
              return secondaryText;
            }
            return null;
          }),
        ),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: surface,
        selectedItemColor: accentColor,
        unselectedItemColor: secondaryText,
        showSelectedLabels: true,
        showUnselectedLabels: true,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        selectedLabelStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
        unselectedLabelStyle: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w500,
        ),
      ),
      tooltipTheme: TooltipThemeData(
        waitDuration: const Duration(milliseconds: 400),
        textStyle: const TextStyle(color: Colors.black, fontSize: 14),
        decoration: BoxDecoration(
          color: accentColor,
          borderRadius: BorderRadius.circular(4),
        ),
      ),
      dividerColor: highContrast ? Colors.white : const Color(0xFF424242),
    );
  }

  static BoxDecoration get gradientBackground => const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFF1A1A1A),
            Color(0xFF121212),
          ],
        ),
      );
}
