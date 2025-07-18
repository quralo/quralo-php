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

### Modos soportados
- `plain`: JSON comprimido (gzip+base64url), sin firma ni cifrado.
- `secure`: JSON comprimido y cifrado con AES-256-CBC, autenticado con HMAC-SHA256 usando una única clave `client_secret`.

### Formato del QR

```
QRL|v=1|<client_id>|<timestamp>|<data>|<mac>
```
- **QRL**: prefijo fijo
- **v=1**: versión del esquema
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

### Opciones adicionales
- `size`: tamaño del QR (por defecto 6)
- `margin`: margen (por defecto 2)
- `error_correction`: nivel de corrección ('L', 'M', 'Q', 'H'; por defecto 'M')
- `include_logo`: incluir logo en el QR (requiere GD y logo-qr.png)


### Notas
- Para 'secure', la clave puede ser binaria de 32 bytes, hex (64 chars) o base64 (44 chars). La librería la normaliza automáticamente.
- El QR generado es compacto, seguro y solo tu backend puede descifrarlo y validarlo.
- No se usa JWT/JWS/JWE ni claves públicas/privadas asimétricas.
- El método `generateQrCode` retorna la imagen PNG en base64 (data URI).
- Se puede incluir un logo en el QR con la opción `include_logo` (requiere GD y logo-qr.png).
