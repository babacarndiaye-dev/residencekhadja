# Déploiement — Résidence Khadija sur hébergement mutualisé cPanel

Cible : **https://residencekhadija.pits.sn/** — sous-domaine, cPanel, sans accès root.
Livraison du code : **Git + GitHub** (`git pull` côté serveur).

---

## 0. Architecture cible

```
/home/CPANELUSER/
├── laravel/
│   └── residence-khadija/          ← le dépôt Git cloné ICI (toute l'app)
│       ├── app/  bootstrap/  config/  ...
│       ├── public/                 ← racine web du sous-domaine pointe LÀ
│       │   ├── index.php
│       │   └── build/              ← assets Vite (versionnés dans le dépôt)
│       ├── storage/                ← doit être inscriptible
│       └── .env                    ← créé sur le serveur, JAMAIS dans Git
└── (public_html reste au site principal pits.sn)
```

Le cœur de Laravel (`app/`, `.env`, `storage/`, `vendor/`) reste **hors** d'un
dossier web : seul `public/` est exposé.

---

## 1. Pré-requis hébergement (à vérifier avec pits.sn / dans cPanel)

| Élément | Exigence | Où dans cPanel |
|---|---|---|
| PHP | **8.3 ou 8.4**, extensions `mbstring intl bcmath pdo_mysql gd fileinfo openssl ctype tokenizer curl` | *MultiPHP Manager* + *MultiPHP INI Editor* / *Select PHP Version* |
| MySQL / MariaDB | 5.7+ / 10.4+ | *Bases de données MySQL* |
| Accès shell | **Terminal cPanel** ou **SSH** (pour Composer + Artisan) | *Terminal* / *Accès SSH* |
| Composer | disponible en ligne de commande (`composer` ou `composer.phar`) | via Terminal |
| Tâches Cron | autorisées | *Tâches Cron* |
| Git | *Git™ Version Control* présent (recommandé) ou `git` en shell | *Git Version Control* |

> **Si Terminal/SSH n'est pas disponible :** voir l'annexe B (upload d'un `vendor/`
> pré-construit par FTP). Tout le reste du guide suppose l'accès shell.

---

## 2. En local — préparer et publier le dépôt

### 2.1 Compiler les assets (indispensable : le mutualisé n'a pas Node)

```sh
npm ci
npm run build          # régénère public/build/  → à committer
```

### 2.2 Initialiser Git et pousser sur GitHub

Le dépôt local n'existe pas encore. Étapes (le premier commit est déjà préparé
par l'assistant si vous utilisez ce runbook tel quel) :

```sh
git init
git add -A
git commit -m "Résidence Khadija — socle applicatif complet"

# Créer un dépôt VIDE sur github.com (privé de préférence) : « residence-khadija »
git remote add origin git@github.com:VOTRE-COMPTE/residence-khadija.git
git branch -M main
git push -u origin main
```

À chaque push sur `main`, la CI (`.github/workflows/ci.yml`) rejoue Pint +
`npm run build` + toute la suite de tests.

> **Ne jamais committer** `.env`, `.env.production`, `vendor/`, `node_modules/` —
> ils sont déjà dans `.gitignore`. `public/build/` **est** versionné, volontairement.

---

## 3. Sur le serveur — base de données

Dans cPanel → *Bases de données MySQL* (déjà fait ici) :

| | Valeur |
|---|---|
| Hôte | `localhost` |
| Base | `pitssn1_residencekhadija` |
| Utilisateur | `residencekhadija` |
| Mot de passe | `residencekhadija` |
| Privilèges | **ALL PRIVILEGES** de l'utilisateur sur la base |

> ⚠️ cPanel préfixe habituellement **aussi** le nom d'utilisateur. Si la migration
> renvoie `SQLSTATE[HY000] [1045] Access denied`, l'utilisateur réel est
> probablement `pitssn1_residencekhadija` → corriger `DB_USERNAME` dans `.env`.
> Pensez aussi à mettre un mot de passe plus robuste que `residencekhadija`.

---

## 4. Sur le serveur — récupérer le code

### Option A (recommandée) — cPanel « Git Version Control »

1. cPanel → *Git Version Control* → **Create**.
2. **Clone URL** : l'URL SSH ou HTTPS du dépôt GitHub (pour un dépôt privé,
   ajouter la clé SSH du serveur — affichée dans *Accès SSH* → *Manage SSH Keys* —
   comme *Deploy key* sur GitHub).
3. **Repository Path** : `/home/CPANELUSER/laravel/residence-khadija`
4. Créer. cPanel clone le dépôt.
5. Mises à jour ultérieures : bouton **Update from Remote** (= `git pull`) puis
   **Deploy HEAD Commit** (exécute `.cpanel.yml` → `deploy/deploy.sh`).

### Option B — git en ligne de commande (Terminal / SSH)

```sh
mkdir -p ~/laravel && cd ~/laravel
git clone https://github.com/VOTRE-COMPTE/residence-khadija.git
cd residence-khadija
```

---

## 5. Sur le serveur — fichier `.env`

Le fichier **`.env.production`** (présent dans votre copie locale, ignoré par Git,
déjà rempli avec la base `pitssn1_residencekhadija`) est le modèle prêt à l'emploi.

1. Le téléverser dans `~/laravel/residence-khadija/` (Gestionnaire de fichiers /
   FTP / `scp`) **puis le renommer `.env`**, ou copier son contenu dans un `.env`
   créé sur place.
2. Générer la clé applicative et vérifier :

```sh
cd ~/laravel/residence-khadija
php artisan key:generate --force  # --force : sans confirmation en production
grep '^APP_KEY=' .env            # doit afficher APP_KEY=base64:...
php artisan config:clear          # au cas où une config vide aurait été mise en cache
php artisan about                 # doit afficher env=production, debug=false
```

> **`APP_KEY` vide = HTTP 500 « No application encryption key » sur toutes les
> pages.** Si ça arrive : `php artisan key:generate --force` puis
> `php artisan optimize:clear && php artisan config:cache` (la clé était peut-être
> figée dans `bootstrap/cache/config.php`). `deploy/deploy.sh` gère désormais ce cas.

Points déjà positionnés dans le modèle : `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL=https://residencekhadija.pits.sn`, `SESSION_SECURE_COOKIE=true`,
journaux en rotation quotidienne, e-mail SMTP Brevo, files et cache en base.

---

## 6. Sur le serveur — installer, migrer, mettre en cache

Un script fait tout (voir [deploy/deploy.sh](deploy/deploy.sh)) :

```sh
cd ~/laravel/residence-khadija
bash deploy/deploy.sh
```

Il enchaîne : `artisan down` → `composer install --no-dev -o` →
`migrate --force` → `storage:link` → `optimize:clear` + `config:cache` +
`route:cache` + `view:cache` + `event:cache` → `queue:restart` → `artisan up`.

### Premier déploiement uniquement — jeu de données initial

```sh
php artisan db:seed --force        # comptes staff, catégories, carte, tarifs…
```

Comptes créés (mot de passe **`khadija`** — **à changer à la 1re connexion**) :

| Rôle | E-mail | PIN caisse |
|---|---|---|
| Direction | `direction@residence-khadija.sn` | `2468` |
| Administrateur | `admin@residence-khadija.sn` | `1379` |
| Réception | `reception@residence-khadija.sn` | (auto) |
| Gouvernante | `housekeeping@residence-khadija.sn` | — |

Connexion admin : `https://residencekhadija.pits.sn/admin/login`.

---

## 7. Sur le serveur — racine web du sous-domaine

cPanel → *Domaines* (ou *Sous-domaines*) → `residencekhadija.pits.sn` →
**Document Root** = `/home/CPANELUSER/laravel/residence-khadija/public`

- `public/.htaccess` (fourni) gère la réécriture Laravel **et** la redirection
  HTTP→HTTPS (compatible proxy via `X-Forwarded-Proto`).
- **HTTPS** : cPanel → *SSL/TLS Status* → *Run AutoSSL* sur le sous-domaine
  (certificat gratuit Let's Encrypt). Si un Cloudflare est devant, le laisser en
  mode **Full**, pas *Flexible* (sinon boucle de redirection).

> **Si l'hébergeur interdit de changer la Document Root** → annexe A.

---

## 8. Sur le serveur — permissions

```sh
cd ~/laravel/residence-khadija
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

`storage/` et `bootstrap/cache/` doivent être inscriptibles par l'utilisateur PHP
(en mutualisé, c'est le même que le compte cPanel → 775 suffit).

---

## 9. Sur le serveur — tâches Cron

Une **seule** tâche suffit : le planificateur Laravel déclenche tout le reste
(file d'attente e-mail/SMS via `queue:work --stop-when-empty`, alertes caisse
`pos:alerts`, rappels de pré-arrivée `reservations:pre-arrival`, snapshots BI…).

cPanel → *Tâches Cron* → ajouter :

```
* * * * * /usr/local/bin/php /home/CPANELUSER/laravel/residence-khadija/artisan schedule:run >> /dev/null 2>&1
```

- Adapter le chemin de `php` (cPanel → *Select PHP Version* affiche le binaire ;
  souvent `/usr/local/bin/php` ou `/opt/cpanel/ea-php84/root/usr/bin/php`).
- Adapter `CPANELUSER`.
- La file tourne donc **par minute** : suffisant pour un hôtel. Un worker
  permanent (Supervisor) n'est pas nécessaire et n'est de toute façon pas
  disponible en mutualisé.

---

## 10. Vérifications post-déploiement

```sh
php artisan about
php artisan migrate:status         # toutes « Ran »
php artisan queue:work --once      # traite un job puis rend la main (test)
php artisan schedule:list
```

Dans le navigateur :

- [ ] `https://residencekhadija.pits.sn/` → page d'accueil vitrine, cadenas HTTPS
- [ ] `https://residencekhadija.pits.sn/up` → « Application is up » (health check)
- [ ] `/admin/login` → connexion `direction@residence-khadija.sn` / `khadija`
- [ ] `/admin` → tableau de bord sans erreur
- [ ] Passer une réservation de test depuis la vitrine → e-mail de confirmation
      reçu (vérifier après ≤ 1 min, le temps que le cron traite la file)
- [ ] `/admin/pos` → ouvrir une caisse, encaisser un ticket test
- [ ] Changer les mots de passe des comptes staff, régénérer les PIN caisse

---

## 11. Redéploiements (mises à jour)

En local : `npm run build` si le front a changé → `git commit` → `git push`.

Sur le serveur :

```sh
cd ~/laravel/residence-khadija
git pull            # ou bouton « Update from Remote » dans cPanel
bash deploy/deploy.sh
```

`deploy.sh` remet l'app en maintenance pendant `composer install` + `migrate`,
recompile les caches, puis relève l'app. Pas besoin de `db:seed` (1re fois only).

---

## Annexe A — Document Root non modifiable

Si le sous-domaine est figé sur `~/residencekhadija.pits.sn/` :

1. Cloner l'app **ailleurs** : `~/laravel/residence-khadija`.
2. Copier le contenu de `public/` dans le dossier web figé :
   `cp -r ~/laravel/residence-khadija/public/* ~/residencekhadija.pits.sn/`
   et le `.htaccess` : `cp ~/laravel/residence-khadija/public/.htaccess ~/residencekhadija.pits.sn/`
3. Éditer `~/residencekhadija.pits.sn/index.php` — corriger les deux chemins :

   ```php
   require __DIR__.'/../laravel/residence-khadija/vendor/autoload.php';
   $app = require_once __DIR__.'/../laravel/residence-khadija/bootstrap/app.php';
   ```

4. Refaire l'étape 2 (copie de `public/`) à **chaque** déploiement qui touche aux
   assets — ou ajouter la commande `cp` à la fin de `deploy/deploy.sh`.

---

## Annexe B — Sans Terminal ni SSH (Composer indisponible)

1. En local : `composer install --no-dev --optimize-autoloader` puis
   téléverser **tout** le projet, `vendor/` compris, par FTP dans
   `~/laravel/residence-khadija/`.
2. Migrations sans shell : créer une route protégée jetable OU importer le SQL.
   Le plus simple : en local
   `php artisan schema:dump` puis exécuter le `.sql` produit via **phpMyAdmin**
   (cPanel), et exécuter les migrations restantes de la même façon après chaque
   mise à jour. *(Solution de dépannage — l'accès shell reste fortement conseillé.)*
3. Caches : téléverser aussi `bootstrap/cache/*.php` générés en local avec un
   `.env` de production (chemins identiques exigés → fragile).

---

## Annexe C — Dépannage

| Symptôme | Cause probable / correctif |
|---|---|
| `500` + page blanche | `storage/logs/` non inscriptible, ou `APP_KEY` vide → étape 5 & 8. Activer temporairement `APP_DEBUG=true` + `php artisan config:clear`. |
| `1045 Access denied` (migrate) | `DB_USERNAME` → essayer `pitssn1_residencekhadija` (préfixe cPanel). |
| Redirection HTTPS en boucle | Cloudflare en mode *Flexible* → passer en *Full*, ou retirer le bloc HTTPS de `public/.htaccess`. |
| CSS/JS 404 | `public/build/` absent du dépôt → `npm run build` puis committer ; vérifier `APP_URL` en `https://`. |
| E-mails/SMS jamais envoyés | La tâche Cron `schedule:run` n'est pas active (étape 9), ou `MAIL_MAILER` pas en `smtp`. `php artisan queue:work --once` pour tester. |
| `419 Page Expired` sur les formulaires | `APP_URL` ≠ domaine réel, ou `SESSION_DOMAIN` erroné, ou horloge serveur décalée. |
| Après déploiement, ancien code servi | `php artisan optimize:clear` puis `config:cache route:cache view:cache` (fait par `deploy.sh`). |
| `Please provide a valid cache path` | `mkdir -p bootstrap/cache storage/framework/{cache,sessions,views}` puis étape 8. |

---

## Récapitulatif « je repars de zéro »

```sh
# --- local ---
npm ci && npm run build
git add -A && git commit -m "maj" && git push

# --- serveur (Terminal cPanel) ---
cd ~/laravel/residence-khadija
git pull
bash deploy/deploy.sh
# (1re fois seulement) php artisan db:seed --force
```
