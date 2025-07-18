import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';

class ContactScreen extends StatelessWidget {
  const ContactScreen({super.key});

  Future<void> _launchUrl(String urlString) async {
    final Uri url = Uri.parse(urlString);
    if (!await launchUrl(url)) {
      throw Exception('Could not launch $urlString');
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
                  color: AppTheme.accentColor.withOpacity(0.2),
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
                    AppTheme.primaryColor.withOpacity(0.8),
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
                    onTap: () => _launchUrl('tel:+18666825837'),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.email,
                    title: 'Email Us',
                    subtitle: 'info@theangelstones.com',
                    onTap: () => _launchUrl('mailto:info@theangelstones.com'),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.credit_card,
                    title: 'Pay Invoice',
                    subtitle: 'Make a secure payment online',
                    onTap: () => _launchUrl('https://www.convergepay.com/hosted-payments?ssl_txn_auth_token=E%2F8reYrhQjCCZuE850a9TQAAAZZqwm4V'),
                  ),
                  _buildContactCard(
                    context: context,
                    icon: Icons.mail,
                    title: 'Mailing Address',
                    subtitle: 'P.O. Box 370, Elberton, GA 30635',
                    onTap: () => _launchUrl('https://www.google.com/maps/search/?api=1&query=P.O.+Box+370,+Elberton,+GA+30635'),
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
                  _buildLocationCard(
                    'Elberton warehouse',
                    '1187 Old Middleton Rd\nElberton, GA 30635',
                    'https://www.google.com/maps?q=1187+Old+Middleton+Rd,+Elberton,+GA+30635',
                  ),
                  const SizedBox(height: 12),
                  _buildLocationCard(
                    'Elberton Office',
                    '203 Williams St\nElberton, GA',
                    'https://www.google.com/maps?q=203+Williams+St,+Elberton,+GA',
                  ),
                  const SizedBox(height: 12),
                  _buildLocationCard(
                    'Barre warehouse',
                    '15 Blackwell St\nBarre, VT',
                    'https://www.google.com/maps?q=15+Blackwell+St,+Barre,+VT',
                  ),
                  const SizedBox(height: 24),
                  _buildLocationCard(
                    'Corporate Office',
                    '5540 Centerview Dr, Ste 204, PMB 162836\nRaleigh, NC',
                    'https://www.google.com/maps?q=5540+Centerview+Dr,+Raleigh,+NC',
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLocationCard(String title, String address, String mapUrl) {
    return Card(
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
              color: AppTheme.accentColor.withOpacity(0.2),
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
          onTap: () => _launchUrl(mapUrl),
          trailing: const Icon(Icons.arrow_forward_ios, 
            size: 16, 
            color: AppTheme.textSecondary
          ),
        ),
      ),
    );
  }
}
