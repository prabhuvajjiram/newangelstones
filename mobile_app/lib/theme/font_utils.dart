import 'package:flutter/material.dart';

class AppFonts {
  static const String montserrat = 'Montserrat';
  static const String openSans = 'OpenSans';
  
  static const TextStyle displayLarge = TextStyle(
    fontFamily: montserrat,
    fontSize: 28,
    fontWeight: FontWeight.bold,
    color: Colors.white,
  );
  
  static const TextStyle displayMedium = TextStyle(
    fontFamily: montserrat,
    fontSize: 24,
    fontWeight: FontWeight.w600,
    color: Colors.white,
  );
  
  static const TextStyle titleLarge = TextStyle(
    fontFamily: montserrat,
    fontSize: 20,
    fontWeight: FontWeight.w600,
    color: Colors.white,
  );
  
  static const TextStyle bodyLarge = TextStyle(
    fontFamily: openSans,
    fontSize: 16,
    color: Colors.white,
    height: 1.5,
  );
  
  static const TextStyle bodyMedium = TextStyle(
    fontFamily: openSans,
    fontSize: 14,
    color: Color(0xFFBDBDBD),
    height: 1.5,
  );
  
  static const TextStyle button = TextStyle(
    fontFamily: montserrat,
    fontSize: 16,
    fontWeight: FontWeight.w600,
    color: Colors.black,
  );
}
