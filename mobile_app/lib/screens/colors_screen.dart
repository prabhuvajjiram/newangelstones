import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../widgets/product_section.dart';

class ColorsScreen extends StatefulWidget {
  final ApiService apiService;
  const ColorsScreen({super.key, required this.apiService});

  @override
  State<ColorsScreen> createState() => _ColorsScreenState();
}

class _ColorsScreenState extends State<ColorsScreen> {
  late Future<List<Product>> _futureColors;

  @override
  void initState() {
    super.initState();
    // Fetch colors from API (which returns all 40 colors with proper URLs)
    // Images will load from bundled assets first (via localImagePath), then fallback to URL
    _futureColors = widget.apiService.fetchColors();
  }

  Future<void> _refreshData() async {
    setState(() {
      // Force refresh colors from server
      _futureColors = widget.apiService.fetchColors(forceRefresh: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _refreshData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ProductSection(
          title: 'Granite Varieties',
          future: _futureColors,
        ),
      ),
    );
  }
}
