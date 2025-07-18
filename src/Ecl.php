<?php

namespace Quralo;

class Ecl
{
  /**
   * Genera el payload QR en formato compacto:
   * QRL|v=1|<client_id>|<timestamp>|<data>|<mac>
   * - client_id: identificador del cliente (visible)
   * - clientSecret: clave secreta para firmar/cifrar (opcional en 'plain')
   * - ...otros parámetros
   */
  public function encodePayload($clientId, $clientSecret = null, $person, $author, $metadata, $format, $ttlSeconds = 300)
  {
    $timestamp = time() + $ttlSeconds;
    $compactPayload = array(
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
      'm' => $metadata
    );
    
    if ($format === 'plain') {
      $json = json_encode($compactPayload);
      $compressed = function_exists('gzcompress') ? gzcompress($json, 9) : $json;
      $data_b64url = rtrim(strtr(base64_encode($compressed), '+/', '-_'), '=');
      // QRL|v=1|<client_id>|<timestamp>|<data>
      return 'QRL|v=1|' . $clientId . '|' . $timestamp . '|' . $data_b64url;
    }
    
    if ($format === 'secure') {
      if (empty($clientSecret)) {
        throw new \Exception("Falta clientSecret para formato 'secure'");
      }
      $clientSecret = $this->normalizeKey($clientSecret);
      $json = json_encode($compactPayload);
      $compressed = gzcompress($json);
      $iv = openssl_random_pseudo_bytes(16);
      $ciphertext = openssl_encrypt($compressed, 'AES-256-CBC', $clientSecret, OPENSSL_RAW_DATA, $iv);
      if ($ciphertext === false) {
        throw new \Exception("Error al cifrar el contenido");
      }
      $data = $iv . $ciphertext;
      $data_b64url = rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
      // Construir string base para MAC (sin nombres de campos)
      $base = 'QRL|v=1|' . $clientId . '|' . $timestamp . '|' . $data_b64url;
      $mac = hash_hmac('sha256', $base, $clientSecret, true);
      $mac_b64url = rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
      // QRL|v=1|<client_id>|<timestamp>|<data>|<mac>
      return $base . '|' . $mac_b64url;
    }
    throw new \Exception("Formato no soportado");
  }
  
  /**
   * Genera un código QR PNG (data URI) con el payload generado.
   * El formato del payload es: QRL|v=1|<client_id>|<timestamp>|<data>|<mac>
   */
  public function generateQrCode($clientId, $clientSecret = null, $person, $author, $metadata, $options)
  {
    $format = isset($options['format']) ? $options['format'] : 'plain';
    $ttlSeconds = isset($options['ttl_seconds']) ? $options['ttl_seconds'] : 300;
    $size = isset($options['size']) ? $options['size'] : 6;
    $margin = isset($options['margin']) ? $options['margin'] : 2;
    $errorCorrection = isset($options['error_correction']) ? strtoupper($options['error_correction']) : 'M';
    $includeLogo = isset($options['include_logo']) ? $options['include_logo'] : false;
    
    $payload = $this->encodePayload($clientId, $clientSecret, $person, $author, $metadata, $format, $ttlSeconds);
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
  
  /**
   * Normaliza la clave secreta: acepta binario, hex (64 chars) o base64 (44 chars).
   */
  private function normalizeKey($keyString)
  {
    if (strlen($keyString) === 32) {
      return $keyString;
    }
    if (ctype_xdigit($keyString) && strlen($keyString) === 64) {
      $bin = hex2bin($keyString);
      if ($bin === false) {
        throw new \Exception("Clave hexadecimal inválida");
      }
      return $bin;
    }
    $decoded = base64_decode($keyString, true);
    if ($decoded !== false && strlen($decoded) === 32) {
      return $decoded;
    }
    throw new \Exception("Clave debe ser binaria de 32 bytes, hex de 64 chars o base64 de 44 chars");
  }
  
  /**
   * Inserta el logo en el QR si está disponible.
   */
  private function addLogoToQrCode($qrFile)
  {
    if (!extension_loaded('gd')) {
      return false;
    }
    $logoPath = __DIR__ . '/assets/logo-qr.png';
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