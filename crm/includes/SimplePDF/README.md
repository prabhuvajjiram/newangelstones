# SimplePDF Library

A lightweight PDF text extraction library for Angel Stones CRM. This library provides basic PDF text extraction capabilities without external dependencies.

## Features
- Extract text content from PDF files
- Support for both pdftotext (if available) and basic PHP parsing
- Simple error handling
- No external dependencies

## Usage
```php
require_once 'SimplePDF.php';

try {
    // Extract text from PDF
    $text = SimplePDF::parse('invoice.pdf');
    if (!empty($text)) {
        // Process the extracted text
        echo $text;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Integration with Supplier Invoice System
This library is used by the supplier invoice system to process PDF-based invoices. It extracts text content which is then parsed according to supplier-specific templates to identify invoice items, quantities, prices, and other relevant data.
