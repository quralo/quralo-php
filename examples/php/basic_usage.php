<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Quralo\Quralo;

echo "=== Quralo PHP SDK - Healthcare System Integration Tools ===\n";
echo "=== Integración de Sistemas Hospitalarios con Plataforma Quralo ===\n\n";

$clientId = 'c710e909-067a-4b05-8679-5a386cdd5e92';
$clientSecret = '7a04d80b5df7ed36701a66d0d7e23ade304bf24d49d4dc8064b77fbd25ec277a'; // 32 bytes hex
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
