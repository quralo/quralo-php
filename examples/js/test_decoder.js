const crypto = require('crypto');
const zlib = require('zlib');

const CLIENT_ID = "c710e909-067a-4b05-8679-5a386cdd5e92";
const CLIENT_SECRET = Buffer.from('6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53', 'hex'); // 32 bytes

// Función base64-url decode
function base64urlDecode(input) {
    input = input.replace(/-/g, '+').replace(/_/g, '/');
    const pad = input.length % 4;
    if (pad) input += '='.repeat(4 - pad);
    return Buffer.from(input, 'base64');
}

/**
 * Decodifica un QR seguro en el formato:
 * QRL|1|ecl|<client_id>|<timestamp>|<data>|<mac>
 * - ecl: identificador del módulo
 * - client_id: identificador del cliente
 * - timestamp: expiración (UNIX)
 * - data: payload comprimido y cifrado (base64url)
 * - mac: HMAC-SHA256 de la cadena anterior (base64url)
 */
function decodeSecureQR(qrString) {
    try {
        const parts = qrString.split('|');
        if (parts.length !== 7 || parts[0] !== 'QRL' || parts[1] !== '1' || parts[2] !== 'ecl') {
            throw new Error('Formato de QR inválido');
        }
        const [ , , module, clientId, timestamp, data_b64url, mac_b64url ] = parts;
        const base = parts.slice(0, 6).join('|'); // QRL|1|ecl|<client_id>|<timestamp>|<data>
        // Verificar MAC
        const expectedMac = crypto.createHmac('sha256', CLIENT_SECRET).update(base).digest();
        const expectedMac_b64url = expectedMac.toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        if (expectedMac_b64url !== mac_b64url) {
            throw new Error('MAC inválido');
        }
        // Decodificar y descifrar
        const bin = base64urlDecode(data_b64url);
        const iv = bin.slice(0, 16);
        const ciphertext = bin.slice(16);
        const decipher = crypto.createDecipheriv('aes-256-cbc', CLIENT_SECRET, iv);
        let compressed = decipher.update(ciphertext);
        compressed = Buffer.concat([compressed, decipher.final()]);
        const jsonBuffer = zlib.unzipSync(compressed);
        const jsonStr = jsonBuffer.toString('utf8');
        const parsed = JSON.parse(jsonStr);
        // Verificar expiración
        const now = Math.floor(Date.now() / 1000);
        if (now > parseInt(timestamp, 10)) {
            throw new Error('El QR ha expirado.');
        }
        return parsed;
    } catch (err) {
        return { error: err.message };
    }
}

// 🧪 Prueba
// Ejemplo: QRL|1|ecl|<client_id>|<timestamp>|<data>|<mac>
const qrString = 'QRL|1|ecl|c710e909-067a-4b05-8679-5a386cdd5e92|1752845098|QPE-TyBFYmhL3xB6rJrY9cX-gotZ9AlnWk1Ey9OAC_iaj_uQDul_jieECkWTis24iSf4TyjqhOZ_TEc9A6b65YkrFipOQNIetyDO5MwzxyBr3ZqfAKXHvWablHPn6sX5ou6rkekvANqJfXRCI5PTZ1Odg9LlY9Cq8M-_g-FBZ6m_-Czgupkkhed61-Cuo7HSfm-UEE51f5Awg1jJmfh1rQ|UJ95ZHQ3OSEdQskS45mHWEni38Tloli8vcbFz5lrfD0';
const resultado = decodeSecureQR(qrString);
console.log('Resultado:', resultado);

