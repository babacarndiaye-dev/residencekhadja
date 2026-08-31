#!/usr/bin/env node
// Terminaison TLS pour la borne / les tablettes du réseau local.
//
// Écoute en HTTPS sur :8443 (toutes interfaces) et relaie vers le serveur
// applicatif `php artisan serve` (http://127.0.0.1:8000). Ajoute les en-têtes
// X-Forwarded-* pour que Laravel génère des URL https correctes
// (voir bootstrap/app.php -> trustProxies). Zéro dépendance npm.
//
//   node deploy/borne-https-proxy.mjs
//
// Variables d'environnement optionnelles :
//   BORNE_LISTEN   interface d'écoute        (défaut 0.0.0.0)
//   BORNE_PORT     port HTTPS                (défaut 8443)
//   BORNE_TARGET   backend HTTP              (défaut http://127.0.0.1:8000)
//   BORNE_CERT     chemin du certificat PEM  (défaut storage/certs/borne-cert.pem)
//   BORNE_KEY      chemin de la clé PEM      (défaut storage/certs/borne-key.pem)

import http from 'node:http';
import https from 'node:https';
import fs from 'node:fs';
import net from 'node:net';
import path from 'node:path';
import { fileURLToPath, URL } from 'node:url';

const root = path.resolve(fileURLToPath(new URL('.', import.meta.url)), '..');
const resolve = (p) => (path.isAbsolute(p) ? p : path.join(root, p));

const LISTEN = process.env.BORNE_LISTEN || '0.0.0.0';
const PORT = Number(process.env.BORNE_PORT || 8443);
const TARGET = new URL(process.env.BORNE_TARGET || 'http://127.0.0.1:8000');
const CERT = resolve(process.env.BORNE_CERT || 'storage/certs/borne-cert.pem');
const KEY = resolve(process.env.BORNE_KEY || 'storage/certs/borne-key.pem');

const tlsOptions = { cert: fs.readFileSync(CERT), key: fs.readFileSync(KEY) };

function forwardHeaders(req) {
  const headers = { ...req.headers };
  const clientIp = (req.socket.remoteAddress || '').replace(/^::ffff:/, '');
  headers['x-forwarded-for'] = headers['x-forwarded-for']
    ? `${headers['x-forwarded-for']}, ${clientIp}`
    : clientIp;
  headers['x-forwarded-proto'] = 'https';
  headers['x-forwarded-port'] = String(PORT);
  headers['x-forwarded-host'] = req.headers.host || '';
  return headers;
}

const server = https.createServer(tlsOptions, (req, res) => {
  const proxyReq = http.request(
    {
      host: TARGET.hostname,
      port: TARGET.port,
      method: req.method,
      path: req.url,
      headers: forwardHeaders(req),
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode, proxyRes.headers);
      proxyRes.pipe(res);
    },
  );
  proxyReq.on('error', (err) => {
    res.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' });
    res.end(`502 — backend injoignable : ${err.message}\n`);
  });
  req.pipe(proxyReq);
});

// Passthrough des connexions WebSocket / Upgrade.
server.on('upgrade', (req, clientSocket, head) => {
  const upstream = net.connect(Number(TARGET.port), TARGET.hostname, () => {
    const headers = forwardHeaders(req);
    let raw = `${req.method} ${req.url} HTTP/1.1\r\n`;
    for (const [key, value] of Object.entries(headers)) raw += `${key}: ${value}\r\n`;
    raw += '\r\n';
    upstream.write(raw);
    if (head && head.length) upstream.write(head);
    upstream.pipe(clientSocket);
    clientSocket.pipe(upstream);
  });
  upstream.on('error', () => clientSocket.destroy());
  clientSocket.on('error', () => upstream.destroy());
});

server.on('clientError', (err, socket) => {
  if (socket.writable) socket.end('HTTP/1.1 400 Bad Request\r\n\r\n');
});

server.listen(PORT, LISTEN, () => {
  console.log(`[borne] HTTPS https://${LISTEN}:${PORT}  ->  ${TARGET.origin}`);
});
