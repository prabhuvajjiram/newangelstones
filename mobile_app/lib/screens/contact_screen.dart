import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';

class ContactScreen extends StatefulWidget {
  const ContactScreen({super.key});

  @override
  State<ContactScreen> createState() => _ContactScreenState();
}

class _ContactScreenState extends State<ContactScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _messageController = TextEditingController();
  bool _isSubmitting = false;
  
  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _messageController.dispose();
    super.dispose();
  }
  
  Future<void> _launchUrl(String urlString, BuildContext context) async {
    try {
      final Uri url = Uri.parse(urlString);
      if (!await launchUrl(url, mode: LaunchMode.externalApplication)) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Could not open $urlString'),
              behavior: SnackBarBehavior.floating,
              backgroundColor: Colors.red.shade800,
              duration: const Duration(seconds: 3),
              action: SnackBarAction(
                label: 'DISMISS',
                textColor: Colors.white,
                onPressed: () {},
              ),
            ),
          );
        }
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: Colors.red.shade800,
          ),
        );
      }
    }
  }
  
  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isSubmitting = true;
      });
      
      // Simulate API call with a delay
      await Future.delayed(const Duration(seconds: 2));
      
      setState(() {
        _isSubmitting = false;
      });
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Message sent successfully!'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: Colors.green.shade800,
            duration: const Duration(seconds: 3),
          ),
        );
        
        // Clear form
        _nameController.clear();
        _emailController.clear();
        _phoneController.clear();
        _messageController.clear();
      }
    }
  }

  Widget _buildContactCard({
    required BuildContext context,
    required IconData icon,
    required String title,
    required String subtitle,
    VoidCallback? onTap,
  }) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: AppTheme.cardGradient,
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.accentColor.withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: AppTheme.accentColor, size: 24),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: AppTheme.accentColor,
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: AppTheme.textSecondary),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: AppTheme.gradientBackground,
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppTheme.primaryColor,
                    AppTheme.primaryColor.withValues(alpha: 0.8),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Get in Touch',
                    style: Theme.of(context).textTheme.displayLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'We create beautiful, lasting memorials that honor your loved ones.',
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ],
              ),
            ),
            
            // Contact Methods
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
              child: Column(
                children: [
                  _buildContactCard(
                    context: context,
                    icon: Icons.phone,
                    title: 'Call Us',
                    subtitle: '+1 866-682-5837',
                    onTap: () => _launchUrl('tel:+18666825837', context),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.email,
                    title: 'Email Us',
                    subtitle: 'info@theangelstones.com',
                    onTap: () => _launchUrl('mailto:info@theangelstones.com', context),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.credit_card,
                    title: 'Pay Invoice',
                    subtitle: 'Make a secure payment online',
                    onTap: () => _launchUrl('https://www.convergepay.com/hosted-payments?ssl_txn_auth_token=E%2F8reYrhQjCCZuE850a9TQAAAZZqwm4V', context),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.mail,
                    title: 'Mailing Address',
                    subtitle: 'P.O. Box 370, Elberton, GA 30635',
                    onTap: () => _launchUrl('https://www.google.com/maps/search/?api=1&query=P.O.+Box+370,+Elberton,+GA+30635', context),
                  ),
                ],
              ),
            ),

            // Locations Section
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Our Locations',
                    style: Theme.of(context).textTheme.displayMedium,
                  ),
                  const SizedBox(height: 16),
                  ..._buildStaggeredLocations(),
                ],
              ),
            ),
            
            // Contact Form Section
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Send us a Message',
                    style: Theme.of(context).textTheme.displayMedium,
                  ),
                  const SizedBox(height: 16),
                  Card(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      padding: const EdgeInsets.all(16),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            // Name field
                            TextFormField(
                              controller: _nameController,
                              decoration: InputDecoration(
                                labelText: 'Name',
                                prefixIcon: const Icon(Icons.person_outline),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                filled: true,
                                fillColor: Colors.white.withValues(alpha: 0.9),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Please enter your name';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),
                            
                            // Email field
                            TextFormField(
                              controller: _emailController,
                              decoration: InputDecoration(
                                labelText: 'Email',
                                prefixIcon: const Icon(Icons.email_outlined),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                filled: true,
                                fillColor: Colors.white.withValues(alpha: 0.9),
                              ),
                              keyboardType: TextInputType.emailAddress,
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Please enter your email';
                                }
                                if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                                  return 'Please enter a valid email';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),
                            
                            // Phone field
                            TextFormField(
                              controller: _phoneController,
                              decoration: InputDecoration(
                                labelText: 'Phone (optional)',
                                prefixIcon: const Icon(Icons.phone_outlined),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                filled: true,
                                fillColor: Colors.white.withValues(alpha: 0.9),
                              ),
                              keyboardType: TextInputType.phone,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly,
                                LengthLimitingTextInputFormatter(10),
                              ],
                            ),
                            const SizedBox(height: 16),
                            
                            // Message field
                            TextFormField(
                              controller: _messageController,
                              decoration: InputDecoration(
                                labelText: 'Message',
                                prefixIcon: const Icon(Icons.message_outlined),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                filled: true,
                                fillColor: Colors.white.withValues(alpha: 0.9),
                              ),
                              maxLines: 4,
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Please enter your message';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 24),
                            
                            // Submit button
                            ElevatedButton(
                              onPressed: _isSubmitting ? null : _submitForm,
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                backgroundColor: AppTheme.accentColor,
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                              child: _isSubmitting
                                  ? const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
                                        SizedBox(width: 12),
                                        Text('Sending...'),
                                      ],
                                    )
                                  : const Text('SEND MESSAGE'),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 40), // Extra space at bottom
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildStaggeredLocations() {
    final locations = [
      {
        'title': 'Elberton warehouse',
        'address': '1187 Old Middleton Rd\nElberton, GA 30635',
        'mapUrl': 'https://www.google.com/maps?q=1187+Old+Middleton+Rd,+Elberton,+GA+30635',
      },
      {
        'title': 'Elberton Office',
        'address': '203 Williams St\nElberton, GA',
        'mapUrl': 'https://www.google.com/maps?q=203+Williams+St,+Elberton,+GA',
      },
      {
        'title': 'Barre warehouse',
        'address': '15 Blackwell St\nBarre, VT',
        'mapUrl': 'https://www.google.com/maps?q=15+Blackwell+St,+Barre,+VT',
      },
      {
        'title': 'Corporate Office',
        'address': '5540 Centerview Dr, Ste 204, PMB 162836\nRaleigh, NC',
        'mapUrl': 'https://www.google.com/maps?q=5540+Centerview+Dr,+Raleigh,+NC',
      },
    ];
    
    List<Widget> result = [];
    
    for (int i = 0; i < locations.length; i++) {
      final location = locations[i];
      result.add(
        AnimatedOpacity(
          opacity: 1.0,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
          child: AnimatedPadding(
            padding: const EdgeInsets.only(top: 0),
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeOut,
            child: _buildLocationCard(
              location['title']!,
              location['address']!,
              location['mapUrl']!,
              i * 100, // Staggered delay
            ),
          ),
        ),
      );
      
      // Add spacing between cards except after the last one
      if (i < locations.length - 1) {
        result.add(const SizedBox(height: 12));
      }
    }
    
    return result;
  }

  Widget _buildLocationCard(String title, String address, String mapUrl, int delayMilliseconds) {
    return FutureBuilder(
      future: Future.delayed(Duration(milliseconds: delayMilliseconds)),
      builder: (context, snapshot) {
        return AnimatedOpacity(
          opacity: snapshot.connectionState == ConnectionState.done ? 1.0 : 0.0,
          duration: const Duration(milliseconds: 500),
          child: Card(
            margin: EdgeInsets.zero,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Container(
              decoration: AppTheme.cardGradient,
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.accentColor.withValues(alpha: 0.2),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.location_on, color: AppTheme.accentColor),
                ),
                title: Text(
                  title,
                  style: const TextStyle(
                    color: AppTheme.textPrimary,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                subtitle: Padding(
                  padding: const EdgeInsets.only(top: 4.0),
                  child: Text(
                    address,
                    style: const TextStyle(color: AppTheme.textSecondary),
                  ),
                ),
                onTap: () => _launchUrl(mapUrl, context),
                trailing: const Icon(Icons.arrow_forward_ios, 
                  size: 16, 
                  color: AppTheme.textSecondary
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
