import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// A wrapper widget that provides proper edge-to-edge handling for Android 15+ compatibility
/// This widget ensures content is properly inset from system bars while maintaining
/// the edge-to-edge display experience
class EdgeToEdgeWrapper extends StatelessWidget {
  final Widget child;
  final bool maintainBottomViewPadding;
  final bool maintainTopViewPadding;
  final EdgeInsets? minimum;
  final bool left;
  final bool top;
  final bool right;
  final bool bottom;

  const EdgeToEdgeWrapper({
    super.key,
    required this.child,
    this.maintainBottomViewPadding = false,
    this.maintainTopViewPadding = true,
    this.minimum,
    this.left = true,
    this.top = true,
    this.right = true,
    this.bottom = true,
  });

  /// Factory constructor for screens that need full edge-to-edge (like image galleries)
  factory EdgeToEdgeWrapper.immersive({
    required Widget child,
    Key? key,
  }) {
    return EdgeToEdgeWrapper(
      key: key,
      maintainBottomViewPadding: false,
      maintainTopViewPadding: false,
      left: false,
      top: false,
      right: false,
      bottom: false,
      child: child,
    );
  }

  /// Factory constructor for normal app screens with proper insets
  factory EdgeToEdgeWrapper.normal({
    required Widget child,
    Key? key,
    EdgeInsets? minimum,
  }) {
    return EdgeToEdgeWrapper(
      key: key,
      maintainBottomViewPadding: true,
      maintainTopViewPadding: true,
      minimum: minimum,
      child: child,
    );
  }

  /// Factory constructor for screens with bottom navigation
  factory EdgeToEdgeWrapper.withBottomNav({
    required Widget child,
    Key? key,
  }) {
    return EdgeToEdgeWrapper(
      key: key,
      maintainBottomViewPadding: false,
      maintainTopViewPadding: true,
      bottom: false,
      child: child,
    );
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      left: left,
      top: top,
      right: right,
      bottom: bottom,
      maintainBottomViewPadding: maintainBottomViewPadding,
      minimum: minimum ?? EdgeInsets.zero,
      child: child,
    );
  }
}

/// A specialized wrapper for AppBar that handles edge-to-edge properly
class EdgeToEdgeAppBar extends StatelessWidget implements PreferredSizeWidget {
  final Widget? title;
  final List<Widget>? actions;
  final Widget? leading;
  final bool automaticallyImplyLeading;
  final Color? backgroundColor;
  final double elevation;
  final SystemUiOverlayStyle? systemOverlayStyle;
  final bool centerTitle;

  const EdgeToEdgeAppBar({
    super.key,
    this.title,
    this.actions,
    this.leading,
    this.automaticallyImplyLeading = true,
    this.backgroundColor,
    this.elevation = 0,
    this.systemOverlayStyle,
    this.centerTitle = true,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      bottom: false,
      child: AppBar(
        title: title,
        actions: actions,
        leading: leading,
        automaticallyImplyLeading: automaticallyImplyLeading,
        backgroundColor: backgroundColor ?? Colors.transparent,
        elevation: elevation,
        systemOverlayStyle: systemOverlayStyle,
        centerTitle: centerTitle,
      ),
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);
}

/// Extension to easily wrap widgets with edge-to-edge handling
extension EdgeToEdgeExtension on Widget {
  /// Wrap this widget with normal edge-to-edge handling
  Widget withEdgeToEdge({
    EdgeInsets? minimum,
  }) {
    return EdgeToEdgeWrapper.normal(
      minimum: minimum,
      child: this,
    );
  }

  /// Wrap this widget with immersive edge-to-edge handling
  Widget withImmersiveEdgeToEdge() {
    return EdgeToEdgeWrapper.immersive(
      child: this,
    );
  }

  /// Wrap this widget with bottom navigation edge-to-edge handling
  Widget withBottomNavEdgeToEdge() {
    return EdgeToEdgeWrapper.withBottomNav(
      child: this,
    );
  }
}
