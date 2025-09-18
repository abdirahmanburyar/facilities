<?php

// Quick test for batch_no conversion
echo "Testing batch_no conversion:\n";

$testValues = [
    123,           // Number
    "ABC123",      // String
    "123.45",      // Decimal as string
    123.45,        // Decimal
    null,          // Null
    "",            // Empty string
];

foreach ($testValues as $value) {
    $converted = (string) ($value ?? '');
    echo "Original: " . var_export($value, true) . " → Converted: '$converted'\n";
}

echo "\nAll conversions should work without validation errors!\n";
