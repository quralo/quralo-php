# Quralo PHP SDK

Librería para integración de sistemas de salud con la plataforma Quralo. Permite generar códigos QR compactos y seguros para flujos de trabajo interoperables.

La SDK de Quralo está diseñada en módulos independientes para facilitar la integración flexible con distintos flujos y sistemas.

## Instalación

Instalá la librería en tu proyecto PHP usando Composer:

```bash
composer require quralo/quralo-php
```

Esto descargará e instalará automáticamente la última versión estable de la SDK y sus dependencias.

## Módulo de Vinculación de Contexto Externo (External Context Linking, ECL)

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
QRL|<schema_version>|<module_id>|<client_id>|<timestamp>|<crypto_version>|<encrypted_data>|<mac>
```
- **QRL**: prefijo fijo que identifica a un QR de Quralo
- **`schema_version`**: versión del esquema (ej: "1")
- **`module_id`**: identificador del módulo (actualmente "ecl" es el único módulo disponible)
- **`client_id`**: identificador del cliente (visible, no cifrado)
- **`timestamp`**: expiración (UNIX epoch, segundos)
- **`crypto_version`**: versión del cifrado (ej: "1A")
- **`encrypted_data`**: datos comprimidos y cifrados (formato salt:iv:ciphertext, base64url)
- **`mac`**: HMAC-SHA256 de la cadena anterior (base64url), para integridad

> **Importante:** El orden de los campos es estricto y no se incluyen nombres de campo. El significado de cada campo depende de la posición.

### Ejemplo de uso

```php
use Quralo\Quralo;

$ecl = Quralo::ecl();
$clientId = 'c710e909-067a-4b05-8679-5a386cdd5e92';
$clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53'; // binario, hex o base64
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
$metadata = [ 'patient_id' => '1', 'chapter_id' => 2 ];

// Generar el string QR seguro
$payload = $ecl->encodePayload($clientId, $clientSecret, $person, $author, $metadata);

// Decodificar y validar el payload seguro
$data = $ecl->decodePayload($payload, $clientSecret);
// $data contiene el array original con las claves 't', 'p', 'a', 'm'

// Generar código QR PNG (data URI)
$qrImage = $ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, [
    'ttl_seconds' => 300,
    'size' => 6,
    'margin' => 2,
    'error_correction' => 'M',
    'include_logo' => true // requiere GD y logo-qr.png
]);
```

### Opciones adicionales
- `size`: tamaño del QR (por defecto 6)
- `margin`: margen (por defecto 2)
- `error_correction`: nivel de corrección ('L', 'M', 'Q', 'H'; por defecto 'M')
- `include_logo`: incluir logo en el QR (requiere GD y logo-qr.png)

### Decodificación y validación en backend (PHP)

El método recomendado es usar `$ecl->decodePayload($payload, $clientSecret)`, que valida el formato, el HMAC y descifra el contenido, devolviendo el array original.

Si el HMAC o el formato no son válidos, lanza una excepción.

### Notas
- La clave puede ser binaria de 32 bytes, hex (64 chars) o base64 (44 chars). La librería la normaliza automáticamente.
- El QR generado es compacto, seguro y solo tu backend puede descifrarlo y validarlo.
- El método `encodePayload` retorna el string QR seguro, y `decodePayload` lo decodifica y valida (incluyendo HMAC y formato).
- No se usa JWT/JWS/JWE ni claves públicas/privadas asimétricas.
- El método `generateQrCode` retorna la imagen PNG en base64 (data URI).
- Se puede incluir un logo en el QR con la opción `include_logo` (requiere GD y logo-qr.png).

## Mantenimiento y Publicación

Si actúas como mantenedor de esta SDK y necesitas generar una **versión compilada** (un solo archivo PHP, un archivo Phar, o una distribución personalizada que incluya la carpeta `vendor`), ten en cuenta lo siguiente:

### Solución a conflictos de constantes (`PHPQRCode`)

La librería `aferrandini/phpqrcode` define constantes globales (como `QR_CACHEABLE`) sin verificar si ya existen. Esto puede causar errores de tipo `Notice: Constant already defined` en entornos de clientes que ya tengan cargada otra versión de la misma librería.

Para evitar esto, se han implementado dos medidas:

1.  **Supresión en ejecución:** El código en `src/Ecl.php` utiliza `@require_once` para silenciar cualquier aviso durante la carga de la dependencia.
2.  **Parche automático en `vendor`:** El archivo `composer.json` incluye scripts que parchean automáticamente la librería externa después de cada `install` o `update`.

### Pasos antes de publicar / compilar

Cada vez que vayas a generar una nueva versión de la librería para distribuir a los clientes, asegúrate de seguir estos pasos:

1.  Actualiza o reinstala las dependencias para asegurar que el parche se aplique:
    ```bash
    composer install
    ```
    *(Esto ejecutará automáticamente el script `post-install-cmd` que envuelve las constantes en bloques `if (!defined(...))`)*.

2.  Si prefieres no reinstalar pero quieres asegurarte de que el parche está aplicado en tu carpeta `vendor` local:
    ```bash
    composer run-script post-install-cmd
    ```

3.  Procede con tu proceso habitual de compilación (Phar, concatenación de archivos, etc.). Los archivos resultantes ya contendrán las protecciones necesarias y serán seguros para cualquier entorno de cliente.

## Desarrollo con Docker

La SDK incluye un archivo `docker-compose.yml` para facilitar el desarrollo y las pruebas en un entorno controlado con **PHP 5.6**.

### Instalación de dependencias (Composer)

No necesitas tener PHP o Composer instalado localmente. Puedes usar el contenedor para instalar las dependencias:

```bash
docker compose run --rm quralo-php composer install
```

### Ejecutar ejemplo de uso básico

Para correr el script de ejemplo incluido en la SDK:

```bash
docker compose run --rm quralo-php
```
*(Esto ejecutará `examples/php/basic_usage.php` por defecto)*.

### Otras tareas de desarrollo

- **Abrir una terminal interactiva:**
  ```bash
  docker compose run --rm -it quralo-php bash
  ```
- **Ejecutar tests:**
  ```bash
  docker compose run --rm quralo-php ./vendor/bin/phpunit
  ```

