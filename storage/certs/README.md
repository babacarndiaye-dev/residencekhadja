# storage/certs/

Certificat **auto-signé** pour la terminaison TLS de la borne / des tablettes du
réseau local (`deploy/borne-https-proxy.mjs`). Sert **uniquement** en local ; la
production (residencekhadija.pits.sn) utilise le certificat AutoSSL de l'hébergeur.

Les fichiers `.pem` ne sont **pas** versionnés (clé privée). Les régénérer sur une
nouvelle machine borne :

```sh
cd storage/certs
openssl req -x509 -newkey rsa:2048 -nodes -days 1200 \
  -keyout borne-key.pem -out borne-cert.pem \
  -subj "/CN=Residence Khadija Borne" \
  -addext "subjectAltName=IP:192.168.1.10,DNS:localhost"
```

Puis importer `borne-cert.pem` comme certificat de confiance sur les tablettes.
