<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Quralo\Quralo;

echo "=== Quralo PHP SDK - Healthcare System Integration Tools ===\n";
echo "=== Integración de Sistemas Hospitalarios con Plataforma Quralo ===\n\n";

$organizationId = 'c710e909-067a-4b05-8679-5a386cdd5e92';
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

// QR plano
$dataUriQrPlain = $ecl->generateQrCode($organizationId, $person, $author, $metadata, array(
    'format' => 'plain',
    'ttl_seconds' => 600,
    'include_logo' => false,
));

file_put_contents(__DIR__ . '/plain_qr.png', base64_decode(str_replace('data:image/png;base64,', '', $dataUriQrPlain)));
echo "QR plano generado y guardado como plain_qr.png\n";

$encryptionKey = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53'; // clave AES de 256 bits (64 chars hex)
$signingKey = 'f0dce5b6a0bd24ff07aaec8835cfee7855bc6ccb6ffa9da5ee57ec7ea49b3c25';    // clave HMAC-SHA256 (64 chars hex)

$dataUriQrSecure = $ecl->generateQrCode($organizationId, $person, $author, $metadata, array(
    'format' => 'secure',
    'signing_key' => $signingKey,
    'encryption_key' => $encryptionKey,
    'ttl_seconds' => 600,
    'include_logo' => false,
));

file_put_contents(__DIR__ . '/secure_qr.png', base64_decode(str_replace('data:image/png;base64,', '', $dataUriQrSecure)));
echo "\nQR seguro generado y guardado como secure_qr.png\n";

echo "\n=== Hospital integration examples completed successfully! ===\n";
echo "Ready for production use in healthcare environments.\n";

// Decodificación del QR seguro generado (en PHP)
function base64url_decode_php($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function decode_secure_qr($qrPayload, $encryptionKey, $signingKey) {
    // Elimina prefijo si existe
    if (strpos($qrPayload, 'qrl:1:ecl:s:') === 0) {
        $qrPayload = substr($qrPayload, strlen('qrl:1:ecl:s:'));
    }

    // Decodifica base64url
    $bin = base64url_decode_php($qrPayload);

    // Extrae IV (primeros 16 bytes)
    $iv = substr($bin, 0, 16);
    $ciphertext = substr($bin, 16);

    // Normaliza claves (hex a binario si corresponde)
    if (ctype_xdigit($encryptionKey) && strlen($encryptionKey) === 64) {
        $encryptionKey = hex2bin($encryptionKey);
    }
    if (ctype_xdigit($signingKey) && strlen($signingKey) === 64) {
        $signingKey = hex2bin($signingKey);
    }

    // Descifra con AES-256-CBC
    $payload = openssl_decrypt($ciphertext, 'AES-256-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
    if ($payload === false) {
        throw new Exception("Error al descifrar el QR");
    }

    // Separa firma (últimos 32 bytes) y datos comprimidos
    $signature = substr($payload, -32);
    $compressedData = substr($payload, 0, -32);

    // Verifica HMAC
    $expectedSig = hash_hmac('sha256', $compressedData, $signingKey, true);
    if (!hash_equals($signature, $expectedSig)) {
        throw new Exception("Firma HMAC inválida.");
    }

    // Descomprime
    $json = @gzuncompress($compressedData);
    if ($json === false) {
        throw new Exception("Error al descomprimir los datos");
    }

    // Decodifica JSON
    $parsed = json_decode($json, true);

    // Verifica expiración
    $now = time();
    if (isset($parsed['e']) && $now > $parsed['e']) {
        throw new Exception("El QR ha expirado.");
    }

    return $parsed;
}

// Prueba de decodificación del QR seguro generado
try {
    // Extrae el payload del QR seguro generado
    $payloadSecure = $ecl->encodePayload($organizationId, $person, $author, $metadata, 'secure', $signingKey, $encryptionKey, 600);

    $resultado = decode_secure_qr($payloadSecure, $encryptionKey, $signingKey);

    echo "\nDecodificación del QR seguro:\n";
    print_r($resultado);
} catch (Exception $e) {
    echo "\nError al decodificar el QR seguro: " . $e->getMessage() . "\n";
}
