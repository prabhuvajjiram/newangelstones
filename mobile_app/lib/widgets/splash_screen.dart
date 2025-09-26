import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  late AnimationController _logoAnimationController;
  late AnimationController _progressAnimationController;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;
  late Animation<double> _progressAnimation;
  
  double _currentProgress = 0.0;
  String _currentStatus = 'Initializing...';
  
  final List<String> _loadingSteps = [
    'Initializing services...',
    'Loading configurations...',
    'Connecting to servers...',
    'Preparing catalog...',
    'Finalizing setup...',
  ];

  @override
  void initState() {
    super.initState();
    
    _logoAnimationController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    
    _progressAnimationController = AnimationController(
      duration: const Duration(seconds: 8),
      vsync: this,
    );

    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _logoAnimationController,
      curve: Curves.easeIn,
    ));

    _scaleAnimation = Tween<double>(
      begin: 0.8,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _logoAnimationController,
      curve: Curves.elasticOut,
    ));
    
    _progressAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _progressAnimationController,
      curve: Curves.easeInOut,
    ));

    _startAnimations();
  }
  
  void _startAnimations() async {
    await _logoAnimationController.forward();
    await Future<void>.delayed(const Duration(milliseconds: 500));
    
    _progressAnimationController.addListener(() {
      final progress = _progressAnimation.value;
      final stepIndex = (progress * (_loadingSteps.length - 1)).floor();
      
      if (mounted) {
        setState(() {
          _currentProgress = progress;
          _currentStatus = _loadingSteps[stepIndex.clamp(0, _loadingSteps.length - 1)];
        });
      }
    });
    
    _progressAnimationController.forward();
  }

  @override
  void dispose() {
    _logoAnimationController.dispose();
    _progressAnimationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final progressBarWidth = screenWidth * 0.7;
    
    return Scaffold(
      backgroundColor: AppTheme.primaryColor,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32.0),
          child: Column(
            children: [
              const Spacer(flex: 2),
              // Logo Section
              AnimatedBuilder(
                animation: _logoAnimationController,
                builder: (context, child) {
                  return FadeTransition(
                    opacity: _fadeAnimation,
                    child: ScaleTransition(
                      scale: _scaleAnimation,
                      child: Container(
                        width: 140,
                        height: 140,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: RadialGradient(
                            colors: [
                              AppTheme.accentColor.withValues(alpha: 0.2),
                              AppTheme.accentColor.withValues(alpha: 0.05),
                              Colors.transparent,
                            ],
                          ),
                          border: Border.all(
                            color: AppTheme.accentColor.withValues(alpha: 0.3),
                            width: 2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.accentColor.withValues(alpha: 0.3),
                              blurRadius: 20,
                              spreadRadius: 5,
                            ),
                          ],
                        ),
                        child: Center(
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(70),
                            child: Container(
                              width: 90,
                              height: 90,
                              color: Colors.transparent,
                              child: Image.asset(
                                'assets/logo.png',
                                width: 90,
                                height: 90,
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 24),
              // Company Name
              FadeTransition(
                opacity: _fadeAnimation,
                child: ShaderMask(
                  blendMode: BlendMode.srcIn,
                  shaderCallback: (Rect bounds) {
                    return const LinearGradient(
                      colors: [
                        Color(0xFFD4AF37),
                        Color(0xFFFFD700),
                        Color(0xFFE6BE8A),
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ).createShader(bounds);
                  },
                  child: const Text(
                    'ANGEL GRANITES',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                      fontFamily: 'OpenSans',
                      letterSpacing: 2.0,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              FadeTransition(
                opacity: _fadeAnimation,
                child: const Text(
                  'Premium Stone Solutions',
                  style: TextStyle(
                    color: AppTheme.textSecondary,
                    fontSize: 14,
                    fontFamily: 'OpenSans',
                    letterSpacing: 0.5,
                  ),
                ),
              ),
              const Spacer(flex: 1),
              // Progress Section
              FadeTransition(
                opacity: _fadeAnimation,
                child: Column(
                  children: [
                    // Progress Bar
                    Container(
                      width: progressBarWidth,
                      height: 6,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(3),
                        color: AppTheme.accentColor.withValues(alpha: 0.2),
                      ),
                      child: Stack(
                        children: [
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 300),
                            width: progressBarWidth * _currentProgress,
                            height: 6,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(3),
                              gradient: const LinearGradient(
                                colors: [
                                  Color(0xFFD4AF37),
                                  Color(0xFFFFD700),
                                ],
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: AppTheme.accentColor.withValues(alpha: 0.5),
                                  blurRadius: 8,
                                  spreadRadius: 1,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    // Progress Text
                    Text(
                      _currentStatus,
                      style: const TextStyle(
                        color: AppTheme.textSecondary,
                        fontSize: 14,
                        fontFamily: 'OpenSans',
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${(_currentProgress * 100).toInt()}%',
                      style: TextStyle(
                        color: AppTheme.accentColor.withValues(alpha: 0.8),
                        fontSize: 12,
                        fontFamily: 'OpenSans',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              const Spacer(flex: 2),
            ],
          ),
        ),
      ),
    );
  }
}
