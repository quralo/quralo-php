<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Quralo\Quralo;

echo "=== Quralo PHP SDK - Healthcare System Integration Tools ===\n";
echo "=== Integración de Sistemas Hospitalarios con Plataforma Quralo ===\n\n";

$clientId = 'c710e909-067a-4b05-8679-5a386cdd5e92';
$clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53'; // 32 bytes hex
$person = [
    'lastname' => 'Smith',
    'firstname' => 'John',
    'person_sex' => 'm',
    'date_of_birth' => '1990-03-15',
    'person_id_type' => 'national_id',
    'person_id_number' => '12345678'
];
$author = [
    'person_id_type' => 'national_id',
    'person_id_number' => '87654321'
];
$metadata = [
    'chapter_id' => "123",
    'patient_id' => "4566",
    'doctor_id' => "643"
];

$ecl = Quralo::ecl();

// QR seguro (siempre)
$dataUriQrSecure = $ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, array(
    'ttl_seconds' => 600,
    'include_logo' => false,
));

file_put_contents(__DIR__ . '/secure_qr.png', base64_decode(str_replace('data:image/png;base64,', '', $dataUriQrSecure)));
echo "\nQR seguro generado y guardado como secure_qr.png\n";

echo "\n=== Hospital integration examples completed successfully! ===\n";
echo "Ready for production use in healthcare environments.\n";

/**
 * Decodifica un QR seguro en el formato:
 * QRL|v=1|ecl|<client_id>|<timestamp>|<data>|<mac>
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

function decode_secure_qr($qrPayload, $clientSecret) {
    $parts = explode('|', $qrPayload);
    if (count($parts) !== 7 || $parts[0] !== 'QRL' || $parts[1] !== 'v=1' || $parts[2] !== 'ecl') {
        throw new Exception("Formato de QR inválido");
    }
    list(, , $module, $clientId, $timestamp, $data_b64url, $mac_b64url) = $parts;
    $base = implode('|', array_slice($parts, 0, 6)); // QRL|v=1|ecl|<client_id>|<timestamp>|<data>
    if (ctype_xdigit($clientSecret) && strlen($clientSecret) === 64) {
        $clientSecret = hex2bin($clientSecret);
    }
    $expectedMac = hash_hmac('sha256', $base, $clientSecret, true);
    $expectedMac_b64url = rtrim(strtr(base64_encode($expectedMac), '+/', '-_'), '=');
    if (!hash_equals($expectedMac_b64url, $mac_b64url)) {
        throw new Exception("MAC inválido");
    }
    $bin = base64url_decode_php($data_b64url);
    $iv = substr($bin, 0, 16);
    $ciphertext = substr($bin, 16);
    $compressed = openssl_decrypt($ciphertext, 'AES-256-CBC', $clientSecret, OPENSSL_RAW_DATA, $iv);
    if ($compressed === false) {
        throw new Exception("Error al descifrar el QR");
    }
    $json = @gzuncompress($compressed);
    if ($json === false) {
        throw new Exception("Error al descomprimir los datos");
    }
    $parsed = json_decode($json, true);
    $now = time();
    if ($now > intval($timestamp)) {
        throw new Exception("El QR ha expirado.");
    }
    return $parsed;
}

// Prueba de decodificación del QR seguro generado
try {
    $payloadSecure = $ecl->encodePayload($clientId, $clientSecret, $person, $author, $metadata, 600);
    $resultado = decode_secure_qr($payloadSecure, $clientSecret);
    echo "\nDecodificación del QR seguro:\n";
    print_r($resultado);
} catch (Exception $e) {
    echo "\nError al decodificar el QR seguro: " . $e->getMessage() . "\n";
}
