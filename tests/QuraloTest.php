<?php

namespace Quralo\Tests;

use PHPUnit\Framework\TestCase;
use Quralo\Quralo;

class QuraloTest extends TestCase
{
    /**
     * @var Quralo
     */
    private $quralo;

    protected function setUp()
    {
        $this->quralo = new Quralo();
    }

    public function testQuraloInstantiation()
    {
        $this->assertInstanceOf('Quralo\Quralo', $this->quralo);
    }

    public function testStaticCreateMethod()
    {
        $quralo = Quralo::create();
        $this->assertInstanceOf('Quralo\Quralo', $quralo);
    }

    public function testStructureDataWithCompleteData()
    {
        $organizationId = 'ORG-12345';
        $person = array(
            'lastname' => 'Pérez',
            'firstname' => 'Juan',
            'person_sex' => 'M',
            'date_of_birth' => '1990-05-15',
            'person_id_type' => 'DNI',
            'person_id_number' => '12345678'
        );
        $author = array(
            'person_id_type' => 'DNI',
            'person_id_number' => '87654321'
        );
        $metadata = array('source' => 'registration_form', 'timestamp' => '2024-01-15T10:30:00Z');

        $result = $this->quralo->structureData($organizationId, $person, $author, $metadata);

        $expected = array(
            'type' => 'plain',
            'payload' => array(
                'organization_id' => 'ORG-12345',
                'person' => array(
                    'lastname' => 'Pérez',
                    'firstname' => 'Juan',
                    'person_sex' => 'M',
                    'date_of_birth' => '1990-05-15',
                    'person_id_type' => 'DNI',
                    'person_id_number' => '12345678'
                ),
                'author' => array(
                    'person_id_type' => 'DNI',
                    'person_id_number' => '87654321'
                ),
                'metadata' => array('source' => 'registration_form', 'timestamp' => '2024-01-15T10:30:00Z')
            )
        );

        $this->assertEquals($expected, $result);
    }

    public function testStructureDataWithMissingPersonFields()
    {
        $organizationId = 'ORG-12345';
        $person = array(
            'lastname' => 'García',
            'firstname' => 'María'
            // Missing other fields
        );
        $author = array(
            'person_id_type' => 'DNI',
            'person_id_number' => '87654321'
        );

        $result = $this->quralo->structureData($organizationId, $person, $author);

        $this->assertEquals('García', $result['payload']['person']['lastname']);
        $this->assertEquals('María', $result['payload']['person']['firstname']);
        $this->assertEquals('', $result['payload']['person']['person_sex']);
        $this->assertEquals('', $result['payload']['person']['date_of_birth']);
        $this->assertEquals('', $result['payload']['person']['person_id_type']);
        $this->assertEquals('', $result['payload']['person']['person_id_number']);
        $this->assertNull($result['payload']['metadata']);
    }

    public function testStructureDataWithNullMetadata()
    {
        $organizationId = 'ORG-12345';
        $person = array('lastname' => 'López', 'firstname' => 'Carlos');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '11111111');

        $result = $this->quralo->structureData($organizationId, $person, $author, null);

        $this->assertNull($result['payload']['metadata']);
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

        $qrCode = $this->quralo->generateQrCode($organizationId, $person, $author);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testGenerateQrCodeFromStructuredData()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $structuredData = array(
            'type' => 'plain',
            'payload' => array(
                'organization_id' => 'ORG-TEST',
                'person' => array(
                    'lastname' => 'Fernández',
                    'firstname' => 'Ana',
                    'person_sex' => 'F',
                    'date_of_birth' => '1988-03-10',
                    'person_id_type' => 'DNI',
                    'person_id_number' => '33333333'
                ),
                'author' => array(
                    'person_id_type' => 'DNI',
                    'person_id_number' => '44444444'
                ),
                'metadata' => null
            )
        );

        $qrCode = $this->quralo->generateQrCodeFromStructuredData($structuredData);

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

        $qrCode = $this->quralo->generateQrCode($organizationId, $person, $author, null, $qrOptions);

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

        $qrCode = $this->quralo->generateQrCode($organizationId, $person, $author, null, $qrOptions);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testDataStructure()
    {
        $organizationId = 'ORG-STRUCTURE-TEST';
        $person = array(
            'lastname' => 'Structure',
            'firstname' => 'Test',
            'person_sex' => 'M',
            'date_of_birth' => '1990-01-01',
            'person_id_type' => 'DNI',
            'person_id_number' => '12345678'
        );
        $author = array(
            'person_id_type' => 'DNI',
            'person_id_number' => '87654321'
        );
        $metadata = array('test' => true, 'timestamp' => '2024-01-01T00:00:00Z');

        $structuredData = $this->quralo->structureData($organizationId, $person, $author, $metadata);
        
        // Test structure format
        $this->assertEquals('plain', $structuredData['type']);
        $this->assertArrayHasKey('payload', $structuredData);
        $this->assertArrayHasKey('organization_id', $structuredData['payload']);
        $this->assertArrayHasKey('person', $structuredData['payload']);
        $this->assertArrayHasKey('author', $structuredData['payload']);
        $this->assertArrayHasKey('metadata', $structuredData['payload']);
        
        // Test person structure
        $person = $structuredData['payload']['person'];
        $this->assertArrayHasKey('lastname', $person);
        $this->assertArrayHasKey('firstname', $person);
        $this->assertArrayHasKey('person_sex', $person);
        $this->assertArrayHasKey('date_of_birth', $person);
        $this->assertArrayHasKey('person_id_type', $person);
        $this->assertArrayHasKey('person_id_number', $person);
        
        // Test author structure
        $author = $structuredData['payload']['author'];
        $this->assertArrayHasKey('person_id_type', $author);
        $this->assertArrayHasKey('person_id_number', $author);
    }
}
