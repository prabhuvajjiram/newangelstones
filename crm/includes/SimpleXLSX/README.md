# SimpleXLSX Library

A lightweight Excel (.xlsx) file parser for Angel Stones CRM. This library is a simplified version of [SimpleXLSX](https://github.com/shuchkin/simplexlsx) adapted for our specific needs.

## Features
- Parse Excel (.xlsx) files without external dependencies
- Extract data from worksheets
- Handle shared strings
- Basic error handling

## Usage
```php
require_once 'SimpleXLSX.php';

// Parse Excel file
$xlsx = SimpleXLSX::parse('file.xlsx');
if ($xlsx) {
    // Get rows from first worksheet
    $rows = $xlsx->rows();
    foreach ($rows as $row) {
        // Process each row
        print_r($row);
    }
}
```

## Integration with Supplier Invoice System
This library is used by the supplier invoice system to process Excel-based invoices. It handles the extraction of invoice items, quantities, prices, and other relevant data based on supplier-specific templates.
