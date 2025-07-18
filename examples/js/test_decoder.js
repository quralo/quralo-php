const crypto = require('crypto');
const zlib = require('zlib');

// Claves iguales a las usadas en PHP
const ENCRYPTION_KEY = Buffer.from('6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53', 'hex'); // 32 bytes
const SIGNING_KEY = Buffer.from('f0dce5b6a0bd24ff07aaec8835cfee7855bc6ccb6ffa9da5ee57ec7ea49b3c25', 'hex'); // debe coincidir con la de PHP

// Función base64-url decode
function base64urlDecode(input) {
    input = input.replace(/-/g, '+').replace(/_/g, '/');
    const pad = input.length % 4;
    if (pad) input += '='.repeat(4 - pad);
    return Buffer.from(input, 'base64');
}

// Descifrado y validación
function decryptQRToken(qrToken) {
    try {
        const buffer = base64urlDecode(qrToken);

        // Extraer IV (primeros 16 bytes)
        const iv = buffer.slice(0, 16);
        const ciphertext = buffer.slice(16);

        // Descifrar con AES-256-CBC
        const decipher = crypto.createDecipheriv('aes-256-cbc', ENCRYPTION_KEY, iv);
        let decrypted = decipher.update(ciphertext);
        decrypted = Buffer.concat([decrypted, decipher.final()]);

        // Separar firma (últimos 32 bytes) y datos comprimidos
        const signature = decrypted.slice(-32); // SHA-256 = 32 bytes
        const compressedData = decrypted.slice(0, -32);
        console.log(compressedData)

        // Verificar HMAC
        const expectedSig = crypto.createHmac('sha256', SIGNING_KEY).update(compressedData).digest();
        if (!crypto.timingSafeEqual(signature, expectedSig)) {
            throw new Error('Firma HMAC inválida.');
        }

        // Descomprimir
        const jsonBuffer = zlib.unzipSync(compressedData);
        const jsonStr = jsonBuffer.toString('utf8');

        // Parsear JSON
        const parsed = JSON.parse(jsonStr);

        // Verificar expiración
        const now = Math.floor(Date.now() / 1000);
        if (parsed.e && now > parsed.e) {
            throw new Error('El QR ha expirado.');
        }
	return parsed;
    } catch (err) {
        return { error: err.message };
    }
}

// 🧪 Prueba

const qrToken = 'R5A3B0YFbuoGoCfnp6-CQ7cXKx7sUFDawD__PsPGgVNScjIcwbSJVo3qhDZyHpktp7hcfftonKEzZ7ITETaqiXj8F2bTmPJPRwhZp-WHL71EfPPtVzJvrRjNcxoM4i-FI0gR8BwPpLCi10IQfH0DPT_Ci9Qpn0TONp7JUQ5HulQJEf-owIPaNEZf50VfCdVdP_DKoRhR5IW4z593V_2-fFoeaOtizshYDwONkoLNx4w4ZCsNurwxJMH5gWZeTmZ5sgRLKfK69ucizJCA67d0Sh6KcRvNjlUL1ru3QZ10O98'; // generado desde PHP

const resultado = decryptQRToken(qrToken);

console.log('Resultado:', resultado);

