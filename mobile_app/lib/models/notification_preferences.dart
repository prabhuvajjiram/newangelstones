import 'package:flutter/material.dart';

class NotificationPreferences {
  bool newColors;
  bool newProducts;
  bool inventoryUpdates;
  bool specialOffers;
  TimeOfDay? quietStart;
  TimeOfDay? quietEnd;

  NotificationPreferences({
    this.newColors = true,
    this.newProducts = true,
    this.inventoryUpdates = true,
    this.specialOffers = true,
    this.quietStart,
    this.quietEnd,
  });
}
