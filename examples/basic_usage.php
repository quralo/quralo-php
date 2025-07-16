<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Quralo\Quralo;

echo "=== Quralo PHP SDK - Healthcare System Integration Tools ===\n";
echo "=== Integración de Sistemas Hospitalarios con Plataforma Quralo ===\n\n";

// Initialize the Quralo SDK for hospital integration
$quralo = Quralo::create();

// Example 1: Structure patient data for hospital integration
echo "1. Structuring patient data for Quralo platform integration...\n";
$organizationId = 'HOSP-CENTRAL-ARG-001';
// Patient data from hospital information system (HIS)
$patient = array(
    'lastname' => 'García',
    'firstname' => 'María Elena',
    'person_sex' => 'F',
    'date_of_birth' => '1985-07-12',
    'person_id_type' => 'DNI',
    'person_id_number' => '35478961'
);

// Medical staff data (doctor, nurse, admin who registers)
$medicalStaff = array(
    'person_id_type' => 'DNI',
    'person_id_number' => '20123456'
);

// Clinical metadata from hospital system
$clinicalMetadata = array(
    'registration_date' => '2024-01-15T14:30:00Z',
    'department' => 'Emergencias',
    'priority' => 'alta',
    'internal_patient_id' => 'PAT-2024-001',
    'attending_physician' => 'Dr. Rodriguez',
    'room_number' => '301A'
);

$structuredData = $quralo->structureData($organizationId, $patient, $medicalStaff, $clinicalMetadata);
echo "Quralo-compatible structured data:\n";
echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Example 2: Generate QR code for healthcare workflows
echo "2. Generating medical QR code for hospital workflows...\n";
if (extension_loaded('gd')) {
    $qrCodeDataUri = $quralo->generateQrCode($organizationId, $patient, $medicalStaff, $clinicalMetadata);
    echo "QR Code generated (Data URI): " . substr($qrCodeDataUri, 0, 50) . "...\n";
    echo "QR code length: " . strlen($qrCodeDataUri) . " characters\n\n";
} else {
    echo "GD extension not available - QR code generation skipped\n\n";
}

// Example 3: Generate QR code from already structured data
echo "3. Generating QR code from structured data...\n";
if (extension_loaded('gd')) {
    $qrCodeFromStructured = $quralo->generateQrCodeFromStructuredData($structuredData);
    echo "QR Code from structured data: " . substr($qrCodeFromStructured, 0, 50) . "...\n\n";
} else {
    echo "GD extension not available - QR code generation skipped\n\n";
}

// Example 4: Different person with minimal data
echo "4. Example with minimal person data...\n";
$minimalPerson = array(
    'lastname' => 'Rodríguez',
    'firstname' => 'Carlos'
    // Other fields will be filled with empty strings
);
$minimalStructured = $quralo->structureData('CLINIC-PERIFERICA-001', $minimalPerson, $medicalStaff);
echo "Minimal structured data:\n";
echo json_encode($minimalStructured, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Example 5: Custom QR code options with logo
echo "5. Generating QR code with custom options and logo...\n";
if (extension_loaded('gd')) {
    $customQrOptions = array(
        'size' => 8,
        'margin' => 2,
        'error_correction' => 'M',
        'include_logo' => true
    );
    $customQrCode = $quralo->generateQrCode(
        'ORG-CUSTOM',
        array('lastname' => 'López', 'firstname' => 'Ana'),
        array('person_id_type' => 'PASSPORT', 'person_id_number' => 'AB123456'),
        array('custom_field' => 'custom_value'),
        $customQrOptions
    );
    echo "Custom QR Code generated with size 8, margin 2, medium error correction, and Quralo logo\n";
    echo "QR code length: " . strlen($customQrCode) . " characters\n\n";
} else {
    echo "GD extension not available - QR code generation skipped\n\n";
}

// Example 6: QR code without logo
echo "6. Generating QR code without logo...\n";
if (extension_loaded('gd')) {
    $noLogoOptions = array(
        'size' => 6,
        'margin' => 2,
        'error_correction' => 'M',
        'include_logo' => false
    );
    $noLogoQrCode = $quralo->generateQrCode(
        'ORG-NO-LOGO',
        array('lastname' => 'Martínez', 'firstname' => 'Pedro'),
        array('person_id_type' => 'DNI', 'person_id_number' => 'DN123456'),
        null,
        $noLogoOptions
    );
    echo "QR Code generated without logo\n";
    echo "QR code length: " . strlen($noLogoQrCode) . " characters\n\n";
} else {
    echo "GD extension not available - QR code generation skipped\n\n";
}

// Example 7: Save QR code to file (optional)
echo "7. Saving QR code to file...\n";
if (extension_loaded('gd') && isset($qrCodeDataUri)) {
    $base64Data = str_replace('data:image/png;base64,', '', $qrCodeDataUri);
    $imageData = base64_decode($base64Data);
    file_put_contents('qr_code_example.png', $imageData);
    echo "QR code saved to: qr_code_example.png\n\n";
} else {
    echo "GD extension not available or QR code not generated - file save skipped\n\n";
}

// Example 8: Test data structure
echo "8. Testing data structure...\n";
$testData = $quralo->structureData($organizationId, $patient, $medicalStaff, $clinicalMetadata);

// Show the structured data format
$originalJson = json_encode($testData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo "Structured data format:\n";
echo $originalJson . "\n";
echo "Data size: " . strlen(json_encode($testData, JSON_UNESCAPED_UNICODE)) . " characters\n";
echo "Note: Data is automatically compressed when generating QR codes\n";

echo "\n=== Hospital integration examples completed successfully! ===\n";
echo "Ready for production use in healthcare environments.\n";
