# Quralo PHP SDK

Librería para integración de sistemas de salud con la plataforma Quralo. Permite generar códigos QR compactos y seguros para flujos de trabajo interoperables.

La SDK de Quralo está diseñada en módulos independientes para facilitar la integración flexible con distintos flujos y sistemas. Actualmente, el único módulo disponible es:

## Vinculación de Contexto Externo (External Context Linking, ECL)

El módulo ECL permite que sistemas externos —como un HIS (Health Information System) u otros integradores— adjunten un bloque de información contextual personalizada al interactuar con Quralo. Esto es útil tanto al enviar solicitudes desde el HIS hacia Quralo, como cuando un usuario utiliza Quralo (por ejemplo, escaneando con Quralo Médicos un QR generado por el HIS).

Quralo no interpreta ni modifica este contexto: simplemente lo almacena temporalmente y lo devuelve intacto junto con la respuesta o cuando se solicite en futuras interacciones.

### 📦 ¿Qué es el "contexto externo"?
El "contexto externo" es cualquier paquete de datos (por ejemplo, un identificador, metadatos o referencias internas del HIS) que el sistema externo necesita mantener a lo largo de una transacción, acción del usuario o evento. Quralo actúa como un "contenedor sellado", transportando este contexto sin conocer ni depender de su estructura o propósito.

### 🧩 Casos de uso
- Integraciones con sistemas clínicos (HIS) que requieren que se les devuelva información propia para mantener la coherencia de estado y contextualización de la acción del usuario.
- Escenarios donde Quralo actúa como proxy o middleware, sin lógica de negocio propia sobre el contexto.
- Mejora la interoperabilidad asimétrica, permitiendo que el sistema externo mantenga su lógica y control sin imponerla a Quralo.

### ⚠️ Consideraciones
- El contenido del contexto no se valida ni se interpreta: es responsabilidad del sistema externo asegurar su integridad y uso adecuado.
- Si el contexto contiene datos sensibles o identificadores, considerar las implicancias en auditoría, cumplimiento y privacidad según las políticas vigentes.

### Formato del QR

```
QRL|v=1|ecl|<client_id>|<timestamp>|<data>|<mac>
```
- **QRL**: prefijo fijo
- **v=1**: versión del esquema
- **ecl**: identificador del módulo (actualmente único)
- **<client_id>**: identificador del cliente (visible, no cifrado)
- **<timestamp>**: expiración (UNIX epoch, segundos)
- **<data>**: payload comprimido y cifrado (base64url)
- **<mac>**: HMAC-SHA256 de la cadena anterior (base64url), para integridad

> **Importante:** El orden de los campos es estricto y no se incluyen nombres de campo. El significado de cada campo depende de la posición.

### Ejemplo de uso

```php
use Quralo\Quralo;

$ecl = Quralo::ecl();
$clientId = 'c710e909-067a-4b05-8679-5a386cdd5e92';
$clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53';
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

$qr = $ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, [
    'ttl_seconds' => 300 // opcional
]);
```

### Opciones adicionales
- `size`: tamaño del QR (por defecto 6)
- `margin`: margen (por defecto 2)
- `error_correction`: nivel de corrección ('L', 'M', 'Q', 'H'; por defecto 'M')
- `include_logo`: incluir logo en el QR (requiere GD y logo-qr.png)

### Verificación y descifrado en backend (PHP)

```php
/**
 * Decodifica un QR seguro en el formato:
 * QRL|v=1|ecl|<client_id>|<timestamp>|<data>|<mac>
 * - ecl: identificador del módulo
 * - client_id: identificador del cliente
 * - timestamp: expiración (UNIX)
 * - data: payload comprimido y cifrado (base64url)
 * - mac: HMAC-SHA256 de la cadena anterior (base64url)
 */
function base64url_decode($data) {
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
    $bin = base64url_decode($data_b64url);
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
```

### Notas
- La clave puede ser binaria de 32 bytes, hex (64 chars) o base64 (44 chars). La librería la normaliza automáticamente.
- El QR generado es compacto, seguro y solo tu backend puede descifrarlo y validarlo.
- No se usa JWT/JWS/JWE ni claves públicas/privadas asimétricas.
- El método `generateQrCode` retorna la imagen PNG en base64 (data URI).
- Se puede incluir un logo en el QR con la opción `include_logo` (requiere GD y logo-qr.png).
