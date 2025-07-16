<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Quralo\Quralo;

/**
 * Example client application demonstrating how a third-party
 * (like a hospital, clinic, or government office) would integrate
 * the Quralo SDK into their existing system for patient/citizen registration
 * 
 * Compatible with PHP 5.6+
 */

class PatientRegistrationSystem
{
    /**
     * @var Quralo
     */
    private $quralo;
    
    /**
     * @var string
     */
    private $organizationId;

    /**
     * @param string $organizationId
     */
    public function __construct($organizationId)
    {
        $this->quralo = Quralo::create();
        $this->organizationId = $organizationId;
    }

    /**
     * Register a patient and generate QR code
     *
     * @param array $patientData
     * @param array $registrarInfo
     * @return array
     * @throws Exception
     */
    public function registerPatient(array $patientData, array $registrarInfo)
    {
        // Map internal patient data to Quralo format
        $person = array(
            'lastname' => $patientData['apellido'],
            'firstname' => $patientData['nombre'],
            'person_sex' => $patientData['sexo'],
            'date_of_birth' => $patientData['fecha_nacimiento'],
            'person_id_type' => $patientData['tipo_documento'],
            'person_id_number' => $patientData['numero_documento']
        );

        $author = array(
            'person_id_type' => $registrarInfo['tipo_documento'],
            'person_id_number' => $registrarInfo['numero_documento']
        );

        $metadata = array(
            'registration_timestamp' => date('Y-m-d\TH:i:s\Z'),
            'department' => isset($patientData['departamento']) ? $patientData['departamento'] : 'General',
            'priority' => isset($patientData['prioridad']) ? $patientData['prioridad'] : 'normal',
            'internal_patient_id' => $patientData['id_interno'],
            'system_version' => '1.0.0'
        );

        // Structure the data using Quralo SDK
        $structuredData = $this->quralo->structureData(
            $this->organizationId,
            $person,
            $author,
            $metadata
        );

        $result = array(
            'structured_data' => $structuredData,
            'success' => true
        );

        // Generate QR code if GD extension is available
        if (extension_loaded('gd')) {
            // Default options include Quralo logo
            $qrOptions = array(
                'size' => 8,
                'margin' => 2,
                'error_correction' => 'M',
                'include_logo' => true
            );
            $qrCode = $this->quralo->generateQrCodeFromStructuredData($structuredData, $qrOptions);
            $result['qr_code'] = $qrCode;
        } else {
            $result['qr_code'] = null;
            $result['qr_error'] = 'GD extension not available';
        }

        return $result;
    }

    /**
     * Register multiple patients in bulk
     *
     * @param array $patientsData
     * @param array $registrarInfo
     * @return array
     */
    public function bulkRegisterPatients(array $patientsData, array $registrarInfo)
    {
        $results = array(
            'total' => count($patientsData),
            'successful' => 0,
            'failed' => 0,
            'registrations' => array()
        );

        foreach ($patientsData as $index => $patientData) {
            try {
                $registration = $this->registerPatient($patientData, $registrarInfo);
                $results['registrations'][] = array(
                    'patient_index' => $index,
                    'status' => 'success',
                    'data' => $registration
                );
                $results['successful']++;
            } catch (Exception $e) {
                $results['registrations'][] = array(
                    'patient_index' => $index,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                );
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Generate a patient card with QR code
     *
     * @param array $patientData
     * @param array $registrarInfo
     * @return string
     * @throws Exception
     */
    public function generatePatientCard(array $patientData, array $registrarInfo)
    {
        $registration = $this->registerPatient($patientData, $registrarInfo);
        
        // In a real system, this would create a complete patient card with the QR code
        $cardData = array(
            'patient_name' => $patientData['nombre'] . ' ' . $patientData['apellido'],
            'organization' => $this->organizationId,
            'qr_code' => $registration['qr_code'],
            'generated_at' => date('Y-m-d H:i:s')
        );

        return json_encode($cardData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Example usage of the client integration
echo "=== Hospital Patient Registration System - Quralo Integration ===\n";
echo "=== Sistema de Registro de Pacientes - Integración con Plataforma Quralo ===\n\n";

try {
    // Initialize the patient registration system for a specific organization
    $hospitalSystem = new PatientRegistrationSystem('HOSP-CENTRAL-BA-001');

    // Registrar information (doctor, nurse, administrative staff)
    $registrarInfo = array(
        'tipo_documento' => 'DNI',
        'numero_documento' => '25789456'
    );

    // Sample patient data from hospital's internal system
    $patientsData = array(
        array(
            'id_interno' => 'PAT-001',
            'apellido' => 'González',
            'nombre' => 'Roberto Carlos',
            'sexo' => 'M',
            'fecha_nacimiento' => '1978-09-23',
            'tipo_documento' => 'DNI',
            'numero_documento' => '28654789',
            'departamento' => 'Cardiología',
            'prioridad' => 'alta'
        ),
        array(
            'id_interno' => 'PAT-002',
            'apellido' => 'Martínez',
            'nombre' => 'Laura Beatriz',
            'sexo' => 'F',
            'fecha_nacimiento' => '1992-12-05',
            'tipo_documento' => 'DNI',
            'numero_documento' => '39876543',
            'departamento' => 'Pediatría',
            'prioridad' => 'normal'
        ),
        array(
            'id_interno' => 'PAT-003',
            'apellido' => 'Silva',
            'nombre' => 'Juan Manuel',
            'sexo' => 'M',
            'fecha_nacimiento' => '1965-04-18',
            'tipo_documento' => 'DNI',
            'numero_documento' => '16234567',
            'departamento' => 'Emergencias',
            'prioridad' => 'urgente'
        )
    );

    echo "1. Registering individual patient...\n";
    $singleRegistration = $hospitalSystem->registerPatient($patientsData[0], $registrarInfo);
    echo "Registration successful:\n";
    echo "- Patient: " . $patientsData[0]['nombre'] . " " . $patientsData[0]['apellido'] . "\n";
    
    if ($singleRegistration['qr_code']) {
        echo "- QR Code length: " . strlen($singleRegistration['qr_code']) . " characters\n";
    } else {
        echo "- QR Code: " . $singleRegistration['qr_error'] . "\n";
    }
    echo "- Structured data type: " . $singleRegistration['structured_data']['type'] . "\n\n";

    echo "2. Bulk patient registration...\n";
    $bulkResults = $hospitalSystem->bulkRegisterPatients($patientsData, $registrarInfo);
    echo "Bulk registration results:\n";
    echo "- Total patients: " . $bulkResults['total'] . "\n";
    echo "- Successful: " . $bulkResults['successful'] . "\n";
    echo "- Failed: " . $bulkResults['failed'] . "\n\n";

    echo "3. Generating patient card...\n";
    $patientCard = $hospitalSystem->generatePatientCard($patientsData[1], $registrarInfo);
    echo "Patient card generated:\n";
    echo $patientCard . "\n\n";

    echo "4. Saving QR codes to files...\n";
    if (extension_loaded('gd')) {
        foreach ($bulkResults['registrations'] as $index => $registration) {
            if ($registration['status'] === 'success' && $registration['data']['qr_code']) {
                $qrCode = $registration['data']['qr_code'];
                $base64Data = str_replace('data:image/png;base64,', '', $qrCode);
                $imageData = base64_decode($base64Data);
                $filename = "patient_qr_" . ($index + 1) . ".png";
                file_put_contents($filename, $imageData);
                echo "- Saved: $filename\n";
            }
        }
    } else {
        echo "- GD extension not available - file save skipped\n";
    }

    echo "\n=== Hospital integration example completed! ===\n";

} catch (Exception $e) {
    echo "Integration Error: " . $e->getMessage() . "\n";
}
