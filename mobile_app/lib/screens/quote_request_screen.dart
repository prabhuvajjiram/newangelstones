import 'package:flutter/material.dart';
import '../services/mautic_service.dart';
import '../models/quote_request.dart';
import '../widgets/app_button.dart';

class QuoteRequestScreen extends StatefulWidget {
  final List<Map<String, dynamic>> cartItems;
  final int totalQuantity;

  const QuoteRequestScreen({
    super.key,
    required this.cartItems,
    required this.totalQuantity,
  });

  @override
  _QuoteRequestScreenState createState() => _QuoteRequestScreenState();
}

class _QuoteRequestScreenState extends State<QuoteRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _projectDetailsController = TextEditingController();
  bool _isSubmitting = false;
  bool _isSuccess = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _projectDetailsController.dispose();
    super.dispose();
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    try {
      final request = QuoteRequest(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
        projectDetails: _projectDetailsController.text.trim(),
        cartItems: widget.cartItems,
        totalQuantity: widget.totalQuantity,
      );

      final success = await MauticService.submitQuoteRequest(
        name: request.name,
        email: request.email,
        phone: request.phone,
        projectDetails: request.projectDetails,
        cartItems: request.formattedCartItems,
        totalQuantity: request.totalQuantity,
      );

      setState(() => _isSuccess = success);
      
      if (success) {
        // Reset form on success
        _formKey.currentState?.reset();
        // Show success message
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Quote request submitted successfully!')),
        );
      } else {
        // Show error message
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to submit quote request. Please try again.')),
        );
      }
    } catch (e) {
      setState(() => _isSuccess = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('An error occurred. Please try again.')),
      );
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Request a Quote'),
      ),
      body: _isSuccess
          ? _buildSuccessView()
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    TextFormField(
                      controller: _nameController,
                      decoration: const InputDecoration(
                        labelText: 'Full Name',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) =>
                          value?.isEmpty ?? true ? 'Please enter your name' : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _emailController,
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.emailAddress,
                      validator: (value) =>
                          !(value?.contains('@') ?? false)
                              ? 'Please enter a valid email'
                              : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _phoneController,
                      decoration: const InputDecoration(
                        labelText: 'Phone Number',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.phone,
                      validator: (value) =>
                          (value?.length ?? 0) < 10
                              ? 'Please enter a valid phone number'
                              : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _projectDetailsController,
                      decoration: const InputDecoration(
                        labelText: 'Project Details',
                        hintText: 'Tell us about your project...',
                        border: OutlineInputBorder(),
                        alignLabelWithHint: true,
                      ),
                      maxLines: 5,
                      validator: (value) =>
                          (value?.length ?? 0) < 10
                              ? 'Please provide more details about your project'
                              : null,
                    ),
                    const SizedBox(height: 24),
                    if (widget.cartItems.isNotEmpty) ...[
                      const Text(
                        'Items in your request:',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      ...widget.cartItems.map((item) => Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4.0),
                            child: Text(
                              '• ${item['name']} (Qty: ${item['quantity']})',
                            ),
                          )),
                      const SizedBox(height: 16),
                    ],
                    AppButton(
                      onPressed: _isSubmitting ? null : _submitForm,
                      child: _isSubmitting
                          ? const CircularProgressIndicator()
                          : const Text('Submit Quote Request'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSuccessView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.check_circle_outline,
              color: Colors.green,
              size: 80,
            ),
            const SizedBox(height: 24),
            const Text(
              'Thank You!',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            const Text(
              'Your quote request has been submitted successfully. Our sales team will contact you shortly.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16),
            ),
            const SizedBox(height: 32),
            AppButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Back to Products'),
            ),
          ],
        ),
      ),
    );
  }
}
