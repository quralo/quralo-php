<?php

namespace Quralo;

/**
 * Quralo PHP SDK
 * 
 * Healthcare system integration tools for seamless interoperability with Quralo platform.
 * Enables hospitals, clinics, and medical facilities to integrate patient data 
 * and generate secure QR codes for efficient healthcare workflows.
 * Compatible with PHP 5.6+
 */
class Quralo
{
  /**
   * Structure patient/person data for Quralo platform integration
   *
   * Transforms healthcare system data into Quralo-compatible format for
   * seamless interoperability between hospital systems and Quralo platform.
   *
   * @param string $organizationId Healthcare organization identifier
   * @param array $person Patient/person data (lastname, firstname, person_sex, date_of_birth, person_id_type, person_id_number)
   * @param array $author Medical staff/registrant data (person_id_type, person_id_number)
   * @param mixed $metadata Optional healthcare-specific metadata (department, priority, timestamps, etc.)
   * @return array Quralo-compatible structured data
   */
  public function structureData($organizationId, array $person, array $author, $metadata = null)
  {
    return array(
      'type' => 'plain',
      'payload' => array(
        'organization_id' => $organizationId,
        'person' => array(
          'lastname' => isset($person['lastname']) ? $person['lastname'] : '',
          'firstname' => isset($person['firstname']) ? $person['firstname'] : '',
          'person_sex' => isset($person['person_sex']) ? $person['person_sex'] : '',
          'date_of_birth' => isset($person['date_of_birth']) ? $person['date_of_birth'] : '',
          'person_id_type' => isset($person['person_id_type']) ? $person['person_id_type'] : '',
          'person_id_number' => isset($person['person_id_number']) ? $person['person_id_number'] : '',
        ),
        'author' => array(
          'person_id_type' => isset($author['person_id_type']) ? $author['person_id_type'] : '',
          'person_id_number' => isset($author['person_id_number']) ? $author['person_id_number'] : '',
        ),
        'metadata' => $metadata,
      )
    );
  }

  /**
   * Generate QR code for healthcare system integration
   *
   * Creates secure QR codes containing patient data for use in healthcare workflows.
   * Automatically compresses data and includes Quralo branding for platform recognition.
   *
   * @param string $organizationId Healthcare organization identifier
   * @param array $person Patient/person data
   * @param array $author Medical staff/registrant data
   * @param mixed $metadata Optional healthcare-specific metadata
   * @param array $qrOptions QR code generation options (size, margin, error_correction, include_logo)
   * @return string Base64 encoded QR code data URI ready for display or printing
   */
  public function generateQrCode($organizationId, array $person, array $author, $metadata = null, array $qrOptions = array())
  {
    $data = $this->structureData($organizationId, $person, $author, $metadata);
    return $this->generateQrCodeFromStructuredData($data, $qrOptions);
  }

  /**
   * Generate QR code from pre-structured healthcare data
   *
   * Creates QR codes from data already formatted for Quralo platform.
   * Useful for batch processing or when data structure is prepared separately.
   *
   * @param array $structuredData Quralo-compatible structured healthcare data
   * @param array $qrOptions QR code generation options (size, margin, error_correction, include_logo)
   * @return string Base64 encoded QR code data URI ready for healthcare workflows
   */
  public function generateQrCodeFromStructuredData(array $structuredData, array $qrOptions = array())
  {
    // Compress the data to reduce QR code complexity
    $compressedData = $this->compressQrData($structuredData);
    
    // QR code options with defaults
    $size = isset($qrOptions['size']) ? $qrOptions['size'] : 6;
    $margin = isset($qrOptions['margin']) ? $qrOptions['margin'] : 2;
    $errorCorrection = isset($qrOptions['error_correction']) ? $qrOptions['error_correction'] : 'M';
    $includeLogo = isset($qrOptions['include_logo']) ? $qrOptions['include_logo'] : true;
    
    // Create temporary file for QR code
    $tempFile = tempnam(sys_get_temp_dir(), 'qrcode');
    
    // Include the QR code library
    require_once dirname(__FILE__) . '/../vendor/aferrandini/phpqrcode/lib/PHPQRCode.php';
    
    // Convert error correction level to library format
    $ecLevel = 'L'; // Default
    switch (strtoupper($errorCorrection)) {
      case 'L': $ecLevel = \PHPQRCode\Constants::QR_ECLEVEL_L; break;
      case 'M': $ecLevel = \PHPQRCode\Constants::QR_ECLEVEL_M; break;
      case 'Q': $ecLevel = \PHPQRCode\Constants::QR_ECLEVEL_Q; break;
      case 'H': $ecLevel = \PHPQRCode\Constants::QR_ECLEVEL_H; break;
    }
    
    // Generate QR code
    \PHPQRCode\QRcode::png($compressedData, $tempFile, $ecLevel, $size, $margin);
    
    // Add logo to QR code if requested and logo exists
    if ($includeLogo) {
      $this->addLogoToQrCode($tempFile);
    }
    
    // Read the generated image
    $imageData = file_get_contents($tempFile);
    
    // Clean up temporary file
    unlink($tempFile);
    
    // Return as data URI
    return 'data:image/png;base64,' . base64_encode($imageData);
  }

  /**
   * Add Quralo logo to the center of the QR code
   *
   * @param string $qrCodeFile Path to the QR code image file
   * @return bool Success status
   */
  private function addLogoToQrCode($qrCodeFile)
  {
    if (!extension_loaded('gd')) {
      return false;
    }

    $logoPath = dirname(__FILE__) . '/logo-qr.png';
    
    // Check if logo file exists
    if (!file_exists($logoPath)) {
      return false;
    }

    // Load QR code image
    $qrImage = imagecreatefrompng($qrCodeFile);
    if (!$qrImage) {
      return false;
    }

    // Load logo image
    $logoImage = imagecreatefrompng($logoPath);
    if (!$logoImage) {
      imagedestroy($qrImage);
      return false;
    }

    // Get dimensions
    $qrWidth = imagesx($qrImage);
    $qrHeight = imagesy($qrImage);
    $logoWidth = imagesx($logoImage);
    $logoHeight = imagesy($logoImage);

    // Calculate logo size (should be about 20% of QR code size)
    $logoNewWidth = $qrWidth * 0.3;
    $logoNewHeight = $qrHeight * 0.3;

    // Maintain aspect ratio
    $logoRatio = $logoWidth / $logoHeight;
    if ($logoNewWidth / $logoNewHeight > $logoRatio) {
      $logoNewWidth = $logoNewHeight * $logoRatio;
    } else {
      $logoNewHeight = $logoNewWidth / $logoRatio;
    }

    // Create resized logo
    $logoResized = imagecreatetruecolor($logoNewWidth, $logoNewHeight);
    
    // Preserve transparency
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);
    $transparent = imagecolorallocatealpha($logoResized, 255, 255, 255, 0);
    imagefill($logoResized, 0, 0, $transparent);
    imagealphablending($logoResized, true);

    // Resize logo
    imagecopyresampled(
      $logoResized, $logoImage,
      0, 0, 0, 0,
      $logoNewWidth, $logoNewHeight,
      $logoWidth, $logoHeight
    );

    // Calculate position (center)
    $logoX = ($qrWidth - $logoNewWidth) / 2;
    $logoY = ($qrHeight - $logoNewHeight) / 2;

    // Create a smaller white background circle only for the logo content (not the entire PNG)
    // Estimate the actual logo circle size (assuming logo is roughly 80% of the PNG size)
    $logoCircleSize = min($logoNewWidth, $logoNewHeight) * 0.8;
    $white = imagecolorallocate($qrImage, 255, 255, 255);
    imagefilledellipse($qrImage, $qrWidth/2, $qrHeight/2, $logoCircleSize, $logoCircleSize, $white);

    // Enable alpha blending for the destination image
    imagealphablending($qrImage, true);
    
    // Copy logo onto QR code preserving transparency
    imagecopy($qrImage, $logoResized, $logoX, $logoY, 0, 0, $logoNewWidth, $logoNewHeight);

    // Save the modified QR code
    $result = imagepng($qrImage, $qrCodeFile);

    // Clean up memory
    imagedestroy($qrImage);
    imagedestroy($logoImage);
    imagedestroy($logoResized);

    return $result;
  }

  /**
   * Compress QR data to reduce QR code complexity
   *
   * @param array $structuredData The structured data to compress
   * @return string Compressed and encoded data
   */
  private function compressQrData(array $structuredData)
  {
    // Create a more compact representation with shorter field names
    $compactData = array(
      't' => $structuredData['type'],
      'p' => array(
        'o' => $structuredData['payload']['organization_id'],
        'p' => array(
          'ln' => $structuredData['payload']['person']['lastname'],
          'fn' => $structuredData['payload']['person']['firstname'],
          'sx' => $structuredData['payload']['person']['person_sex'],
          'db' => $structuredData['payload']['person']['date_of_birth'],
          'it' => $structuredData['payload']['person']['person_id_type'],
          'in' => $structuredData['payload']['person']['person_id_number']
        ),
        'a' => array(
          'it' => $structuredData['payload']['author']['person_id_type'],
          'in' => $structuredData['payload']['author']['person_id_number']
        ),
        'm' => $structuredData['payload']['metadata']
      )
    );

    // Convert to JSON without extra spaces
    $jsonData = json_encode($compactData, JSON_UNESCAPED_UNICODE);

    // Compress using gzip if available
    if (function_exists('gzcompress')) {
      $compressed = gzcompress($jsonData, 9); // Maximum compression
      if ($compressed !== false) {
        // Add prefix to identify compressed data and encode in base64
        return 'QZ1:' . base64_encode($compressed);
      }
    }

    // Fallback: just use compact JSON if compression not available
    return 'QJ1:' . $jsonData;
  }

  /**
   * Get the compressed data that would be stored in the QR code (for testing/debugging)
   *
   * @param array $structuredData The structured data
   * @return string The compressed data string
   */
  private function getCompressedData(array $structuredData)
  {
    return $this->compressQrData($structuredData);
  }

  /**
   * Decompress QR data (for verification or processing)
   *
   * @param string $compressedData The compressed data from QR code
   * @return array|false The original structured data or false on error
   */
  private function decompressQrData($compressedData)
  {
    // Check if data is compressed
    if (strpos($compressedData, 'QZ1:') === 0) {
      // Compressed data
      $encodedData = substr($compressedData, 4);
      $decodedData = base64_decode($encodedData);
      
      if ($decodedData === false) {
        return false;
      }

      if (function_exists('gzuncompress')) {
        $jsonData = gzuncompress($decodedData);
        if ($jsonData === false) {
          return false;
        }
      } else {
        return false; // Can't decompress without gzip
      }
    } elseif (strpos($compressedData, 'QJ1:') === 0) {
      // Compact JSON data
      $jsonData = substr($compressedData, 4);
    } else {
      // Try to parse as regular JSON (backward compatibility)
      $jsonData = $compressedData;
    }

    $compactData = json_decode($jsonData, true);
    if ($compactData === null) {
      return false;
    }

    // Convert back to full format
    if (isset($compactData['t']) && isset($compactData['p'])) {
      // New compact format
      return array(
        'type' => $compactData['t'],
        'payload' => array(
          'organization_id' => $compactData['p']['o'],
          'person' => array(
            'lastname' => $compactData['p']['p']['ln'],
            'firstname' => $compactData['p']['p']['fn'],
            'person_sex' => $compactData['p']['p']['sx'],
            'date_of_birth' => $compactData['p']['p']['db'],
            'person_id_type' => $compactData['p']['p']['it'],
            'person_id_number' => $compactData['p']['p']['in']
          ),
          'author' => array(
            'person_id_type' => $compactData['p']['a']['it'],
            'person_id_number' => $compactData['p']['a']['in']
          ),
          'metadata' => $compactData['p']['m']
        )
      );
    } else {
      // Assume it's already in full format (backward compatibility)
      return $compactData;
    }
  }

  /**
   * Static factory method
   *
   * @return Quralo
   */
  public static function create()
  {
    return new self();
  }
}
