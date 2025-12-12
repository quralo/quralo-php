<?php

namespace Quralo;

/**
* Quralo PHP SDK
*
* Librería para integración de sistemas de salud con la plataforma Quralo.
* Permite a hospitales, clínicas y centros médicos generar códigos QR compactos y seguros para flujos de trabajo interoperables.
*
* - Compatible con PHP 5.6+
* - Soporta múltiples dominios: ECL, Webhooks, etc.
*
* Ejemplo de uso:
*
*   use Quralo\Quralo;
*   $ecl = Quralo::ecl();
*   $person = [
*     'lastname' => 'Pérez',
*     'firstname' => 'Ana',
*     'person_sex' => 'female',
*     'date_of_birth' => '1990-01-01',
*     'person_id_type' => 'DNI',
*     'person_id_number' => '12345678',
*     'email' => 'ana@perez.com',
*     'phone_number' => '+34123456789',
*   ];
*   $author = [
*     'person_id_type' => 'DNI',
*     'person_id_number' => '87654321',
*   ];
*   $metadata = [ 'vacuna' => 'COVID-19', 'dosis' => 2 ];
*
*   // Generar payload seguro (string QR)
*   $clientId = 'ORG001';
*   $clientSecret = random_bytes(32); // o base64/hex de 32 bytes
*   $payload = $ecl->encodePayload($clientId, $clientSecret, $person, $author, $metadata);
*
*   // Decodificar y validar el payload seguro
*   $data = $ecl->decodePayload($payload, $clientSecret);
*   // $data contiene el array original con las claves 't', 'p', 'a', 'm'
*
*   // Generar código QR PNG (data URI)
*   $qrImage = $ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, [
*     'ttl_seconds' => 300,
*     'size' => 6,
*     'margin' => 2,
*     'error_correction' => 'M',
*     'include_logo' => true // requiere GD y logo-qr.png
*   ]);
*
* Notas:
* - Las claves pueden ser binarios (32 bytes), hex (64 chars) o base64 (44 chars). La librería las normaliza automáticamente.
* - El método encodePayload retorna el string QR seguro, y decodePayload lo decodifica y valida (incluyendo HMAC y formato).
* - Si el HMAC o el formato no son válidos, decodePayload lanza una excepción.
* - El método generateQrCode retorna la imagen PNG en base64 (data URI).
* - Se puede incluir un logo en el QR con 'include_logo' => true (requiere GD y logo-qr.png).
*/
class Quralo
{
  /**
  * Devuelve una instancia del dominio ECL
  * @return Ecl
  */
  public static function ecl()
  {
    return new Ecl();
  }
  // Aquí se pueden agregar métodos para otros dominios en el futuro, por ejemplo:
  // public static function webhooks() { return new Webhooks(); }
}
