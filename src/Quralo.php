<?php

namespace Quralo;

/**
* Quralo PHP SDK
*
* Librería para integración de sistemas de salud con la plataforma Quralo.
* Permite a hospitales, clínicas y centros médicos generar códigos QR compactos y seguros para flujos de trabajo interoperables.
*
* - Compatible con PHP 5.6+
* - Soporta dos formatos de QR:
*   1. plain: JSON comprimido (gzip+base64), sin firma ni cifrado.
*   2. secure: JSON comprimido, firmado con HMAC-SHA256 y cifrado con AES-256-CBC (requiere claves de 32 bytes).
*
* El string final del QR es siempre <TIPO>:<CONTENIDO>, por ejemplo:
*   qrl:v1:p:<base64...>   (plain)
*   qrl:v1:s:<base64url...> (secure)
*
* Uso típico:
*
*   $quralo = Quralo::create();
*   $person = [
*     'lastname' => 'Pérez',
*     'firstname' => 'Ana',
*     'person_sex' => 'F',
*     'date_of_birth' => '1990-01-01',
*     'person_id_type' => 'DNI',
*     'person_id_number' => '12345678',
*   ];
*   $author = [
*     'person_id_type' => 'DNI',
*     'person_id_number' => '87654321',
*   ];
*   $metadata = [ 'vacuna' => 'COVID-19', 'dosis' => 2 ];
*
*   // QR plano
*   $qr1 = $quralo->generateQrCode('ORG001', $person, $author, $metadata, [ 'format' => 'plain' ]);
*
*   // QR seguro (firmado y cifrado)
*   $signingKey = random_bytes(32); // o base64/hex de 32 bytes
*   $encryptionKey = random_bytes(32);
*   $qr2 = $quralo->generateQrCode('ORG001', $person, $author, $metadata, [
*     'format' => 'secure',
*     'signing_key' => $signingKey,
*     'encryption_key' => $encryptionKey,
*     'ttl_seconds' => 300 // opcional
*   ]);
*
* Notas:
* - Las claves pueden ser binarios, hex (64 chars) o base64 (44 chars). La librería las normaliza.
* - El método generateQrCode retorna la imagen PNG en base64 (data URI).
* - Se puede incluir un logo en el QR con 'include_logo' => true (requiere GD y logo-qr.png).
*/
class Quralo
{
  /**
   * Static factory method
   *
   * @return Quralo
   */
  public static function create()
  {
    return new self();
  }

  public function encodePayload($organizationId, $person, $author, $metadata, $format, $signingKey = null, $encryptionKey = null, $ttlSeconds = 300)
  {
    $compactPayload = array(
      'o' => $organizationId,
      'p' => array(
        'ln' => isset($person['lastname']) ? $person['lastname'] : '',
        'fn' => isset($person['firstname']) ? $person['firstname'] : '',
        'sx' => isset($person['person_sex']) ? $person['person_sex'] : '',
        'db' => isset($person['date_of_birth']) ? $person['date_of_birth'] : '',
        'it' => isset($person['person_id_type']) ? $person['person_id_type'] : '',
        'in' => isset($person['person_id_number']) ? $person['person_id_number'] : '',
      ),
      'a' => array(
        'it' => isset($author['person_id_type']) ? $author['person_id_type'] : '',
        'in' => isset($author['person_id_number']) ? $author['person_id_number'] : '',
      ),
      'm' => $metadata,
      'e' => time() + $ttlSeconds // Expiración
    );

    if ($format === 'plain') {
      $json = json_encode($compactPayload);
      $compressed = function_exists('gzcompress') ? gzcompress($json, 9) : $json;
      return 'qrl:v1:p:' . base64_encode($compressed);
    }

    if ($format === 'secure') {
      // Paso 1: Chequear disponibilidad de claves
      if (empty($signingKey) || empty($encryptionKey)) {
        throw new \Exception("Faltan claves para formato 'secure'");
      }
    
      $signingKey = $this->normalizeKey($signingKey);
      $encryptionKey = $this->normalizeKey($encryptionKey);
      
      // Paso 2: Serializar a JSON
      $json = json_encode($compactPayload);

      // Paso 3: Comprimir
      $compressed = gzcompress($json);

      // Paso 4: Firmar con HMAC-SHA256
      $signature = hash_hmac('sha256', $compressed, $signingKey, true); // binario

      // Paso 5: Concatenar compressed + signature
      $payload = $compressed . $signature;

      // Paso 6: Generar IV de 16 bytes para AES-256-CBC
      $iv = openssl_random_pseudo_bytes(16);

      // Paso 7: Cifrar el payload completo
      $ciphertext = openssl_encrypt($payload, 'AES-256-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
      if ($ciphertext === false) {
        throw new \Exception("Error al cifrar el contenido");
      }

      // Paso 8: Concatenar IV + ciphertext
      $final = $iv . $ciphertext;

      // Paso 9: Codificar para el QR
      return 'qrl:v1:s:' . rtrim(strtr(base64_encode($final), '+/', '-_'), '=');
    }
    
    throw new \Exception("Formato no soportado");
  }
  
  public function generateQrCode($organizationId, $person, $author, $metadata, $options)
  {
    $format = isset($options['format']) ? $options['format'] : 'plain';
    $signingKey = isset($options['signing_key']) ? $options['signing_key'] : null;
    $encryptionKey = isset($options['encryption_key']) ? $options['encryption_key'] : null;
    $ttlSeconds = isset($options['ttl_seconds']) ? $options['ttl_seconds'] : 300;
    $size = isset($options['size']) ? $options['size'] : 6;
    $margin = isset($options['margin']) ? $options['margin'] : 2;
    $errorCorrection = isset($options['error_correction']) ? strtoupper($options['error_correction']) : 'M';
    $includeLogo = isset($options['include_logo']) ? $options['include_logo'] : false;

    $payload = $this->encodePayload($organizationId, $person, $author, $metadata, $format, $signingKey, $encryptionKey, $ttlSeconds);
    echo $payload;
    require_once __DIR__ . '/../vendor/aferrandini/phpqrcode/lib/PHPQRCode.php';
    
    $levels = array(
      'L' => \PHPQRCode\Constants::QR_ECLEVEL_L,
      'M' => \PHPQRCode\Constants::QR_ECLEVEL_M,
      'Q' => \PHPQRCode\Constants::QR_ECLEVEL_Q,
      'H' => \PHPQRCode\Constants::QR_ECLEVEL_H,
    );
    $ecLevel = isset($levels[$errorCorrection]) ? $levels[$errorCorrection] : \PHPQRCode\Constants::QR_ECLEVEL_M;
    
    $tmpFile = tempnam(sys_get_temp_dir(), 'qr_');
    \PHPQRCode\QRcode::png($payload, $tmpFile, $ecLevel, $size, $margin);
    
    if ($includeLogo) {
      $this->addLogoToQrCode($tmpFile);
    }
    
    $imageData = file_get_contents($tmpFile);
    unlink($tmpFile);
    
    return 'data:image/png;base64,' . base64_encode($imageData);
  }

  private function normalizeKey($keyString)
  {
      // Si ya es binario (longitud 32), la retornamos tal cual
      if (strlen($keyString) === 32) {
          return $keyString;
      }
  
      // Si parece hexadecimal (64 chars hex)
      if (ctype_xdigit($keyString) && strlen($keyString) === 64) {
          $bin = hex2bin($keyString);
          if ($bin === false) {
              throw new \Exception("Clave hexadecimal inválida");
          }
          return $bin;
      }
  
      // Intentamos base64_decode
      $decoded = base64_decode($keyString, true);
      if ($decoded !== false && strlen($decoded) === 32) {
          return $decoded;
      }
  
      throw new \Exception("Clave debe ser binaria de 32 bytes, hex de 64 chars o base64 de 44 chars");
  }
  
  private function addLogoToQrCode($qrFile)
  {
    if (!extension_loaded('gd')) {
      return false;
    }
    
    $logoPath = __DIR__ . '/logo-qr.png';
    if (!file_exists($logoPath)) {
      return false;
    }
    
    $qr = imagecreatefrompng($qrFile);
    $logo = imagecreatefrompng($logoPath);
    if (!$qr || !$logo) {
      if ($qr) imagedestroy($qr);
      if ($logo) imagedestroy($logo);
      return false;
    }
    
    $qrW = imagesx($qr);
    $qrH = imagesy($qr);
    $logoW = imagesx($logo);
    $logoH = imagesy($logo);
    
    $logoNewW = $qrW * 0.2;
    $logoNewH = $qrH * 0.2;
    
    $ratio = $logoW / $logoH;
    if ($logoNewW / $logoNewH > $ratio) {
      $logoNewW = $logoNewH * $ratio;
    } else {
      $logoNewH = $logoNewW / $ratio;
    }
    
    $dstX = ($qrW - $logoNewW) / 2;
    $dstY = ($qrH - $logoNewH) / 2;
    
    $resizedLogo = imagecreatetruecolor($logoNewW, $logoNewH);
    imagealphablending($resizedLogo, false);
    imagesavealpha($resizedLogo, true);
    imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $logoNewW, $logoNewH, $logoW, $logoH);
    
    imagecopy($qr, $resizedLogo, $dstX, $dstY, 0, 0, $logoNewW, $logoNewH);
    imagepng($qr, $qrFile);
    
    imagedestroy($qr);
    imagedestroy($logo);
    imagedestroy($resizedLogo);
    
    return true;
  }
}
