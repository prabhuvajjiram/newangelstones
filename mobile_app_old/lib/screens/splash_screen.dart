import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../navigation/app_router.dart';
import '../theme/app_theme.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    // Navigate to home after a short delay
    Future.delayed(const Duration(milliseconds: 1500), () {
      if (mounted) {
        context.goNamed(AppRouter.home);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final shortestSide = size.shortestSide;
    final isSmallDevice = shortestSide < 550;
    final logoSize = isSmallDevice ? shortestSide * 0.6 : 300.0;

    return Scaffold(
      backgroundColor: AppTheme.primaryColor,
      body: Center(
        child: Hero(
          tag: 'splash-logo',  // For smooth transition if needed later
          child: Image.asset(
            'assets/logo.png',
            width: logoSize,
            height: logoSize,
            fit: BoxFit.contain,
            errorBuilder: (context, error, stackTrace) => Icon(
              Icons.diamond_outlined,
              size: logoSize * 0.5,
              color: Theme.of(context).colorScheme.secondary,
            ),
          ),
        ),
      ),
    );
  }
}
