import 'package:flutter/material.dart';

class ContactScreen extends StatelessWidget {
  const ContactScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('We create beautiful sculptures which last forever'),
          const SizedBox(height: 8),
          const Text('Phone: +1 866-682-5837'),
          const SizedBox(height: 8),
          const Text('Email: info@theangelstones.com'),
          const SizedBox(height: 8),
          const Text('Mailing Address: P.O. Box 370, Elberton, GA 30635'),
          const SizedBox(height: 8),
          const Text('Physical Addresses:'),
          const Text('1187 Old Middleton Rd, Elberton, GA 30635'),
          const Text('203 Williams St, Elberton, GA'),
          const Text('15 Blackwell St, Barre, VT'),
          const SizedBox(height: 8),
          const Text('Corporate Address: 5540 Centerview Dr, ste 204, PMB 162836, Raleigh, NC'),
          const SizedBox(height: 8),
          TextButton(
            onPressed: () {
              // Launch map link
            },
            child: const Text('View Map'),
          ),
        ],
      ),
    );
  }
}
