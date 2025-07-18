import 'package:flutter/material.dart';
import '../services/api_service.dart';

class DesignGalleryScreen extends StatelessWidget {
  final String categoryId;
  final String title;
  final ApiService apiService;

  const DesignGalleryScreen({super.key, required this.categoryId, required this.title, required this.apiService});

  @override
  Widget build(BuildContext context) {
    final futureImages = apiService.fetchCategoryImages(categoryId);
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: FutureBuilder<List<String>>(
        future: futureImages,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No designs found'));
          }
          final images = snapshot.data!;
          return GridView.builder(
            padding: const EdgeInsets.all(8),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 1,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
            ),
            itemCount: images.length,
            itemBuilder: (context, index) {
              final url = images[index];
              return Image.network(
                url,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stack) => const Icon(Icons.broken_image),
              );
            },
          );
        },
      ),
    );
  }
}

