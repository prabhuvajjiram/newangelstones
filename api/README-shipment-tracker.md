# WebTracker Shipment Data Scraper API

## Overview
This API scrapes shipment data from the WebTracker Wisegrid system. It handles authentication, navigation, and extraction of all shipment information (60+ columns).

## How It Works

1. **Authentication**: The API logs into the WebTracker Wisegrid system using the provided credentials.
2. **Navigation**: It accesses the shipments page and submits search parameters.
3. **Data Extraction**: Extracts shipment data from the response HTML.
4. **Data Processing**: Returns a structured JSON response with all shipment details.

## Usage

### Direct API Call

```
https://yourdomain.com/api/shipment-data-scraper.php
```

### Optional Parameters

You can override default credentials by POSTing:

```
{
  "companyCode": "ANGSTORAG",
  "email": "your-email",
  "password": "your-password"
}
```

### Response Format

```json
{
  "success": true,
  "headers": ["Shipment#", "Bill", "Shipper", "Consignee", ...],
  "shipments": [
    {
      "Shipment#": "SMTOU123456",
      "Bill": "MAEU1234567890",
      ...
    },
    ...
  ]
}
```

## Error Handling

- HTTP 200: Success - data returned
- HTTP 401: Authentication failed
- HTTP 500: Server error or scraping failure

## Implementation Notes

1. The API tries multiple approaches to locate and extract shipment data tables.
2. It uses DOM parsing for reliable data extraction even when table structures change.
3. Logs detailed debugging information to `/logs/scraper-debug.log`.
4. Falls back to sample data only if scraping fails completely.

## Integration

To integrate with other systems:

```php
// Example API client
$url = 'https://yourdomain.com/api/shipment-data-scraper.php';
$response = file_get_contents($url);
$shipmentData = json_decode($response, true);

// Process shipment data
foreach ($shipmentData['shipments'] as $shipment) {
  // Do something with each shipment record
}
```
