import 'package:flutter/material.dart';

class AppButton extends StatelessWidget {
  final VoidCallback? onPressed;
  final Widget child;
  final Color? color;
  final Color? textColor;
  final bool fullWidth;
  final EdgeInsetsGeometry? padding;
  final double? height;
  final double? width;

  const AppButton({
    super.key,
    required this.onPressed,
    required this.child,
    this.color,
    this.textColor,
    this.fullWidth = true,
    this.padding,
    this.height,
    this.width,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final buttonColor = color ?? theme.primaryColor;
    final buttonTextColor = textColor ?? Colors.white;

    return SizedBox(
      width: fullWidth ? double.infinity : width,
      height: height ?? 50,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: buttonColor,
          foregroundColor: buttonTextColor,
          padding: padding ?? const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          elevation: 2,
        ),
        child: child,
      ),
    );
  }
}
