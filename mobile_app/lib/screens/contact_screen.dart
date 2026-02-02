import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;
import 'package:url_launcher/url_launcher.dart';
import 'package:map_launcher/map_launcher.dart';
import '../theme/app_theme.dart';
import '../services/mautic_service.dart';
import '../config/security_config.dart';
import 'webview_screen.dart';
import 'package:go_router/go_router.dart';

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
  String? _paymentUrl;
  String? _contactPhone;
  String? _contactEmail;
  
  // Email validation method
  bool _isValidEmail(String email) {
    final emailRegExp = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    return emailRegExp.hasMatch(email);
  }

  // Phone validation method
  bool _isValidPhone(String phone) {
    final phoneRegExp = RegExp(r'^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$');
    return phoneRegExp.hasMatch(phone) && phone.length >= 10;
  }

  @override
  void initState() {
    super.initState();
    _loadConfiguration();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _messageController.dispose();
    super.dispose();
  }
  
  Future<void> _loadConfiguration() async {
    try {
      final results = await Future.wait([
        SecurityConfig.getPaymentUrl(),
        SecurityConfig.getContactPhone(),
        SecurityConfig.getContactEmail(),
      ]);
      
      if (mounted) {
        setState(() {
          _paymentUrl = results[0];
          _contactPhone = results[1];
          _contactEmail = results[2];
        });
      }
    } catch (e) {
      debugPrint('⚠️ Error loading configuration: $e');
      // Set fallback values
      if (mounted) {
        setState(() {
          _paymentUrl = null;
          _contactPhone = '+1 866-682-5837';
          _contactEmail = 'info@theangelstones.com';
        });
      }
    }
  }
  
  Future<void> _launchUrl(String urlString, BuildContext context) async {
    try {
      final Uri url = Uri.parse(urlString);
      
      // For phone numbers, try different launch modes
      if (urlString.startsWith('tel:')) {
        // First try with platformDefault mode for better iOS compatibility
        bool launched = await launchUrl(url, mode: LaunchMode.platformDefault);
        
        // If that fails, try externalApplication mode
        if (!launched) {
          launched = await launchUrl(url, mode: LaunchMode.externalApplication);
        }
        
        if (!launched && context.mounted) {
          final phoneNumber = _contactPhone ?? '+1 866-682-5837';
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Could not open phone dialer. Please dial $phoneNumber manually.'),
              behavior: SnackBarBehavior.floating,
              backgroundColor: Colors.red.shade800,
              duration: const Duration(seconds: 4),
              action: SnackBarAction(
                label: 'COPY',
                textColor: Colors.white,
                onPressed: () {
                  Clipboard.setData(ClipboardData(text: phoneNumber));
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Phone number copied to clipboard'),
                      duration: Duration(seconds: 2),
                    ),
                  );
                },
              ),
            ),
          );
        }
      } else {
        // For other URLs, use the standard approach
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
  
  Future<void> _openMap(String address, BuildContext context) async {
    try {
      // Capture context before async gap
      final currentContext = context;
      
      // For web or non-iOS platforms, use Google Maps directly
      if (kIsWeb || !(Platform.isIOS)) {
        final googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(address)}';
        if (currentContext.mounted) {
          await _launchUrl(googleMapsUrl, currentContext);
        }
        return;
      }
      
      // For iOS, check available map apps and show options
      final availableMaps = await MapLauncher.installedMaps;
      
      if (availableMaps.isEmpty) {
        // Fallback to Google Maps URL if no map apps are available
        final googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(address)}';
        if (currentContext.mounted) {
          await _launchUrl(googleMapsUrl, currentContext);
        }
        return;
      }
      
      if (currentContext.mounted) {
        showModalBottomSheet<void>(
          context: context,
          backgroundColor: AppTheme.primaryColor,
          shape: const RoundedRectangleBorder(
            borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
          ),
          builder: (BuildContext context) {
            return SafeArea(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Padding(
                    padding: EdgeInsets.all(16.0),
                    child: Text(
                      'Open with Maps',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const Divider(color: Colors.grey),
                  ...availableMaps.map((map) => ListTile(
                    leading: CircleAvatar(
                      radius: 15,
                      backgroundColor: Colors.grey.shade800,
                      child: Text(
                        map.mapName.substring(0, 1).toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontSize: 14),
                      ),
                    ),
                    title: Text(
                      map.mapName,
                      style: const TextStyle(color: Colors.white),
                    ),
                    onTap: () {
                      Navigator.pop(context);
                      if (map.mapType == MapType.apple) {
                        // For Apple Maps, use the launchUrl approach to avoid coordinate issues
                        final appleMapsUrl = 'https://maps.apple.com/?q=${Uri.encodeComponent(address)}&address=${Uri.encodeComponent(address)}'; 
                        launchUrl(Uri.parse(appleMapsUrl), mode: LaunchMode.externalApplication);
                      } else {
                        // For other map types, use the standard approach
                        map.showMarker(
                          coords: Coords(34.1083, -82.8665), // Default to Elberton, GA if geocoding fails
                          title: 'Angel Stones',
                          description: address,
                          extraParams: {'q': address},
                        );
                      }
                    },
                  )),
                ],
              ),
            );
          },
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error opening maps: ${e.toString()}'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: Colors.red.shade800,
          ),
        );
      }
    }
  }
  
  Future<void> _submitForm() async {
    // Close keyboard before submission
    FocusScope.of(context).unfocus();
    
    if (_formKey.currentState!.validate()) {
      // Trim all input values
      _nameController.text = _nameController.text.trim();
      _emailController.text = _emailController.text.trim();
      _phoneController.text = _phoneController.text.trim();
      _messageController.text = _messageController.text.trim();
      
      // Re-validate after trimming
      if (!_formKey.currentState!.validate()) {
        return;
      }
      setState(() {
        _isSubmitting = true;
      });
      
      try {
        final success = await MauticService.submitContactForm(
          name: _nameController.text.trim(),
          email: _emailController.text.trim(),
          phone: _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
          message: _messageController.text.trim(),
        );
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                success 
                  ? 'Message sent successfully! We\'ll get back to you soon.'
                  : 'Failed to send message. Please try again later.',
              ),
              behavior: SnackBarBehavior.floating,
              backgroundColor: success ? Colors.green.shade800 : Colors.red.shade800,
              duration: const Duration(seconds: 4),
              action: SnackBarAction(
                label: 'DISMISS',
                textColor: Colors.white,
                onPressed: () {},
              ),
            ),
          );
          
          if (success) {
            // Clear form only on successful submission
            _nameController.clear();
            _emailController.clear();
            _phoneController.clear();
            _messageController.clear();
            _formKey.currentState?.reset();
          }
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: const Text('An error occurred. Please try again later.'),
              behavior: SnackBarBehavior.floating,
              backgroundColor: Colors.red.shade800,
              duration: const Duration(seconds: 4),
              action: SnackBarAction(
                label: 'DISMISS',
                textColor: Colors.white,
                onPressed: () {},
              ),
            ),
          );
        }
      } finally {
        if (mounted) {
          setState(() {
            _isSubmitting = false;
          });
        }
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
      margin: const EdgeInsets.symmetric(vertical: 5, horizontal: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 2,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: BoxDecoration(
            color: AppTheme.cardColor,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.15),
                blurRadius: 4,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppTheme.accentColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: AppTheme.accentColor, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: AppTheme.accentColor,
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 12,
                            height: 1.3,
                          ),
                    ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right, 
                color: AppTheme.accentColor.withValues(alpha: 0.7),
                size: 20,
              ),
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
      child: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header - Compact
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
                  Semantics(
                    header: true,
                    child: Text(
                      'Get in Touch',
                      style: Theme.of(context).textTheme.displayLarge?.copyWith(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'We create beautiful, lasting memorials',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
            
            // Contact Methods
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
              child: Column(
                children: [
                  _buildContactCard(
                    context: context,
                    icon: Icons.phone,
                    title: 'Call Us',
                    subtitle: _contactPhone ?? '+1 866-682-5837',
                    onTap: () {
                      final phone = _contactPhone ?? '+1 866-682-5837';
                      final telUrl = 'tel:${phone.replaceAll(RegExp(r'[^\d+]'), '')}';
                      _launchUrl(telUrl, context);
                    },
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.email,
                    title: 'Email Us',
                    subtitle: _contactEmail ?? 'info@theangelstones.com',
                    onTap: () {
                      final email = _contactEmail ?? 'info@theangelstones.com';
                      _launchUrl('mailto:$email', context);
                    },
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.credit_card,
                    title: 'Pay Invoice',
                    subtitle: 'Make a secure payment online',
                    onTap: _paymentUrl != null 
                        ? () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => WebViewScreen(
                                  url: _paymentUrl!,
                                  title: 'Payment',
                                ),
                              ),
                            );
                          }
                        : null,
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.person_outline,
                    title: 'Login to Account',
                    subtitle: 'Access your customer portal',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => WebViewScreen(
                            url: '${SecurityConfig.monumentBusinessBaseUrl}/GV/Account/Login',
                            title: 'Customer Portal',
                          ),
                        ),
                      );
                    },
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.mail,
                    title: 'Mailing Address',
                    subtitle: 'P.O. Box 370, Elberton, GA 30635',
                    onTap: () => _openMap('P.O. Box 370, Elberton, GA 30635', context),
                  ),
                ],
              ),
            ),

            // Locations Section
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 10.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Our Locations',
                    style: Theme.of(context).textTheme.displayMedium?.copyWith(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 10),
                  ..._buildStaggeredLocations(),
                ],
              ),
            ),
            
            // Contact Form Section
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 10.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Send us a Message',
                    style: Theme.of(context).textTheme.displayMedium?.copyWith(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Card(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      padding: const EdgeInsets.all(12),
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
                                prefixIcon: const Icon(Icons.person_outline, color: AppTheme.textSecondary),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: const BorderSide(color: AppTheme.accentColor, width: 2),
                                ),
                                filled: true,
                                fillColor: Colors.grey.shade900,
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 14),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return 'Please enter your name';
                                }
                                if (value.trim().length < 2) {
                                  return 'Name is too short';
                                }
                                return null;
                              },
                              inputFormatters: [
                                FilteringTextInputFormatter.allow(RegExp(r'[a-zA-Z\s]')), // Only letters and spaces
                              ],
                            ),
                            const SizedBox(height: 12),
                            
                            // Email field
                            TextFormField(
                              controller: _emailController,
                              decoration: InputDecoration(
                                labelText: 'Email',
                                prefixIcon: const Icon(Icons.email_outlined, color: AppTheme.textSecondary),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: const BorderSide(color: AppTheme.accentColor, width: 2),
                                ),
                                filled: true,
                                fillColor: Colors.grey.shade900,
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 14),
                              keyboardType: TextInputType.emailAddress,
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Please enter your email';
                                }
                                if (!_isValidEmail(value)) {
                                  return 'Please enter a valid email';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 12),
                            
                            // Phone field
                            TextFormField(
                              controller: _phoneController,
                              decoration: InputDecoration(
                                labelText: 'Phone (optional)',
                                prefixIcon: const Icon(Icons.phone_outlined, color: AppTheme.textSecondary),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: const BorderSide(color: AppTheme.accentColor, width: 2),
                                ),
                                filled: true,
                                fillColor: Colors.grey.shade900,
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
                                hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 14),
                              keyboardType: TextInputType.phone,
                              validator: (value) {
                                if (value != null && value.isNotEmpty && !_isValidPhone(value)) {
                                  return 'Please enter a valid phone number';
                                }
                                return null;
                              },
                              inputFormatters: [
                                FilteringTextInputFormatter.allow(RegExp(r'[0-9\s\-()]')), // Allow digits, spaces, hyphens, and parentheses
                                LengthLimitingTextInputFormatter(15),
                              ],
                            ),
                            const SizedBox(height: 12),
                            
                            // Message field
                            TextFormField(
                              controller: _messageController,
                              decoration: InputDecoration(
                                labelText: 'Message',
                                alignLabelWithHint: true,
                                prefixIcon: const Padding(
                                  padding: EdgeInsets.only(bottom: 60),
                                  child: Icon(Icons.message_outlined, color: AppTheme.textSecondary),
                                ),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide(color: Colors.grey.shade700),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: const BorderSide(color: AppTheme.accentColor, width: 2),
                                ),
                                filled: true,
                                fillColor: Colors.grey.shade900,
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 14),
                              textAlignVertical: TextAlignVertical.top,
                              maxLines: 4,
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return 'Please enter your message';
                                }
                                if (value.trim().length < 10) {
                                  return 'Message is too short (min 10 characters)';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),
                            
                            // Submit button
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton(
                                onPressed: _isSubmitting ? null : _submitForm,
                                style: ElevatedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(vertical: 14),
                                  backgroundColor: AppTheme.accentColor,
                                  foregroundColor: AppTheme.primaryColor,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  elevation: 2,
                                  textStyle: const TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                                child: _isSubmitting
                                    ? const Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
                                          SizedBox(width: 10),
                                          Text('Sending...'),
                                        ],
                                      )
                                    : const Text('SEND MESSAGE'),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20), // Extra space at bottom
                ],
              ),
            ),
          ],
        ),
      ),
    ));
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
        result.add(const SizedBox(height: 8));
      }
    }
    
    return result;
  }

  Widget _buildLocationCard(String title, String address, String mapUrl, int delayMilliseconds) {
    return FutureBuilder(
      future: Future<void>.delayed(Duration(milliseconds: delayMilliseconds)),
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
                onTap: () => _openMap(address, context),
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
