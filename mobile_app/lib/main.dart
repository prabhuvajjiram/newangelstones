import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'services/inventory_service.dart';
import 'services/directory_service.dart';
import 'screens/home_screen.dart';
import 'screens/colors_screen.dart';
import 'screens/inventory_screen.dart';
import 'screens/contact_screen.dart';
import 'theme/app_theme.dart';
import 'theme/font_utils.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Angel Granites',
      debugShowCheckedModeBanner: false,
      theme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: AppTheme.primaryColor,
        colorScheme: const ColorScheme.dark(
          primary: AppTheme.accentColor,
          secondary: AppTheme.accentColor,
          surface: AppTheme.cardColor,
          background: AppTheme.primaryColor,
        ),
        textTheme: GoogleFonts.montserratTextTheme(
          Theme.of(context).textTheme.copyWith(
            displayLarge: AppFonts.displayLarge,
            displayMedium: AppFonts.displayMedium,
            titleLarge: AppFonts.titleLarge,
            bodyLarge: AppFonts.bodyLarge,
            bodyMedium: AppFonts.bodyMedium,
            labelLarge: AppFonts.button,
          ),
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.transparent,
          elevation: 0,
          centerTitle: true,
          titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
            fontFamily: 'Montserrat',
          ),
          iconTheme: IconThemeData(color: AppTheme.accentColor),
        ),
        cardTheme: CardThemeData(
          color: AppTheme.cardColor,
          elevation: 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: AppTheme.accentColor,
            foregroundColor: AppTheme.primaryColor,
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            textStyle: AppFonts.button,
          ),
        ),
        bottomNavigationBarTheme: BottomNavigationBarThemeData(
          backgroundColor: AppTheme.cardColor,
          selectedItemColor: AppTheme.accentColor,
          unselectedItemColor: AppTheme.textSecondary,
          showSelectedLabels: true,
          showUnselectedLabels: true,
          type: BottomNavigationBarType.fixed,
        ),
      ),
      home: const MainNavigation(),
    );
  }
}

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  final PageStorageBucket _bucket = PageStorageBucket();
  int _currentIndex = 0;
  final ApiService _apiService = ApiService();
  final StorageService _storageService = StorageService();
  final InventoryService _inventoryService = InventoryService();
  final DirectoryService _directoryService = DirectoryService();

  late final List<Widget> _pages;

  @override
  void initState() {
    super.initState();
    _pages = [
      HomeScreen(
        key: const PageStorageKey('home'),
        apiService: _apiService,
        storageService: _storageService,
        inventoryService: _inventoryService,
        directoryService: _directoryService,
        onViewFullInventory: () => setState(() => _currentIndex = 2),
      ),
      ColorsScreen(
        key: const PageStorageKey('colors'),
        apiService: _apiService,
      ),
      InventoryScreen(
        key: const PageStorageKey('inventory'),
        inventoryService: _inventoryService,
      ),
      const ContactScreen(key: PageStorageKey('contact')),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              margin: const EdgeInsets.only(right: 12),
              decoration: BoxDecoration(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Image.asset(
                'assets/logo.png',
                width: 28,
                height: 28,
                fit: BoxFit.contain,
              ),
            ),
            const Text('Angel Granites'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_cart_outlined),
            tooltip: 'Cart',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: const Text('Cart coming soon'),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.person_outline),
            tooltip: 'Login',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: const Text('Login coming soon'),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: PageStorage(
        bucket: _bucket,
        child: IndexedStack(index: _currentIndex, children: _pages),
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.3),
              blurRadius: 15,
              offset: const Offset(0, -5),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(16),
            topRight: Radius.circular(16),
          ),
          child: BottomNavigationBar(
            currentIndex: _currentIndex,
            elevation: 8,
            type: BottomNavigationBarType.fixed,
            onTap: (index) => setState(() => _currentIndex = index),
            items: const [
              BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Home'),
              BottomNavigationBarItem(icon: Icon(Icons.palette), label: 'Colors'),
              BottomNavigationBarItem(
                icon: Icon(Icons.inventory),
                label: 'Inventory',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.contact_mail),
                label: 'Contact',
              ),
            ],
          ),
        ),
      ),
    );
  }
}
