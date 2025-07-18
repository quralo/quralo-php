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
*   $qr1 = $ecl->generateQrCode('ORG001', $person, $author, $metadata, [ 'format' => 'plain' ]);
*
*   // QR seguro (firmado y cifrado)
*   $signingKey = random_bytes(32); // o base64/hex de 32 bytes
*   $encryptionKey = random_bytes(32);
*   $qr2 = $ecl->generateQrCode('ORG001', $person, $author, $metadata, [
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
