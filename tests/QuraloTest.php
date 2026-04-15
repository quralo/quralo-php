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
        $this->assertInstanceOf('Quralo\Ecl', $this->ecl);
    }

    public function testGenerateQrCodeReturnsDataUri()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $clientId = 'ORG-TEST';
        $clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53';
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
        $metadata = null;
        $options = array();

        $qrCode = $this->ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, $options);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testQrCodeWithCustomOptions()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $clientId = 'ORG-CUSTOM';
        $clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53';
        $person = array('lastname' => 'Custom', 'firstname' => 'Test');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '55555555');
        $metadata = null;
        $qrOptions = array(
            'size' => 6,
            'margin' => 2,
            'error_correction' => 'M',
            'include_logo' => true
        );

        $qrCode = $this->ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, $qrOptions);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testQrCodeWithoutLogo()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $clientId = 'ORG-NO-LOGO';
        $clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53';
        $person = array('lastname' => 'NoLogo', 'firstname' => 'Test');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '66666666');
        $metadata = null;
        $qrOptions = array(
            'size' => 6,
            'margin' => 2,
            'error_correction' => 'M',
            'include_logo' => false
        );

        $qrCode = $this->ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, $qrOptions);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function testSecureQrCode()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }
        $clientId = 'ORG-SECURE';
        $clientSecret = '6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53';
        $person = array('lastname' => 'Secure', 'firstname' => 'Test');
        $author = array('person_id_type' => 'DNI', 'person_id_number' => '77777777');
        $metadata = array('test' => 'meta');
        $qrOptions = array(
            'format' => 'secure',
            'ttl_seconds' => 600,
            'include_logo' => false
        );
        $qrCode = $this->ecl->generateQrCode($clientId, $clientSecret, $person, $author, $metadata, $qrOptions);
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }
}
