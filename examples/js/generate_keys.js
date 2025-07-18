const crypto = require('crypto');

const encryptionKey = crypto.randomBytes(32).toString('hex'); // 64 chars
const signingKey = crypto.randomBytes(32).toString('hex');    // 64 chars

console.log('--- 🔐 Quralo Keys (.env format, HEX) ---');
console.log(`QURALO_ENCRYPTION_KEY=${encryptionKey}`);
console.log(`QURALO_SIGNING_KEY=${signingKey}`);