import 'package:flutter/material.dart';

import 'chat_screen.dart';
import 'theme.dart';

void main() => runApp(const BoshpanaApp());

class BoshpanaApp extends StatelessWidget {
  const BoshpanaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Boshpana.ai',
      debugShowCheckedModeBanner: false,
      theme: buildTheme(),
      home: const ChatScreen(),
    );
  }
}
