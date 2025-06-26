import 'package:flutter/material.dart';
import 'models/product.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'screens/home_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Angel Stones',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: HomeScreen(
        apiService: ApiService(),
        storageService: StorageService(),
      ),
    );
  }
}
