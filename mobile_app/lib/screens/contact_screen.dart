import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';
import '../services/mautic_service.dart';

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
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      elevation: 4,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: BoxDecoration(
            color: AppTheme.cardColor,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.2),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.accentColor.withValues(alpha: 0.15),
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
                            fontWeight: FontWeight.w700,
                            fontSize: 16,
                          ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 14,
                            height: 1.4,
                          ),
                    ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right, 
                color: AppTheme.accentColor.withValues(alpha: 0.7),
                size: 24,
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
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 15),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 16),
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
                            const SizedBox(height: 16),
                            
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
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 15),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 16),
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
                            const SizedBox(height: 16),
                            
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
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 15),
                                hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 14),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 16),
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
                            const SizedBox(height: 16),
                            
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
                                labelStyle: const TextStyle(color: AppTheme.textSecondary, fontSize: 15),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 16),
                              textAlignVertical: TextAlignVertical.top,
                              maxLines: 5,
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
                            const SizedBox(height: 24),
                            
                            // Submit button
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton(
                                onPressed: _isSubmitting ? null : _submitForm,
                                style: ElevatedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(vertical: 18),
                                  backgroundColor: AppTheme.accentColor,
                                  foregroundColor: AppTheme.primaryColor,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  elevation: 2,
                                  textStyle: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                    letterSpacing: 0.5,
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
