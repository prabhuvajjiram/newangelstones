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
    _futureColors = widget.apiService.loadLocalProducts('assets/colors.json');
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: ProductSection(
        title: 'Granite Varieties',
        future: _futureColors,
      ),
    );
  }
}
