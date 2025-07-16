# Quralo PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%3E%3D5.6-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Herramientas de integración para sistemas hospitalarios y la plataforma Quralo**

El SDK oficial de PHP para integrar sistemas de salud, hospitales, clínicas y centros médicos con la plataforma Quralo. Facilita la interoperabilidad y el intercambio seguro de datos de pacientes a través de códigos QR optimizados.

## Instalación

Instala la librería usando Composer:

```bash
composer require quralo/quralo-php
```

## Requisitos

- PHP 5.6 o superior
- Extensión GD (para generar códigos QR)
- Extensión JSON

## Integración Rápida

```php
<?php

require_once 'vendor/autoload.php';

use Quralo\Quralo;

// Inicializar SDK para integración hospitalaria
$quralo = Quralo::create();

// Datos del paciente desde su sistema hospitalario
$organizationId = 'HOSP-CENTRAL-001';
$patient = [
    'lastname' => 'García',
    'firstname' => 'María Elena',
    'person_sex' => 'F',
    'date_of_birth' => '1985-07-12',
    'person_id_type' => 'DNI',
    'person_id_number' => '35478961'
];

// Datos del personal médico que registra
$medicalStaff = [
    'person_id_type' => 'DNI',
    'person_id_number' => '20123456'
];

// Metadatos específicos del sistema hospitalario
$clinicalMetadata = [
    'department' => 'Emergencias',
    'priority' => 'alta',
    'registration_timestamp' => date('Y-m-d\TH:i:s\Z'),
    'internal_patient_id' => 'PAT-2024-001'
];

// Estructurar datos para interoperabilidad con Quralo
$structuredData = $quralo->structureData($organizationId, $patient, $medicalStaff, $clinicalMetadata);

// Generar código QR para workflows hospitalarios
$qrCode = $quralo->generateQrCode($organizationId, $patient, $medicalStaff, $clinicalMetadata);

// El QR está listo para imprimir en pulseras, historias clínicas, etc.
echo '<img src="' . $qrCode . '" alt="Código QR Paciente">';
```

## Funcionalidades de Integración

### Estructuración de Datos Hospitalarios

El SDK convierte datos de pacientes de cualquier sistema hospitalario al formato estándar de Quralo:

```php
$structuredData = $quralo->structureData(
    'HOSPITAL-ID',           // ID de su organización en Quralo
    $patientData,           // Datos del paciente
    $staffData,             // Datos del personal médico
    $clinicalMetadata       // Metadatos clínicos específicos
);
```

**Resultado compatible con Quralo:**
```json
{
    "type": "plain",
    "payload": {
        "organization_id": "HOSPITAL-ID",
        "person": {
            "lastname": "Apellido",
            "firstname": "Nombre", 
            "person_sex": "M/F",
            "date_of_birth": "YYYY-MM-DD",
            "person_id_type": "DNI/PASSPORT/etc",
            "person_id_number": "Número"
        },
        "author": {
            "person_id_type": "DNI/PASSPORT/etc",
            "person_id_number": "Número"
        },
        "metadata": {
            "department": "Servicio médico",
            "priority": "normal/alta/urgente",
            "internal_patient_id": "ID interno del hospital"
        }
    }
}
```

### Generación de Códigos QR Médicos

Códigos QR optimizados para entornos hospitalarios:

```php
// Generar QR desde datos del sistema hospitalario
$qrCode = $quralo->generateQrCode($organizationId, $patient, $staff, $metadata);

// Generar QR desde datos ya estructurados (para procesamiento en lotes)
$qrCode = $quralo->generateQrCodeFromStructuredData($structuredData);
```

### Opciones de Configuración para Hospitales

```php
$hospitalQrOptions = [
    'size' => 8,                    // Tamaño apropiado para pulseras/etiquetas
    'margin' => 2,                  // Margen para impresión
    'error_correction' => 'M',      // Corrección de errores para entornos clínicos
    'include_logo' => true          // Logo Quralo para identificación de plataforma
];

$qrCode = $quralo->generateQrCode($organizationId, $patient, $staff, $metadata, $hospitalQrOptions);
```

### Logo Institucional en Códigos QR

Los códigos QR incluyen automáticamente el logo de Quralo para:

- **Identificación de plataforma**: Reconocimiento inmediato del sistema Quralo
- **Confianza del paciente**: Logo visible aumenta confianza
- **Interoperabilidad**: Facilita integración entre diferentes centros médicos
- **Control de calidad**: Fondo blanco garantiza legibilidad en cualquier superficie

Configuración del logo:
- **Con logo**: `'include_logo' => true` (predeterminado para hospitales)
- **Sin logo**: `'include_logo' => false` (para casos especiales)

### Optimización para Sistemas Hospitalarios

**Compresión Automática de Datos Clínicos:**
- **Campos compactos**: Reduce espacio sin perder información
- **Compresión GZIP**: Hasta 70% de reducción en tamaño
- **QR codes simples**: Más fáciles de escanear en entornos clínicos
- **Compatibilidad**: Funciona con cualquier lector QR estándar

## Casos de Uso en Salud

### 🏥 Hospitales
- **Identificación de pacientes**: Pulseras con QR para acceso rápido a datos
- **Historias clínicas**: QR en documentos para trazabilidad
- **Emergencias**: Acceso inmediato a información crítica del paciente
- **Traslados**: Transferencia segura de datos entre servicios

### 🩺 Clínicas
- **Registro de consultas**: QR para seguimiento de citas y tratamientos
- **Recetas médicas**: Códigos QR para verificación y dispensación
- **Resultados de laboratorio**: Acceso seguro a estudios e informes

### 🚑 Servicios de Emergencia
- **Identificación rápida**: Escaneado inmediato en situaciones críticas
- **Datos médicos esenciales**: Alergias, medicamentos, condiciones previas
- **Contactos de emergencia**: Información familiar y médica relevante

### 🏢 Organizaciones de Salud
- **Interoperabilidad**: Intercambio de datos entre diferentes sistemas
- **Auditoría**: Trazabilidad completa de accesos y modificaciones
- **Integración**: Conexión con sistemas HIS/EMR existentes

## Campos de Datos Médicos

### Datos del Paciente (`$patient`)
- `lastname` (string): Apellido del paciente
- `firstname` (string): Nombre del paciente  
- `person_sex` (string): Sexo ('M' o 'F')
- `date_of_birth` (string): Fecha de nacimiento (YYYY-MM-DD)
- `person_id_type` (string): Tipo de documento (DNI, PASSPORT, etc.)
- `person_id_number` (string): Número de documento

### Datos del Personal Médico (`$staff`)
- `person_id_type` (string): Tipo de documento del personal
- `person_id_number` (string): Número de documento del personal

### Metadatos Clínicos (`$metadata`)
Campo flexible para información específica del hospital:
```php
$clinicalMetadata = [
    'department' => 'Cardiología',
    'priority' => 'alta',
    'internal_patient_id' => 'PAT-2024-001',
    'admission_date' => '2024-01-15',
    'attending_physician' => 'Dr. González',
    'room_number' => '301A',
    'insurance_info' => 'OSDE Plan 450'
];
```

## Manejo de Códigos QR en Entornos Clínicos

### Impresión y Visualización
```php
$qrCode = $quralo->generateQrCode($organizationId, $patient, $staff, $metadata);

// Para pulseras de pacientes
$base64Data = str_replace('data:image/png;base64,', '', $qrCode);
$imageData = base64_decode($base64Data);
file_put_contents('patient_wristband_qr.png', $imageData);

// Para visualización en pantallas
echo '<img src="' . $qrCode . '" alt="QR Paciente" class="patient-qr">';

// Para historias clínicas digitales
$qrHtml = '<div class="qr-container">
    <img src="' . $qrCode . '" alt="QR ID: ' . $patient['person_id_number'] . '">
    <p>Paciente: ' . $patient['firstname'] . ' ' . $patient['lastname'] . '</p>
</div>';
```

### Integración con Sistemas Hospitalarios

```php
class HospitalIntegration 
{
    private $quralo;
    private $hospitalId;
    
    public function __construct($hospitalId) 
    {
        $this->quralo = Quralo::create();
        $this->hospitalId = $hospitalId;
    }
    
    public function generatePatientQR($patientRecord, $staffId) 
    {
        // Mapear datos del sistema hospitalario a formato Quralo
        $patient = [
            'lastname' => $patientRecord['apellido'],
            'firstname' => $patientRecord['nombre'],
            'person_sex' => $patientRecord['sexo'],
            'date_of_birth' => $patientRecord['fecha_nacimiento'],
            'person_id_type' => $patientRecord['tipo_doc'],
            'person_id_number' => $patientRecord['numero_doc']
        ];
        
        $staff = [
            'person_id_type' => 'DNI',
            'person_id_number' => $staffId
        ];
        
        $metadata = [
            'department' => $patientRecord['servicio'],
            'internal_id' => $patientRecord['historia_clinica'],
            'generated_by' => 'HIS-INTEGRATION-V1.0'
        ];
        
        return $this->quralo->generateQrCode(
            $this->hospitalId, 
            $patient, 
            $staff, 
            $metadata
        );
    }
}
```

## Ejemplos Prácticos

Consulta el directorio `examples/` para implementaciones completas:

- [`basic_usage.php`](examples/basic_usage.php) - Integración básica hospitalaria
- [`client_integration.php`](examples/client_integration.php) - Sistema hospitalario completo con procesamiento en lotes

## Desarrollo con Docker

Puedes utilizar el archivo `docker-compose.yml` incluido en el repositorio para levantar un entorno de desarrollo rápido y reproducible, sin necesidad de instalar PHP ni extensiones en tu máquina local.

### Ejecutar comandos en el contenedor

Para ejecutar cualquier comando dentro del entorno PHP del contenedor (por ejemplo, correr tests, ejemplos o Composer), utiliza:

```bash
docker compose run --rm quralo-php <comando>
```

Por ejemplo, para ejecutar los tests:

```bash
docker compose run --rm quralo-php composer test
```

Para probar un ejemplo:

```bash
docker compose run --rm quralo-php php examples/basic_usage.php
```

Esto ejecutará el comando en un contenedor efímero, que se elimina automáticamente al finalizar.

### Levantar un entorno interactivo (opcional)

Si necesitas una terminal interactiva dentro del contenedor:

```bash
docker compose run --rm -it quralo-php bash
```

### Detener y limpiar recursos

No es necesario detener manualmente los contenedores, ya que cada comando usa `--rm` y elimina el contenedor al finalizar. Si llegas a levantar servicios en modo background, puedes limpiar todo con:

```bash
docker compose down
```

## Desarrollo y Testing

### Ejecutar Tests
```bash
composer test
```

### Verificar Compatibilidad
La librería es compatible con:
- **PHP**: 5.6+ hasta las versiones más recientes
- **Sistemas hospitalarios**: HIS, EMR, PACS
- **Lectores QR**: Cualquier escáner QR estándar
- **Plataformas**: Web, móvil, sistemas embebidos

## Soporte y Documentación

### Para Desarrolladores Hospitalarios
- **API Documentation**: [https://docs.quralo.com/php-sdk](https://docs.quralo.com/php-sdk)
- **Guías de Integración**: [https://docs.quralo.com/integration](https://docs.quralo.com/integration)
- **Casos de Uso Médicos**: [https://docs.quralo.com/healthcare](https://docs.quralo.com/healthcare)

### Soporte Técnico
- **Issues Técnicos**: [GitHub Issues](https://github.com/quralo/quralo-php/issues)
- **Soporte de Integración**: integration@quralo.com
- **Soporte Comercial**: sales@quralo.com

## Contribuir

Las contribuciones de la comunidad médica y de desarrolladores de sistemas hospitalarios son bienvenidas:

1. Fork del repositorio
2. Crea una rama para tu feature (`git checkout -b feature/hospital-integration`)
3. Commit tus cambios (`git commit -m 'Add hospital workflow support'`)
4. Push a la rama (`git push origin feature/hospital-integration`)
5. Abre un Pull Request

## Seguridad y Cumplimiento

- **Datos Sensibles**: El SDK maneja datos de pacientes siguiendo mejores prácticas
- **No Logging**: No registra información de pacientes en logs
- **Encriptación**: Datos comprimidos para minimizar exposición
- **Cumplimiento**: Diseñado para facilitar cumplimiento con regulaciones de salud

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - consulta el archivo [LICENSE](LICENSE) para más detalles.

<!-- ## Roadmap

### Próximas Funcionalidades
- **Validación FHIR**: Soporte para estándares HL7 FHIR
- **Encriptación avanzada**: Cifrado de extremo a extremo
- **APIs de sincronización**: Sincronización bidireccional con Quralo
- **Webhooks**: Notificaciones en tiempo real
- **Soporte DICOM**: Integración con imágenes médicas

## Changelog

### v1.0.0 - Integración Hospitalaria Inicial
- **Integración con sistemas hospitalarios**: HIS, EMR, PACS
- **Compatible con PHP 5.6+**: Máxima compatibilidad con sistemas legacy
- **Estructuración de datos médicos**: Formato estándar Quralo
- **Generación de QR para hospitales**: Optimizado para entornos clínicos
- **Logo integrado**: Branding Quralo para reconocimiento
- **Compresión automática**: Hasta 70% reducción en tamaño de datos
- **Detección de extensiones**: Adaptación automática a entorno de servidor
- **API simplificada**: Métodos esenciales para desarrolladores hospitalarios
- **Metadatos clínicos**: Soporte para información específica de salud
- **Optimización de transparencias**: Logo profesional en códigos QR -->
