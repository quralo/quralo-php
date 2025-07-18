# Quralo PHP SDK

Librería para integración de sistemas de salud con la plataforma Quralo. Permite generar códigos QR compactos y seguros para flujos de trabajo interoperables.

## Modos soportados
- `plain`: JSON comprimido (gzip+base64url), sin firma ni cifrado.
- `secure`: JSON comprimido y cifrado con AES-256-CBC, autenticado con HMAC-SHA256 usando una única clave `client_secret`.

## Formato del QR

```
QRL|v=1|id=<client_id>|ts=<timestamp>|data=<base64url(ciphertext)>|mac=<base64url(hmac)>
```
- `v`: versión del esquema.
- `id`: Client ID (visible, no cifrado).
- `ts`: timestamp de expiración o generación.
- `data`: payload comprimido y cifrado (base64url).
- `mac`: HMAC-SHA256 de todo lo anterior (base64url), para integridad.

## Ejemplo de uso

```php
use Quralo\Quralo;

$ecl = Quralo::ecl();
$clientId = 'cliente123';
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
$qr1 = $ecl->generateQrCode($clientId, $person, $author, $metadata, [
    'format' => 'plain'
]);

// QR seguro (firmado y cifrado)
$clientSecret = random_bytes(32); // Puede ser binario, base64 o hex
$qr2 = $ecl->generateQrCode($clientId, $person, $author, $metadata, [
    'format' => 'secure',
    'client_secret' => $clientSecret,
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

function decode_secure_qr($qrPayload, $clientSecret) {
    $parts = [];
    foreach (explode('|', $qrPayload) as $kv) {
        if (strpos($kv, '=') !== false) {
            list($k, $v) = explode('=', $kv, 2);
            $parts[$k] = $v;
        }
    }
    if (!isset($parts['data']) || !isset($parts['mac']) || !isset($parts['ts']) || !isset($parts['id'])) {
        throw new Exception("Formato de QR inválido");
    }
    $base = 'QRL|v=1|id=' . $parts['id'] . '|ts=' . $parts['ts'] . '|data=' . $parts['data'];
    if (ctype_xdigit($clientSecret) && strlen($clientSecret) === 64) {
        $clientSecret = hex2bin($clientSecret);
    }
    $expectedMac = hash_hmac('sha256', $base, $clientSecret, true);
    $expectedMac_b64url = rtrim(strtr(base64_encode($expectedMac), '+/', '-_'), '=');
    if (!hash_equals($expectedMac_b64url, $parts['mac'])) {
        throw new Exception("MAC inválido");
    }
    $bin = base64url_decode($parts['data']);
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
    if ($now > intval($parts['ts'])) {
        throw new Exception("El QR ha expirado.");
    }
    return $parsed;
}
```

---

## Notas
- Para 'secure', la clave puede ser binaria de 32 bytes, hex (64 chars) o base64 (44 chars). La librería la normaliza automáticamente.
- El QR generado es compacto, seguro y solo tu backend puede descifrarlo y validarlo.
- No se usa JWT/JWS/JWE ni claves públicas/privadas asimétricas.
- El método `generateQrCode` retorna la imagen PNG en base64 (data URI).
- Se puede incluir un logo en el QR con la opción `include_logo` (requiere GD y logo-qr.png).
