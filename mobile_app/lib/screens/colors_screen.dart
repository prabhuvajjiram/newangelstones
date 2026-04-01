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
    // Show local/cached colors immediately (zero network wait)
    _futureColors = widget.apiService.getLocalColors();
    // Refresh from server in background to pick up any new colors
    _backgroundRefresh();
  }

  void _backgroundRefresh() {
    widget.apiService.fetchColors().then((serverColors) {
      if (mounted) {
        setState(() {
          _futureColors = Future.value(serverColors);
        });
      }
    }).catchError((Object e) {
      debugPrint('Background colors refresh error: $e');
    });
  }

  Future<void> _refreshData() async {
    setState(() {
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
