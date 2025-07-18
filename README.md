# Quralo PHP SDK

Librería para integración de sistemas de salud con la plataforma Quralo. Permite generar códigos QR compactos y seguros para flujos de trabajo interoperables.

## Modos soportados
- `plain`: JSON comprimido (gzip+base64), sin firma ni cifrado.
- `secure`: JSON comprimido, firmado con HMAC-SHA256 y cifrado con AES-256-CBC (requiere `signing_key` y `encryption_key`).

## Ejemplo de uso

```php
use Quralo\Quralo;

$ecl = Quralo::ecl();
$person = [
    'lastname' => 'Pérez',
    'firstname' => 'Ana',
    'person_sex' => 'F',
    'date_of_birth' => '1990-01-01',
    'person_id_type' => 'DNI',
    'person_id_number' => '12345678',
];
$author = [
    'person_id_type' => 'DNI',
    'person_id_number' => '87654321',
];
$metadata = [ 'vacuna' => 'COVID-19', 'dosis' => 2 ];

// QR plano
$qr1 = $ecl->generateQrCode('ORG001', $person, $author, $metadata, [
    'format' => 'plain'
]);

// QR seguro (firmado y cifrado)
$signingKey = random_bytes(32); // Puede ser binario, base64 o hex
$encryptionKey = random_bytes(32);
$qr2 = $ecl->generateQrCode('ORG001', $person, $author, $metadata, [
    'format' => 'secure',
    'signing_key' => $signingKey,
    'encryption_key' => $encryptionKey,
    'ttl_seconds' => 300 // opcional
]);
```

## Opciones adicionales
- `size`: tamaño del QR (por defecto 6)
- `margin`: margen (por defecto 2)
- `error_correction`: nivel de corrección ('L', 'M', 'Q', 'H'; por defecto 'M')
- `include_logo`: incluir logo en el QR (requiere GD y logo-qr.png)

## Verificación y descifrado en backend (PHP)

```php
function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function verify_and_decrypt($qr, $signingKey, $encryptionKey) {
    $raw = base64url_decode(substr($qr, 8)); // quitar 'qrl:v1:s:'
    $iv = substr($raw, 0, 16);
    $ciphertext = substr($raw, 16);
    $payload = openssl_decrypt($ciphertext, 'AES-256-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
    $compressed = substr($payload, 0, -32);
    $signature = substr($payload, -32);
    if (!hash_equals(hash_hmac('sha256', $compressed, $signingKey, true), $signature)) {
        return false; // Firma inválida
    }
    $json = gzuncompress($compressed);
    $data = json_decode($json, true);
    if ($data['e'] < time()) {
        return false; // Expirado
    }
    return $data;
}
```

---

## Notas
- Para 'secure', las claves pueden ser binarios de 32 bytes, hex (64 chars) o base64 (44 chars). La librería las normaliza automáticamente.
- El QR generado es compacto, seguro y solo tu backend puede descifrarlo y validarlo.
- No se usa JWT/JWS/JWE ni claves públicas/privadas asimétricas.
- El método `generateQrCode` retorna la imagen PNG en base64 (data URI).
- Se puede incluir un logo en el QR con la opción `include_logo` (requiere GD y logo-qr.png).
