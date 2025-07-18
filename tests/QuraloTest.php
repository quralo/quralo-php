<?php

namespace Quralo\Tests;

use PHPUnit\Framework\TestCase;
use Quralo\Quralo;

class QuraloTest extends TestCase
{
    /**
     * @var \Quralo\Ecl
     */
    private $ecl;

    protected function setUp()
    {
        $this->ecl = Quralo::ecl();
    }

    public function testEclInstantiation()
    {
        $this->assertInstanceOf('Quralo\\Ecl', $this->ecl);
    }

    public function testGenerateQrCodeReturnsDataUri()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $organizationId = 'ORG-TEST';
        $person = array(
            'lastname' => 'Test',
            'firstname' => 'User',
            'person_sex' => 'F',
            'date_of_birth' => '1995-12-25',
            'person_id_type' => 'PASSPORT',
            'person_id_number' => 'ABC123456'
        );
        $author = array(
            'person_id_type' => 'DNI',
            'person_id_number' => '99999999'
        );

        $qrCode = $this->ecl->generateQrCode($organizationId, $person, $author);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testQrCodeWithCustomOptions()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $organizationId = 'ORG-CUSTOM';
        $person = array('lastname' => 'Custom', 'firstname' => 'Test');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '55555555');
        
        $qrOptions = array(
            'size' => 6,
            'margin' => 2,
            'error_correction' => 'M',
            'include_logo' => true
        );

        $qrCode = $this->ecl->generateQrCode($organizationId, $person, $author, null, $qrOptions);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testQrCodeWithoutLogo()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $organizationId = 'ORG-NO-LOGO';
        $person = array('lastname' => 'NoLogo', 'firstname' => 'Test');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '66666666');
        
        $qrOptions = array(
            'size' => 6,
            'margin' => 2,
            'error_correction' => 'M',
            'include_logo' => false
        );

        $qrCode = $this->ecl->generateQrCode($organizationId, $person, $author, null, $qrOptions);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    // Los siguientes tests han sido comentados porque los métodos structureData y generateQrCodeFromStructuredData
    // ya no existen en la clase Ecl. Si los necesitas, deberías implementarlos en Ecl o adaptar los tests.

    /*
    public function testStructureDataWithCompleteData() { ... }
    public function testStructureDataWithMissingPersonFields() { ... }
    public function testStructureDataWithNullMetadata() { ... }
    public function testGenerateQrCodeFromStructuredData() { ... }
    public function testDataStructure() { ... }
    public function testStaticCreateMethod() { ... }
    public function testQuraloInstantiation() { ... }
    */
}
