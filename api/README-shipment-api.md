# Angel Stones Shipment Tracking API

This API provides endpoints for accessing shipment tracking data from the WebTracker system. A CRON job is scheduled to run at 6 AM and 6 PM EST.

## Endpoints

### List All Shipments

```
GET /api/listShipments
```

Returns all shipment tracking numbers stored in the database.

**Authentication Required**: Yes (Bearer Token)

**Response Example**:
```json
{
  "count": 3,
  "shipments": [
    "STP12345",
    "STP23456",
    "STP34567"
  ],
  "timestamp": "2025-04-21 03:15:27"
}
```

### Get Shipment Details

```
GET /api/getShippingDetails/{shipment_number}
```

Returns detailed information about a specific shipment.

**Authentication Required**: Yes (Bearer Token)

**Response Example**:
```json
{
  "shipment": {
    "id": 1,
    "shipment_number": "STP12345",
    "bill": "BOL123456",
    "shipper": "Supplier Inc",
    "consignee": "Angel Stones",
    "origin": "China",
    "destination": "Savannah",
    "eta": "2025-05-15",
    "status": "In Transit"
    // Additional fields...
  },
  "timestamp": "2025-04-21 03:15:27"
}
```

## Authentication

All API requests require authentication using a Bearer token in the Authorization header:

The API returns standard HTTP status codes:

- **200**: Success
- **400**: Bad Request (e.g., missing parameters)
- **401**: Unauthorized (missing or invalid token)
- **404**: Not Found (shipment not found)
- **500**: Server Error

Error response format:
```json
{
  "error": "Error type",
  "message": "Detailed error message",
  "timestamp": "2025-04-21 03:15:27"
}
```

## Example Usage

curl -H "Authorization: Bearer AngelStones2025ApiToken" "https://theangelstones.com/api/shipping_endpoints.php?endpoint=listShipments"

curl -H "Authorization: Bearer AngelStones2025ApiToken" "https://theangelstones.com/api/shipping_endpoints.php?endpoint=getShippingDetails&id=SMAA00194463"