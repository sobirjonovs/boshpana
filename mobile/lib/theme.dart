import 'package:flutter/material.dart';

/// Boshpana.ai brand palette — mirrors the website (green #13a06a).
class AppColors {
  static const green = Color(0xFF13A06A);
  static const greenDark = Color(0xFF0E8659);
  static const greenSoft = Color(0xFFE6F3EC);
  static const greenSoft2 = Color(0xFFF1F7F3);
  static const slate = Color(0xFF1C3344);
  static const page = Color(0xFFF1F6F3);
  static const card = Colors.white;
  static const border = Color(0xFFE3E6EA);
  static const textSoft = Color(0xFF5F6368);
  static const textFaint = Color(0xFF80868B);
  static const userBubble = Color(0xFFE6F3EC);
}

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.green,
      primary: AppColors.green,
      surface: AppColors.page,
    ),
    scaffoldBackgroundColor: AppColors.page,
    fontFamily: 'Roboto',
  );
  return base.copyWith(
    textTheme: base.textTheme.apply(
      bodyColor: AppColors.slate,
      displayColor: AppColors.slate,
    ),
  );
}
