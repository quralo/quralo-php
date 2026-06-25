<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Quralo\Quralo;

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        putenv($key . '=' . $value);
    }
}

echo "=== Quralo PHP SDK - Healthcare System Integration Tools ===\n";
echo "=== Integración de Sistemas Hospitalarios con Plataforma Quralo ===\n\n";

$clientId = getenv('QURALO_CLIENT_ID') ?: '';
$clientSecret = getenv('QURALO_CLIENT_SECRET') ?: ''; 
$person = [
    'lastname' => 'Garzolana',
    'firstname' => 'Roberto',
    'person_sex' => 'male',
    'date_of_birth' => '1990-01-08',
    'person_id_type' => 'national_id',
    'person_id_number' => '35113456',
    'person_id_country' => 'AR',
    'email' => 'frossi+01@quralo.com',
    'phone_number' => '+34123456789',
];
$author = [
    'person_id_type' => 'national_id',
    'person_id_number' => '35113456',
    'person_id_country' => 'AR',
];
$metadata = [
    'chapter_id' => "123",
    'patient_id' => "4566",
    'doctor_id' => "643"
];

$ecl = Quralo::ecl();

// QR seguro (siempre)
$dataUriQrSecure = $ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, array(
    'ttl_seconds' => 7200,
    'include_logo' => false,
));

file_put_contents(__DIR__ . '/secure_qr.png', base64_decode(str_replace('data:image/png;base64,', '', $dataUriQrSecure)));
echo "\nQR seguro generado y guardado como secure_qr.png\n";

echo "\n=== Hospital integration examples completed successfully! ===\n";
echo "Ready for production use in healthcare environments.\n";

/**
 * Decodifica un QR seguro en el formato:
 * QRL|1|ecl|<client_id>|<timestamp>|<data>|<mac>
 * - ecl: identificador del módulo
 * - client_id: identificador del cliente
 * - timestamp: expiración (UNIX)
 * - data: payload comprimido y cifrado (base64url)
 * - mac: HMAC-SHA256 de la cadena anterior (base64url)
 */
function base64url_decode_php($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

// Prueba de decodificación del QR seguro generado
try {
    $payloadSecure = $ecl->encodePayload($clientId, $clientSecret, $person, $author, $metadata, 600);
    $payloadDecoded = $ecl->decodePayload($payloadSecure, $clientSecret);
    echo "\nDecodificación del QR seguro:\n";
    print_r($payloadDecoded);
} catch (Exception $e) {
    echo "\nError al decodificar el QR seguro: " . $e->getMessage() . "\n";
}
