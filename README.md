# KHADIJA DIGITAL ECOSYSTEM

Premières briques du cahier des charges *Khadija Digital Ecosystem* pour l'Hôtel Résidence
Khadija (Thiès). Site + Booking + PMS + QR Ordering + Opérations + Économat/Finance.

> **Phase 4** — Site web premium + tunnel de réservation.
> **Phase 5** — Socle PMS : réservations persistées (anti-double réservation), back-office
> authentifié (Front Office, check-in/out, statuts chambres, clients/CRM, audit).
> **Phase 6** — Restaurant + QR Ordering : carte digitale, commande table & room service,
> Kitchen Display System, encaissement + imputation folio PMS, admin carte/QR.
> **Phase 7** — Housekeeping (plan de ménage, contrôle qualité, incidents) + Maintenance
> (équipements, tickets correctifs, plans préventifs récurrents auto-générés).
> **Phase 8** — Stocks (magasins, CUMP, mouvements, transferts, inventaires, seuils) +
> Achats (demande → validation → commande → réception → facture) + Finance (journal
> recettes/dépenses, caisses, créances/dettes) + Comptabilité (partie double, écritures
> automatiques, balance, grand livre général, **compte de résultat, bilan, déclaration de
> TVA**, **exercices comptables + clôture** avec verrouillage de la période, contre-passation,
> export CSV).
> **Phase 9** — RH : dossiers employés (services, fonctions, contrats, documents),
> planning équipes/shifts avec détection de conflits, **pointage borne QR + code PIN**
> (calcul retard / heures supp. / absence par rapport au shift), congés & absences avec
> solde, **paie** brut/net paramétrable (composants + éléments variables + cotisations),
> bulletins imprimables, passation en trésorerie + comptabilité. Cartes agent CR80 imprimables.
> **Phase 10** — CRM 360° + Fidélité + Marketing : fiche client enrichie (naissance, origine,
> étiquettes, **consentement marketing** horodaté), historique relation client (timeline),
> programme **Khadija Privilège** (paliers dynamiques, points crédités automatiquement à
> l'encaissement, utilisation en remise, ajustements), **segments** dynamiques (règles
> combinables + aperçu live), **campagnes** e-mail / SMS (destinataires calculés depuis un
> segment, opt-in imposé, **envoi réel** — Mailable en file pour l'e-mail, `App\Services\Sms`
> pour le SMS —, stats), **codes promo** en base utilisables dans le tunnel de réservation.
> **Phase 11** — Commercial & Événements (MICE) : **pipeline** commercial (affaires,
> activités & tâches, étapes contrôlées), **devis** événementiels (lignes + forfaits
> restauration/technique, TVA, acompte, impression), **salles** avec **anti-double
> réservation** sur créneau, **événements** option → confirmé → réalisé, **feuille de
> fonction (BEO)**, encaissement acompte / solde en trésorerie + comptabilité, contrat
> imprimable, calendrier mensuel.
> **Phase 12** — Paiement en ligne : abstraction **agnostique du prestataire**
> (`PaymentGateway` + driver `simulator`, slots PayDunya / CinetPay), **intentions de
> paiement** pour l'acompte/solde réservation, l'addition QR et l'acompte/solde événement,
> **page de paiement hébergée** (carte, Orange Money, Wave, Free Money), capture idempotente
> + **webhook signé (HMAC)**, **reçu par e-mail** au payeur, remboursements, suivi back-office.
> Le règlement solde la cible métier via les mécanismes existants (observer `Payment` →
> trésorerie + fidélité, `FinanceLedger`).
> **Phase 13** — Décisionnel (BI) : `App\Services\Analytics` consolide les KPI de tous les
> domaines (occupation, ADR, RevPAR, F&B, opérations, finance, CRM, MICE, paiements),
> **instantanés quotidiens** (`daily_metrics`, `bi:snapshot`) pour les tendances,
> **tableau de bord exécutif** avec alertes vs objectifs et mini-graphes SVG,
> **bibliothèque de rapports** exportables CSV (11 rapports) + **rapports planifiés**
> (`report_schedules`, `bi:run-schedules` → **CSV envoyé par e-mail** aux destinataires).
> **Phase 14** — Distribution & Channel Manager : **calendrier ARI** (disponibilité +
> restrictions stop-sell / CTA / CTD / min-max stay par date & catégorie), **canaux**
> (Booking.com, Expedia, Airbnb, Hotelbeds — connecteur `simulator`), **grille tarifaire**
> par canal (parité / majoration), **réservations entrantes** idempotentes (webhook +
> simulateur) avec **commission** comptabilisée au départ du client, **flux iCal** public
> par catégorie, journal de synchronisation, poussée ARI (`channels:push`).
> **Phase 15** — Application mobile invité (**PWA `/app`**, installable + hors-ligne) :
> connexion par **référence + nom** ou **lien magique** signé (aucun compte), accueil,
> **folio + règlement du solde en ligne**, **demandes chambre** (routées vers réception /
> housekeeping / maintenance), room service, **carte de fidélité** + inscription, Wi-Fi.
> Écran back-office « Demandes clients », **carte d'accueil QR** sur la fiche réservation.
>
> Le socle du cahier des charges est couvert (§4–60). Tous les e-mails transactionnels et
> marketing sont branchés (Mailables en file), les webhooks sont signés, un **centre de
> notifications** in-app alerte le personnel, la **CI** (GitHub Actions) impose `pint` + les
> tests. Pour la production : pointer `MAIL_MAILER` sur un vrai SMTP et lancer le worker de
> file (`deploy/supervisor-queue.conf.example`). Le back-office couvre le **CRUD complet des
> catégories de chambre** (contenu vitrine + photos), un **écran ménage tactile** pour les
> agents d'étage et l'**upload des photos de la carte**. Le **module Satisfaction /
> e-réputation** est en place : enquête post-séjour (lien personnel, sans compte) → note
> globale + NPS + notes par critère + commentaire ; tableau de bord (NPS, moyenne, taux de
> réponse, tendance), alerte détracteur au personnel, et publication modérée des avis
> consentis directement sur la page d'accueil. Suite : drivers de paiement réels
> (PayDunya / CinetPay), connecteurs OTA, i18n EN/Wolof/Arabe.

---

## Stack

| Couche | Choix |
|--------|-------|
| Backend | Laravel 13 (PHP 8.4) |
| Base de données | **MySQL / MariaDB** (`residence_khadija`) — SQLite en test |
| Vues | Blade + composants |
| CSS | Tailwind CSS v4 (`@tailwindcss/vite`) |
| JS | Alpine.js |
| Polices | Montserrat + Poppins (charte HRK), **auto-hébergées** via `laravel-vite-plugin/fonts` (Bunny) ; icônes Google Material Symbols |
| Auth | Session Laravel + 11 rôles (`admin`, `direction`, `reception`, `restaurant`, `housekeeping`, `maintenance`, `stock`, `finance`, `rh`, `marketing`, `commercial`) |
| Tests | PHPUnit — 264 tests, suite `Feature` |

Aligné avec le socle recommandé au § 121 du cahier.

---

## Démarrer

```bash
composer install
npm install
php artisan key:generate

# Base de données (MySQL/MariaDB via XAMPP)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS residence_khadija CHARACTER SET utf8mb4"
php artisan migrate:fresh --seed        # schéma + données de démonstration

npm run build                            # ou: npm run dev  (HMR)
php artisan serve                        # http://127.0.0.1:8000
```

### Comptes back-office (seed) — mot de passe `khadija`

| Rôle | E-mail | Accès |
|------|--------|-------|
| Direction | `direction@residence-khadija.sn` | tableau de bord, réservations (lecture), clients, carte, QR, paramètres |
| Réception | `reception@residence-khadija.sn` | réservations (confirmer / check-in / check-out / paiements), chambres, clients, KDS & commandes |
| Restauration | `restaurant@residence-khadija.sn` | KDS, commandes, service en salle, carte, QR codes |
| Housekeeping | `housekeeping@residence-khadija.sn` (+ `etage@…`) | tableau de bord, chambres, plan de ménage, contrôle qualité, incidents |
| Maintenance | `maintenance@residence-khadija.sn` | équipements, tickets, plans préventifs |
| Économat / Stocks | `economat@residence-khadija.sn` | articles, mouvements, transferts, inventaires, fournisseurs, demandes & réceptions d'achat |
| Finance & Compta | `finance@residence-khadija.sn` | journal financier, caisses, créances/dettes, plan de comptes, écritures, balance, grand livre, règlement fournisseurs |
| Ressources humaines | `rh@residence-khadija.sn` | employés, contrats, documents, planning, pointage, congés, formations, paie (génération) |
| Administrateur | `admin@residence-khadija.sn` | tout + journal d'audit |

Employés (26 seedés, sans login) : code de pointage borne = **4 derniers chiffres du matricule**
(ex. matricule `RK0101` → code `0101`). Borne : **`/pointage`** ou **`/pointage/{matricule}`**.

Back-office : **`/admin`** (redirige vers `/admin/login`).

XAMPP : le projet peut aussi être servi via Apache en pointant le vhost sur `public/`.

---

## Contenu = 100 % paramétrable (aucun texte en dur dans les vues)

**Réglages du site en back-office** — `/admin/parametres/site` (direction) : ~45 champs sur
11 onglets (identité, contact, horaires, réseaux, accès, réservation, restauration, fidélité,
Wi-Fi, SEO, e-mails). Ils sont stockés dans `site_settings` (clé → valeur) ; le manifeste
`config/settings.php` décrit chaque champ (type, validation, chemin de config surchargé).
`App\Support\SiteSettings::apply()` surcharge la config au démarrage
(`AppServiceProvider::boot`) — **aucune vue ni service ne change**, tout continue de lire
`config('hotel.name')`, `config('booking.tax_rate')`… Tant qu'un champ n'est pas enregistré,
c'est la valeur du fichier qui s'applique. Cache invalidé à chaque enregistrement.

Le contenu structuré de la vitrine vit toujours dans `config/*.php` :

| Fichier | Contenu |
|---------|---------|
| `config/hotel.php` | Identité, coordonnées, horaires, réseaux, chiffres-clés, fidélité, devise |
| `config/navigation.php` | Menu principal (libellés via `lang/`) |
| `config/rooms.php` | Chambres & suites (tarifs, surfaces, équipements, photos) |
| `config/dining.php` | Points de restauration + rubrique « Saveurs du Sénégal » (storytelling) |
| `config/spaces.php` | Piscine, rooftop, fitness, spa, business corner, salles de séminaire |
| `config/offers.php` | Offres / séjours packagés |
| `config/experiences.php` | Expériences Teranga |
| `config/gallery.php` | Galerie (groupes + filtres) |
| `config/reviews.php` | Avis clients |
| `config/booking.php` | Tunnel : taxes, plans tarifaires, services, codes promo, demandes spéciales |
| `config/seo.php` | Titres / descriptions / OG par défaut, mots-clés locaux, ID analytics |

i18n : `lang/fr/site.php` (libellés d'interface). La structure est prête pour
**EN / Wolof / Arabe (RTL)** — il suffira d'ajouter `lang/en/…`, `lang/wo/…`, `lang/ar/…`
et un sélecteur de langue + middleware de locale.

Les **photos** sont des placeholders premium (URLs Unsplash paramétrées, dans les `config/`).
À remplacer par les visuels de l'hôtel servis via CDN / stockage optimisé (WebP/AVIF).

**Logo** : `public/img/logo-hrk.svg` (+ `logo-hrk-mono.svg` pour le pied de page).
C'est une **recréation SVG** de votre logo HRK dans la teinte terre cuite `#d9532e` ;
remplacez ces fichiers par l'export vectoriel officiel quand vous l'avez.

---

## Pages

Accueil · L'hôtel · Chambres & Suites (liste + détail) · Restaurant & Saveurs du Sénégal ·
Espaces & bien-être · Séminaires & Événements (+ formulaire devis) · Expériences ·
Galerie (filtrable) · Offres · Contact & localisation · 404 sur-mesure.

### Tunnel de réservation — `/reservation`

`Dates → Chambre & tarif → Options & demandes → Récapitulatif & coordonnées → Confirmation`

- État porté par la **session** (`booking`), gardes-fous entre étapes.
- Devis calculé par `App\Services\BookingQuote` : nuits, plans tarifaires
  (flexible / non remboursable), services (par personne / par nuit / par séjour),
  **codes promo** (`TERANGA`, `DIRECT12`, `BIENVENUE`), taxes, taxe de séjour, dépôt 30 %.
- Fin de parcours = **réservation `pending` persistée en base** avec référence `HRK-XXXXXX`.
  Contrôle de disponibilité **anti-double réservation** (`App\Services\Availability`,
  transaction + `lockForUpdate`). Reste à venir : e-mail de confirmation (queue) + paiement.

---

## PMS / Back-office — `/admin`

Socle du Property Management System (Phase 5 du cahier, §12–16, §33–34, §69–71).

### Modèle de données

`hotels` · `room_categories` · `rooms` (chambres physiques, 8 statuts) · `rate_plans` ·
`guests` · `reservations` (6 statuts) · `payments` · `audit_logs`. Base **multi-hôtels**
d'emblée (`hotel_id` partout). Les catégories/chambres/tarifs sont **seedés depuis
`config/*.php`** puis pilotés en base.

### Écrans

| Écran | Rôles | Contenu |
|-------|-------|---------|
| Tableau de bord | tous | Occupation, ADR/RevPAR, arrivées/départs du jour, clients en séjour, état des chambres, dernières réservations |
| Réservations | réception, direction | Liste filtrable (statut, dates, recherche) · fiche détail |
| — **Réservation sur place** | réception | Formulaire walk-in : client + catégorie + dates → devis (`BookingQuote`) + garde-fou `Availability` ; option **check-in immédiat** (attribution d'une chambre propre, statut `checked_in`). Canal `walk_in`. |
| — actions | réception | Confirmer · annuler · no-show · **check-in** (attribution chambre) · **check-out** (encaissement solde + n° facture) · enregistrer un paiement |
| Facture | réception, direction | Facture imprimable / PDF (`window.print`) |
| Plan des chambres | réception, housekeeping | Plan des chambres par étage, changement de statut |
| Gestion des chambres | direction, réception | Parc de chambres physiques : création à l'unité **ou en série** (préfixe + plage + zéros de remplissage), renommage / catégorie / étage / bâtiment / notes, **activation / mise hors service** (retire la chambre du stock vendable, refusée si `occupee`), suppression (bloquée si liée à des réservations). Synthèse « vendables » par catégorie. |
| Messages | direction, réception | Boîte du formulaire de contact de la vitrine : liste filtrable par statut (nouveau / lu / traité), fiche message (lecture ⇒ « lu »), **répondre par e-mail** (mailto), marquer traité / rouvrir. Chaque envoi crée un `ContactMessage` **et** met en file `App\Mail\ContactMessageReceived` vers `hotel.contact.email` (Reply-To = expéditeur). |
| Clients (CRM) | réception, direction | Liste + fiche 360° (coordonnées, valeur cumulée, historique séjours) |
| Paramètres | direction | Réglage rapide tarif / capacité des catégories, coefficients des plans tarifaires. Liens vers : **Réglages du site** (`/admin/parametres/site` — identité, contact, réservation, fidélité, SEO, Wi-Fi… surchargent `config/*.php`) et **CRUD complet des catégories** (`/admin/parametres/categories` — création / suppression bloquée si chambres ou réservations, champs vitrine + **photos multiples** sur disque `public`). La vitrine lit la base — une nouvelle catégorie est visible aussitôt. |
| Notifications | tout le personnel | Cloche + compteur dans le bandeau (bureau & mobile), page `/admin/notifications`, « tout marquer lu ». Table Laravel `notifications` + `App\Notifications\StaffAlert` ; diffusion ciblée par rôle via `App\Support\Notify::roles()` (admin toujours inclus). Déclencheurs : nouvelle réservation en ligne, nouveau message de contact, demande client (PWA). |
| Piscine | réception, direction | `/admin/piscine` : réservation de **transats / bains doubles / cabanas** par créneau (matinée / après-midi / journée), anti-double-réservation (`App\Services\PoolBooking`), lien vers un séjour en cours, prix auto ½ j / j, suivi de statut (réservé → installé → terminé / absent). `/admin/piscine/parc` gère le parc d'emplacements. |
| Salles & séminaires | commercial, direction, réception | `/admin/salles` : **planning d'occupation sur 7 jours** (salles × jours à partir des réservations de salle des événements), cartes du parc. Le CRUD des salles et l'affectation à un événement restent dans le module **Événements** (`/admin/evenements/salles`). |
| Journal d'audit | admin | Connexions, confirmations, check-in/out, paiements, changements de statut |

RBAC : middleware `role:...` (`app/Http/Middleware/EnsureRole.php`), le rôle `admin`
passe partout. Guests non connectés → `/admin/login`. Connexion **throttlée** (6/min).
**Mot de passe oublié** : `/admin/mot-de-passe-oublie` → lien de réinitialisation par
e-mail (notification FR, `AppServiceProvider::boot` + `Password` broker), page
`/admin/reinitialiser/{token}`. Comptes désactivés (`is_active = false`) refusés.

**E-mails invité** (tous `App\Mail\*`, en file d'attente, envoyés si le client a une adresse) :
`ReservationConfirmed` (fin de tunnel, confirmation d'une réservation en attente, walk-in —
avec le lien magique app invité si le séjour est accessible) · `ReservationCancelled`
(annulation) · `StayCompleted` (check-out, avec n° de facture + solde) · `PreArrivalReminder`
(J-2, via la commande planifiée `reservations:pre-arrival`, garde-fou `pre_arrival_sent_at`).
Pages d'erreur **403 / 404 / 500 / 503** aux couleurs de l'hôtel.

**Webhooks entrants signés** : `App\Support\WebhookSignature` vérifie une signature
HMAC-SHA256 du corps brut (en-tête `X-Signature`, préfixe `sha256=` toléré) sur
`POST /paiement/webhook/{provider}` et `POST /distribution/webhook/{channel}`. Le secret
vient de `config/payments.php` (`providers.*.webhook_secret`, env `*_WEBHOOK_SECRET`) ou de
`channels.credentials['webhook_secret']` / `config('distribution.webhook_secret')`.
Signature invalide ⇒ `401 {"reason":"bad_signature"}` + `Log::warning`. Sans secret
configuré (recette, connecteur `simulator`) la vérification n'est pas imposée mais l'absence
de protection est journalisée (`*.webhook.unverified`).
**Mise en production** — en dev la file est vidée chaque minute par le planificateur
(`queue:work --stop-when-empty`). En production :

- `MAIL_MAILER=smtp` (+ hôte / identifiants), `QUEUE_CONNECTION=database` conservé ;
- **SMS** (`config/sms.php`) : `SMS_DRIVER=http|twilio|orange` + identifiants ; `SMS_DRIVER=log`
  (défaut) journalise sans envoyer. Les SMS invité transactionnels (confirmation de résa,
  pré-arrivée, suivi des demandes) partent via `App\Jobs\SendSms` si `SMS_TRANSACTIONAL=true`
  et que le client a un numéro — le worker est donc requis pour l'e-mail **et** le SMS ;
- worker permanent : `deploy/supervisor-queue.conf.example` (Supervisor / systemd) ;
- planificateur en crontab : `* * * * * php artisan schedule:run` ;
- secrets de webhook : `PAYDUNYA_WEBHOOK_SECRET` / `CINETPAY_WEBHOOK_SECRET` / `CHANNEL_WEBHOOK_SECRET` ;
- `php artisan config:cache route:cache view:cache` après déploiement.

**Intégration continue** : `.github/workflows/ci.yml` (PHP 8.4) exécute `pint --test`,
`npm ci && npm run build` puis `php artisan test` (SQLite en mémoire) sur chaque push / PR.
Le dépôt est Pint-clean.

### Services

- `App\Services\Availability` — disponibilité par catégorie sur une plage, verrou optionnel.
- `App\Services\BookingQuote` — devis (lit la catégorie en base, règles de prix dans `config/booking.php`).
- `App\Models\AuditLog::record()` — trace horodatée + IP + utilisateur.

---

## QR Ordering — carte digitale & commande (Phase 6)

Boucle complète scan → carte → panier → commande → cuisine → service → encaissement,
pour les tables (multi-espaces) et le room service (§21–32).

### Modèle de données

`venues` (points de vente + Room Service) · `qr_locations` (44 tables + 48 chambres,
chacune un `code` unique → URL `/carte/{code}`) · `menu_categories` (↔ `venues`, flag
`room_service`) · `menu_items` (prix, image — **URL externe ou fichier téléversé** sur le
disque `public`, résolu par `MenuItem::imageUrl()` —, storytelling, allergènes, tags, dispo) ·
`menu_option_groups` / `menu_options` (single/multi, requis, suppléments) · `orders` +
`order_items` (snapshots) · `service_requests` (appel serveur / addition) ·
`reservation_charges` (folio : consommations imputées, entrent dans `Reservation::balance()`).

Seed depuis `config/menu.php` (18 articles, 6 catégories) + `config/dining.php`.

### Parcours client — `/carte/{code}` (sans compte)

- Menu filtré selon le point de vente ; room service = catégories `room_service`.
- Panier **Alpine + localStorage** (survit au réseau) ; options/suppléments recalculés
  **côté serveur** (`App\Services\CartPricer`) — le client ne peut pas fausser le prix.
- **Idempotence** : `idempotency_key` unique par commande → double envoi = même commande.
- **Imputation chambre** (§29) : room service sur séjour `checked_in` ⇒ ligne de folio
  automatique ; à table ⇒ n° chambre + nom. Sinon règlement au service.
- « Appeler le serveur » (§27) et « Demander l'addition » (§28) → `service_requests`.
- Suivi commande `/carte/{code}/suivi/{ref}` (timeline, rafraîchissement auto).
- §99 : QR inconnu → 404 dédié ; QR/venue inactif → 410 ; commande impossible si le
  point de vente n'accepte pas le QR (carte en lecture seule).
- `/carte` : carte publique consultable (indexable), liée depuis la page Restaurant.

### Back-office

| Écran | Rôles | Contenu |
|-------|-------|---------|
| **Caisse restaurant** (POS) | restaurant, réception | Écran de vente **tactile 3 zones** (catégories · articles · ticket), voir ci-dessous |
| Cuisine (KDS) | restaurant, réception | Colonnes Nouvelles / En préparation / Prêtes, avance de statut, auto-refresh 15 s (§30). **N'affiche que les articles à préparer** : les catégories `needs_kitchen = false` (boissons, bar) sont masquées ; une commande 100 % boissons ne s'y affiche pas (elle part `served` en POS / `ready` en QR) |
| Commandes | restaurant, réception | Liste filtrable · fiche · encaissement (espèces/carte/mobile) · imputation folio · annulation |
| Service en salle | restaurant, réception | Demandes ouvertes (appel serveur, addition), prise en charge / traité |
| Carte | restaurant, direction | CRUD catégories & articles, options, disponibilité, affectation aux points de vente, **case « Cuisine »** par catégorie (envoi KDS) |
| QR codes | restaurant, direction | Liste par lieu, **QR SVG** (`endroid/qr-code`), feuille d'impression A4 |

### Caisse restaurant (POS) — `/admin/pos`

Écran plein écran **clair et plat** (deux grands volets), pensé pour un caissier / une
tablette tactile :

- **Zone 1 — catégories** du point de restauration sélectionné + recherche instantanée ;
  la catégorie ouverte est cerclée (contour + caret).
- **Zone 2 — articles** en tuiles blanches à filet (photo + nom + prix), grille dense ; un
  article à options ouvre une mini-modale de choix (simple / multiple, requis).
- **Zone 3 — ticket** (panneau blanc) : en-tête « 👤 Pas de client / 🍴 Pas de table »,
  lignes avec vignette + `− quantité +` + total + `n × PU`, ligne **% Remise**, sous-total,
  et un gros bouton **« Payer N produit(s) · TOTAL »**. Type de vente **Restaurant / Bar /
  Piscine / Room service**.
- **Actions rapides** : Nouvelle vente · **Mettre en attente** / **Reprendre** (commandes
  parquées) · **Payer**.
- **Modale de paiement** (le **nom du client** est demandé ici, une fois tous les plats
  ajoutés) : Espèces (montant reçu → **monnaie à rendre**, boutons rapides), Wave, Orange
  Money, Free Money, Carte, **🏨 Chambre** (choix d'une réservation en séjour → imputation
  folio). Après validation : **ticket imprimable** 80 mm.
- Prix **recalculés côté serveur** (`CartPricer::priceForVenue`) — le client ne peut pas
  influer sur le total. La vente crée un `Order` (`source = pos`) qui **part en cuisine (KDS)**
  comme une commande QR ; l'encaissement alimente la trésorerie (`FinanceLedger`, catégorie
  `restaurant`, session de caisse ouverte) ou le folio (`reservation_charges`). Champs POS
  ajoutés à `orders` (`source`, `sale_type`, `table_label`, `discount`, `amount_tendered`,
  `cashier_id`). Tests : `tests/Feature/PosTest.php` (9).

---

## Housekeeping & Maintenance (Phase 7)

### Housekeeping (§33–34)

- `housekeeping_tasks` (unique room+date+type) · `housekeeping_task_checks` (check-list QC) ·
  `housekeeping_incidents` (dégât, objet trouvé, propreté, technique…).
- `App\Services\HousekeepingPlanner::generateForDate()` : crée le plan du jour à partir de
  l'état des chambres et des mouvements (départ → `departure`, séjour en cours → `stayover`,
  chambre `sale` → `departure`). Un check-out crée automatiquement la tâche de recouche.
- Écran **`/admin/menage`** : navigation par date, « Générer le plan », affectation des
  chambres aux agents (rôle `housekeeping`), avance de statut
  (`pending → in_progress → done`), lien vers le contrôle qualité.
- Écran terrain **`/admin/menage/mobile`** (« Ménage terrain ») : les tâches du jour en
  grandes cartes tactiles, bascule *mes chambres / tout l'étage*, boutons pleine largeur
  **Commencer / Terminé / Bloquée**, formulaire d'incident dépliable par chambre (option
  ticket maintenance). Pensé pour un téléphone ou une tablette.
- **Contrôle qualité** `/admin/menage/{task}/controle` : check-list de 12 points → score ;
  ≥ seuil (`config('housekeeping.qc_pass_score')`) ⇒ chambre `propre` et tâche `inspected` ;
  sinon la chambre repart en `sale`. Les statuts de chambre suivent le cycle
  `en_nettoyage → controle → propre`.
- **Incidents** `/admin/menage/incidents` : un incident « technique » (ou sur demande)
  ouvre automatiquement un ticket de maintenance lié.

### Maintenance (§35–36)

- `equipment` (16 seedés : ascenseurs, groupe électrogène, clim, piscine, cuisine, fitness…) ·
  `maintenance_tickets` (correctif / préventif, priorité, technicien, coûts main-d'œuvre + pièces) ·
  `maintenance_plans` (périodicité, check-list, prochaine échéance).
- **Préventif automatique** : `App\Services\PreventiveMaintenance::run()` génère un ticket
  pour chaque plan dont l'échéance est atteinte (sans doublonner tant qu'un ticket est ouvert)
  puis reporte `next_due_on`. Lancé par la commande **`php artisan maintenance:run-plans`**
  (planifiée quotidiennement dans `routes/console.php`) ou le bouton
  « Générer les interventions dues » sur `/admin/maintenance/plans`.
- Écrans : **`/admin/maintenance`** (tickets, KPIs : ouverts, critiques, préventif dû, coût
  du mois), fiche ticket (affectation, statut, coûts, résolution — met à jour l'état de
  l'équipement), **`/admin/maintenance/equipements`**, **`/admin/maintenance/plans`**.
- Nouveau rôle `maintenance` ; `reception` peut créer des tickets et voir les tableaux.

---

## Économat, Finance & Comptabilité (Phase 8)

### Stocks (§37–38)

`suppliers` · `warehouses` · `stock_categories` · `stock_items` (CUMP) · `stock_levels`
(par magasin) · `stock_movements` (registre immuable) · `inventory_counts` (+ lignes).

- `App\Services\StockLedger` : `move()` (entrée/sortie, verrou, refus de stock négatif,
  **coût unitaire moyen pondéré** sur les entrées valorisées), `transfer()` (2 mouvements
  liés par un `transfer_group`), `setLevel()` (ajustement inventaire).
- `/admin/stock` : articles + valeur + **alertes de seuil** · mouvements (saisie manuelle
  entrée/sortie/ajustement) · transferts inter-magasin · inventaires (comptage → clôture
  qui ajuste les stocks) · fournisseurs & magasins.

### Achats (§39) — `/admin/achats`

Workflow sur `purchase_orders` : **Demande** (`draft` → `submitted`) → **Validation**
(`approved`, réservée à la direction) → **Commande** (`ordered`) → **Réception**
(`goods_receipts` → entrées de stock au prix de la ligne + écriture d'achat) →
**Facture** (`supplier_invoices` → règlement via le journal financier).

### Finance & Caisses (§40, §42) — `/admin/finance`

- `finance_accounts` (caisses / banque / mobile), `finance_transactions` (journal
  recettes/dépenses catégorisé), `cash_sessions` (fond → théorique → compté → **écart**).
- `App\Services\FinanceLedger::record()` inscrit chaque opération **et génère l'écriture
  comptable**. Alimenté automatiquement par : paiements de réservation (observer sur
  `Payment`), encaissement de commande QR, règlement fournisseur, saisie manuelle.
- Tableau de bord : trésorerie par compte, résultat de période par catégorie, **créances
  clients** (soldes de réservations) et **dettes fournisseurs**.

### Comptabilité (§41) — `/admin/comptabilite`

`ledger_accounts` (plan SYSCOHADA simplifié), `journal_entries` + `journal_entry_lines`
(toujours Σ débit = Σ crédit), `fiscal_years` (exercices, statut, résultat figé).

- `App\Services\Accounting::post()` — écriture équilibrée rattachée à sa pièce, **refusée si
  sa date tombe dans un exercice clôturé** ; `trialBalance()` ; `ledger()` (solde progressif).
- Journaux VT / AC / CA / BQ / OD / **CL** (clôture) · ventilation dans `config/accounting.php`.
- **États de synthèse** : `incomeStatement()` (compte de résultat, hors journal CL),
  `balanceSheet()` (bilan actif / passif + résultat non affecté, contrôle d'équilibre),
  `vatReturn()` (TVA collectée − déductible → à décaisser / crédit), `generalLedger()`
  (grand livre général, tous comptes, à-nouveau + solde).
- **Exercices comptables** (`/admin/comptabilite/exercices`, direction) : la clôture regroupe
  les comptes de gestion dans `120000 Résultat` (écriture `CL`), fige le résultat et
  **verrouille la période** ; ré-ouverture possible (supprime l'écriture de clôture). Les
  comptes de bilan sont reportés naturellement (grand livre cumulatif — pas d'à-nouveaux).
- **Contre-passation** d'une écriture (miroir débit/crédit, journal OD) ; écritures manuelles
  (contrôle d'équilibre) ; balance générale, grand livre par compte, **export CSV**.
- Tableau de bord `/admin/comptabilite` : exercice en cours + tuiles vers chaque état.

---

## Ressources humaines (Phase 9) — `/admin/rh`

### Dossiers & structure

`departments` (9 services) · `job_positions` (36 fonctions) · `employees` (matricule,
statut, solde de congés, `pin_hash` pour la borne) · `employee_contracts` (CDI/CDD…,
salaire de base, échéances) · `employee_documents` (upload vers `storage/app/hr/…`,
téléchargement contrôlé).

### Planning (§46) — `/admin/rh/planning`

`shift_templates` (Matin / Journée / Après-midi / Nuit) + `shifts`. Grille hebdomadaire
employés × jours, modèle → horaires pré-remplis, **détection de conflits** (chevauchement
même employé même jour signalé en rouge), remplacements, « copier vers la semaine suivante ».

### Pointage (§44) — borne `/pointage` + `/admin/rh/pointage`

`App\Services\AttendanceService` : `clockByPin(matricule, PIN)` enregistre automatiquement
l'entrée puis la sortie ; calcul du **retard** (vs début de shift − tolérance), du **temps
travaillé** (− pause) et des **heures supplémentaires** (vs durée planifiée). Tableau
d'assiduité du jour : présents / absents / retards, pointage & marquage d'absence manuels,
correction a posteriori.

**QR badge** : chaque employé a un QR (SVG, `endroid/qr-code`) encodant `/pointage/{matricule}` —
la borne se pré-remplit à la lecture, le code personnel reste requis. Scanner caméra intégré
à la borne (`html5-qrcode` via CDN) ; planche de badges à imprimer sur `/admin/rh/badges`.

### Congés (§43) — `/admin/rh/conges`

`leave_requests` avec types configurables (`config/hr.php` : payé / maladie / sans solde /
maternité…). `LeaveService` : jours ouvrables, approbation (direction) qui **décrémente le
solde** pour les congés payés, refus, annulation (recrédite).

### Paie (§45) — `/admin/rh/paie`

`salary_components` (BASE, primes, cotisations IPRES/CSS/IRPP à taux éditables, retenues) ·
`employee_salary_components` (paramétrage par salarié) · `salary_advances` · `payroll_runs`
→ `payslips` + `payslip_lines` · `payroll_adjustments` (primes/retenues de période).

`App\Services\PayrollEngine::generate()` : gains fixes + heures supplémentaires (issues du
pointage, majorées) + primes saisies ; retenues en % sur la **base imposable**, retenue
d'absence (jours × taux journalier), remboursement d'avance, retenues saisies → **net**.
Workflow : brouillon → génération → approbation (direction) → **paiement** qui écrit
l'écriture comptable (DR 661 charges de personnel / CR 447 retenues / CR banque net),
une opération de trésorerie et solde les avances. Bulletin imprimable / PDF.

---

## CRM 360°, Fidélité & Marketing (Phase 10) — `/admin/crm`, `/admin/marketing`

Cahier §18–20, §52–57.

### CRM — fiche client 360° (`/admin/clients/{guest}`)

- Identité enrichie : date de naissance, **origine** (`config/crm.php`), **étiquettes**, notes.
- **Consentement marketing** : opt-in / retrait horodaté (`consent_updated_at`), tracé dans
  le journal d'audit (`crm.consent.granted` / `crm.consent.withdrawn`).
- **Historique relation client** : timeline d'interactions (note, appel, e-mail, SMS,
  réclamation, compliment) avec auteur.
- Vue consolidée : séjours, valeur cumulée, nombre de séjours honorés, encart fidélité.

### Fidélité — « Khadija Privilège » (`/admin/crm/fidelite`)

- Paliers **dynamiques** (`config/loyalty.php`, seedés en base) : Découverte / Privilège /
  Prestige, seuil de points + **taux de gain** + avantages par palier.
- `App\Services\LoyaltyProgram` : `enroll` (n° de carte `KP…`), `earn` (1 pt / 1 000 FCFA ×
  taux du palier), `redeem` (remise, minimum paramétrable, 1 pt = 5 FCFA), `adjust` (± manuel).
  Recalcul du palier à chaque transaction.
- **Crédit automatique** : l'observer `Payment::created` crédite les points du titulaire
  inscrit à chaque encaissement de séjour (`accrueLoyalty`).
- Grand livre de points par membre sur la fiche client.

### Segments (`/admin/crm/segments`)

- `App\Services\Segmentation` traduit une `definition` JSON de règles combinables en requête :
  `opted_in`, `min_stays`, `min_spend`, `country`, `tier`, `has_tag`, `never_stayed`,
  `inactive_days`, `birthday_month`.
- Constructeur visuel + **aperçu live** (comptage + échantillon) en AJAX.

### Codes promo (`/admin/crm/codes-promo`)

- Table `promo_codes` (%, montant, fenêtre de validité, plafond d'utilisations).
- `PromoCode::activeMap()` est **fusionnée par-dessus `config('booking.promo_codes')`** :
  un code de campagne est immédiatement utilisable dans le tunnel de réservation.

### Campagnes (`/admin/marketing`)

- E-mail / SMS, ciblage par segment (défaut : tous les opt-in), code promo associé,
  jetons `{prenom}` `{nom}` `{code}`, modèles prêts (`config/marketing.php`), envoi planifiable.
- `App\Services\CampaignDispatcher` : `build` (calcul des destinataires, **opt-in imposé**,
  contact sans adresse → `skipped`, dédoublonné), `send` (marque les destinataires, fige les
  stats). `deliver()` = point d'intégration SMTP / passerelle SMS (journalisé pour l'instant).
- Détail campagne : aperçu personnalisé, table des destinataires, statistiques.

Rôle **`marketing`** ajouté (10ᵉ). CRM/Fidélité : `direction`, `reception`, `marketing`.
Campagnes : `direction`, `marketing`.

---

## Commercial & Événements — MICE (Phase 11) — `/admin/commercial`, `/admin/evenements`

Cahier §21–24 (séminaires, conférences, galas, mariages). Rôle **`commercial`** (11ᵉ) —
compte `commercial@residence-khadija.sn`. Accès : `commercial`, `direction`.

### Pipeline commercial (`/admin/commercial`)

- **Affaires** (`event_leads`) : société, contact, type, dates & pax attendus, budget estimé,
  source, commercial référent. Board Kanban par étape + prévision **pondérée**.
- Étapes contrôlées par `App\Services\SalesPipeline` (`nouveau → qualifie → devis →
  negociation → gagne / perdu`) : transitions illégales refusées, motif obligatoire si perdu,
  **devis accepté requis** pour « gagné ».
- **Activités & tâches** horodatées par affaire (note, appel, e-mail, RDV, tâche à échéance).

### Devis événementiels (`/admin/commercial/devis`)

- Lignes par catégorie (location, restauration, pause, technique, hébergement, divers),
  **ajout rapide** de forfaits restauration (par pax) et de matériel technique
  (`config/events.php`).
- `App\Services\EventQuoteBuilder::recalculate` : sous-total → remise → **TVA** → total →
  **acompte** (part paramétrable). Aperçu live (Alpine) + **devis imprimable / PDF**.
- `accept()` → crée un **événement en option** (créneau + expiration) et passe l'affaire à
  « gagné ».

### Salles & événements (`/admin/evenements`)

- `event_spaces` (dispositions → capacités, tarifs ½ / journée) seedées depuis
  `config/spaces.php`. Édition en place.
- `App\Services\EventBooking` : **anti-double réservation** d'une salle sur un créneau
  (chevauchement `start_A < end_B ET end_A > start_B`, transaction + `lockForUpdate`) ;
  seuls les statuts `option` / `confirme` / `realise` bloquent. `confirm()` revérifie tous
  les créneaux avant de figer.
- Cycle **option → confirmé → réalisé** (ou annulé = salles libérées).
- **Feuille de fonction (BEO)** : lignes horodatées (accueil, restauration, technique,
  logistique) avec responsable.
- **Finance** : encaissement **acompte** puis **solde** via `FinanceLedger` (catégorie
  `evenements` → compte `706300`) + comptabilité équilibrée. **Contrat imprimable**.
- **Calendrier mensuel** des événements + options bientôt expirées.

---

## Paiement en ligne (Phase 12) — `/paiement/…`, `/admin/paiements`

Cahier §25. Abstraction **agnostique du prestataire** — `App\Services\PaymentGateway` avec
un driver **`simulator`** (page de paiement locale, aucun débit réel) ; slots `paydunya` /
`cinetpay` prêts dans `config/payments.php` (clés via `.env`).

### Intentions de paiement (`payment_intents`)

- Cible polymorphe : `reservation_deposit` / `reservation_balance`, `order`,
  `event_deposit` / `event_balance`. Montant par défaut calculé depuis la cible.
- `PaymentGateway::open()` est **idempotent** (réutilise l'intention ouverte non expirée),
  `capture()` est **idempotent** (rejeu webhook / double-clic → un seul règlement),
  `fail()`, `refund()`.
- **Le règlement solde la cible métier** via les mécanismes existants :
  - réservation → `Payment` (`deposit`/`balance`, réf. `PI-…`) → observer → trésorerie +
    fidélité ; passe `pending` → `confirmed` si l'acompte est couvert ;
  - commande QR → `payment_status = paid` + `FinanceLedger` (recette `restaurant`) ;
  - événement → `deposit_paid` / `settled` + `FinanceLedger` (recette `evenements`).
- Remboursement : `Payment` négatif (réservation) ou `FinanceLedger` dépense (commande / événement).

### Parcours client

- **Page de paiement hébergée** `/paiement/{ref}` : montant, moyens (carte, Orange Money,
  Wave, Free Money), bouton payer + « simuler un échec ». Carte `4000 0000 0000 0002` =
  refus forcé. Reçu `/paiement/{ref}/recu`.
- **Réservation** : bouton « Payer l'acompte » sur la page de confirmation
  (`POST /reservation/{ref}/paiement`).
- **Commande QR** : bouton « Payer en ligne » sur la page de suivi.
- **Webhook** `POST /paiement/webhook/{provider}` (CSRF-exempt, idempotent) pour les
  notifications des agrégateurs en production.

### Back-office (`/admin/paiements`)

- Liste filtrable (statut / objet / recherche), détail, **encaissement manuel** (paiement
  hors ligne), **remboursement**, annulation. Boutons « Lien de paiement » sur les fiches
  réservation et événement.
- Accès : `finance`, `direction`, `reception` (liens événement : `commercial`, `direction`).

---

## Décisionnel — BI, KPI & reporting (Phase 13) — `/admin/bi`

Cahier §26–28. Accès : `direction`, `finance`.

### Consolidation (`App\Services\Analytics`)

- KPI transverses : **occupation, ADR, RevPAR, CA hébergement**, F&B (couverts, ticket moyen,
  top ventes), PMS (délai de résa, séjour moyen, taux d'annulation, mix canal), housekeeping
  (taux de complétion, contrôle qualité), maintenance, stock (valeur, ruptures), finance
  (recettes/dépenses/résultat, trésorerie, créances/dettes), CRM (nouveaux membres, taux de
  répétition, passif points), MICE (pipeline, taux de conversion, CA confirmé), paiements
  (taux de succès, part en ligne, remboursements).
- `overview()` compare chaque KPI à sa cible (`config/bi.targets`) → alertes du tableau de bord.

### Instantanés quotidiens (`daily_metrics`)

- `php artisan bi:snapshot [--date=] [--backfill=N]` fige 19 métriques par jour (programmé
  quotidiennement à 01:00). `Analytics::series()` alimente les **mini-graphes SVG** (sans
  librairie) du tableau de bord.
- ⚠️ La recherche d'une métrique existante se fait par `whereDate()` (le cast `date` ferait
  rater la ligne sur SQLite — même piège que HousekeepingPlanner / AttendanceService).

### Rapports (`App\Services\ReportRegistry`)

- 11 rapports exportables **CSV (UTF-8 BOM)** : réservations, occupation quotidienne,
  arrivées, départs, CA par jour, paiements en ligne, créances & dettes, ventes restaurant
  par article, valorisation du stock, membres fidélité, pipeline événementiel.
- **Planification** (`report_schedules`) : quotidien / hebdo (lundi) / mensuel (1er),
  fenêtre glissante, destinataires. `php artisan bi:run-schedules` (07:00) exécute les
  rapports dus → `report_runs` (l'envoi e-mail sera branché en production).

---

## Distribution & Channel Manager (Phase 14) — `/admin/distribution`

Cahier §29–31. Accès : `direction`, `reception`. Connecteur **`simulator`** (aucune API
externe ; les poussées ARI sont journalisées dans `channel_sync_logs`). Un connecteur réel
s'implémente en remplaçant `push*()` / `ingest*()` de `App\Services\ChannelManager`.

### Calendrier ARI (`availability_calendar`)

- Par (catégorie, date) : `rooms_open` (plafond manuel), `min_stay` / `max_stay`, `cta`
  (fermé à l'arrivée), `ctd` (fermé au départ), `stop_sell`.
- `App\Services\Availability` lit ces contraintes : `remaining()` applique le plafond,
  `stayRestrictions()` liste les blocages, `canBook()` les refuse — donc **le tunnel de
  réservation direct respecte le stop-sell et le séjour minimum**.
- Éditeur back-office par plage de dates (stopper / rouvrir / restreindre / réinitialiser).

### Canaux & tarifs

- `channels` : Booking.com, Expedia, Airbnb, Hotelbeds (+ « direct »), `connector`,
  `commission_rate`. `channel_rate_plans` : mapping plan tarifaire → canal + **majoration**
  (0 = parité). `channel_rate_overrides` : prix figé par date. `ChannelManager::priceFor()`
  = override ?? `prix × multiplicateur plan × (1 + majoration)`.

### Réservations entrantes

- `POST /distribution/webhook/{channel}` (CSRF-exempt) ou bouton « simuler une réservation
  OTA ». `ChannelManager::ingestReservation()` est **idempotent** par `(channel,
  external_ref)`, refuse les dates en stop-sell (trace `channel_reservations.status = failed`
  hors transaction), crée un `Guest` + une `Reservation` `confirmed` (`channel = <clé>`),
  calcule `commission_amount`.
- **Commission comptabilisée au check-out** : l'observer `Reservation::updated` appelle
  `postCommissionOnCheckout()` → `FinanceLedger` (dépense `commissions_ota` → compte `622100`)
  une seule fois (`commission_posted`).

### Diffusion sortante

- `php artisan channels:push [--rates]` (programmé toutes les heures) → `pushAvailability()`
  / `pushRates()` sur l'horizon (`config/distribution.push_horizon_days`), une ligne de
  journal par canal, `channels.last_sync_at` mis à jour.
- **Flux iCal public** `GET /calendrier/{slug}.ics` : un `VEVENT` « Occupé » par séjour
  bloquant (aucune donnée client) — à importer dans un extranet OTA géré manuellement.

---

## Application mobile invité — PWA (Phase 15) — `/app`

Cahier §58–60. **Sans compte** : le client se connecte avec sa **référence + son nom**, ou
via un **lien magique signé** (`URL::signedRoute`) remis à la réception (QR « carte
d'accueil » sur la fiche réservation). Jeton opaque de 48 caractères en cookie
(`guest_token`, non chiffré, TTL `config/guestapp.token_ttl_days`), résolu par le middleware
`guest.app` (`App\Http\Middleware\EnsureGuest`).

### Installable & hors-ligne

- `GET /app/manifest.webmanifest` (`application/manifest+json`, `scope=/app`, `display=standalone`).
- `GET /app/sw.js` (`Service-Worker-Allowed: /app`) : cache de l'app-shell à l'installation,
  **network-first** avec repli cache puis page `/app/hors-ligne`.

### Écrans invité (`resources/views/app/*`, layout mobile + barre d'onglets)

- **Accueil** : carte séjour (dates, chambre, statut), solde, demandes en cours, raccourcis
  (room service, ménage, dépannage, Wi-Fi, réception, fidélité), WhatsApp conciergerie.
- **Séjour** : détail + **folio** (hébergement + charges − règlements = solde) et bouton
  **« Régler le solde en ligne »** → `PaymentGateway::open(reservation, 'reservation_balance')`
  → page de paiement hébergée (Phase 12).
- **Demandes** : formulaire (type + note) → `GuestRequest` **routé** automatiquement
  (ménage/linge/articles → housekeeping ; dépannage → maintenance ; sinon réception) ;
  liste avec statut + annulation ; auto-refresh 30 s.
- **Carte** : lien profond vers la carte QR de la chambre.
- **Fidélité** : carte membre (palier, points, valeur, avantages) ou **inscription** en un tap.
- **Wi-Fi** : SSID + mot de passe (`config/guestapp.wifi`).

### Back-office

- **Demandes clients** `/admin/demandes-clients` (`reception`, `housekeeping`, `maintenance`,
  `direction`) : file filtrée par service, « Prendre » / « Traitée ». Nav « Demandes clients ».
- Fiche réservation (confirmée / en séjour) : encart **carte d'accueil** avec le QR du lien
  magique (`admin.reservations.app_qr`).

---

## Design System

`resources/css/app.css` — jeton `@theme` Tailwind v4 :

- **Terre cuite** (`terracotta-*`) : couleur de marque, issue du logo.
- **Nuit** (`nuit-*`) : indigo profond — texte, sections sombres.
- **Laiton / or** (`laiton-*`) : filets, détails.
- **Sable** (`sable-*`) : fonds chauds.
- Titrage **Fraunces** (600), texte **Manrope**.
- **Style plat** : pas d'ombre portée — `--shadow-card` est un filet `0 0 0 1px`, seuls les
  éléments flottants (menus, modales) gardent une ombre discrète ; rayons resserrés
  (`--radius-xl/2xl/3xl`) ; hero sur scrim uni (plus de dégradé) ; cartes et widgets séparés
  par un `border`.
- `prefers-reduced-motion` respecté ; focus visibles ; `.reveal` (apparition au scroll).

Composants Blade : `x-container`, `x-button`, `x-section-heading`, `x-page-hero`,
`x-room-card`, `x-offer-card`, `x-booking-widget`, `x-booking-steps`, `x-booking-summary`,
`x-field`, `x-cta-band`.

---

## Performance & SEO (déjà en place)

- Polices auto-hébergées + `preconnect`, images `loading="lazy"` / `decoding="async"` /
  dimensions explicites, `<link rel="preload">` sur l'image LCP de l'accueil.
- CSS ~13 ko gzip, JS ~19 ko gzip.
- `sitemap.xml` (route), `robots.txt`, canonical, Open Graph / Twitter, `theme-color`,
  `site.webmanifest` (base PWA).
- Données structurées JSON-LD : `Hotel` (layout) et `Restaurant` (page restaurant).
- HTML sémantique, lien d'évitement, `lang` dynamique.

### Restant côté perf/prod (phases ultérieures du cahier)

CDN, cache multicouche + Redis, AVIF, service worker PWA, RUM / monitoring / alerting,
tests de charge & résilience, CI/CD, WAF, secrets manager, sauvegardes & DR.

---

## Tests

```bash
php artisan test            # 264 tests
./vendor/bin/pint           # style de code
```

- `tests/Feature/SiteSmokeTest.php` : rendu de toutes les pages, 404, sitemap, formulaire contact.
- `tests/Feature/BookingFlowTest.php` : tunnel complet, gardes-fous, dates passées, effet code promo.
- `tests/Feature/PmsBackofficeTest.php` : login, RBAC (403), confirm → check-in → check-out,
  statut chambre, anti-surréservation, réservation walk-in, réinitialisation de mot de passe,
  e-mails invité (annulation, remerciement au départ, relance pré-arrivée non dupliquée).
- `tests/Feature/RoomsManagementTest.php` : création à l'unité / en série (avec saut des
  numéros existants), renommage, désactivation (→ hors service, retirée du stock vendable),
  refus de désactiver une chambre occupée, refus de supprimer une chambre liée à des
  réservations, suppression d'une chambre inutilisée, RBAC.
- `tests/Feature/AdminNavTest.php` : un seul lien de menu actif à la fois sur les pages
  autrefois ambiguës (chambres, RH, finance, CRM, événements), lien actif = le plus
  spécifique, absence d'icônes emoji dans la barre latérale.
- `tests/Feature/ContactMessagesTest.php` : le formulaire de contact persiste le message +
  met en file l'e-mail de notification, consentement obligatoire, boîte `/admin/messages`
  (lecture → « lu », marquer traité / rouvrir), RBAC + page 403 personnalisée.
- `tests/Feature/NotificationsTest.php` : `Notify::roles` cible les bons rôles (+ admin),
  ignore les comptes inactifs ; cloche du bandeau (compteur, « tout marquer lu »), ouverture
  d'une notification (marquage lu + redirection vers son URL) ; un message de contact notifie
  la réception ; la page est ouverte à tout le personnel.
- `tests/Feature/MiscCoverageTest.php` : Paramètres (liste catégories / plans tarifaires,
  édition prix-capacité-featured, multiplicateur, RBAC direction), sitemap XML public, 404 aux
  couleurs de l'hôtel.
- `tests/Feature/QrOrderingTest.php` : résolution QR (table/chambre/inconnu/inactif), commande +
  prix serveur, idempotence, imputation folio, appel serveur / addition, cycle KDS, RBAC.
- `tests/Feature/OperationsTest.php` : plan de ménage, tâche auto au check-out, **écran ménage
  mobile** (tâches du jour, filtre « mes chambres », avance de statut au doigt), contrôle qualité
  (pass/fail), incident → ticket maintenance, préventif (génération + idempotence + coûts + état
  équipement), RBAC, commande artisan.
- `tests/Feature/RoomCategoryAdminTest.php` : création d'une catégorie (slug unique auto,
  équipements en liste, photo téléversée) visible aussitôt sur la vitrine, mise à jour +
  retrait de photo, suppression bloquée si chambres liées / autorisée si vide, RBAC direction.
- `tests/Feature/MenuPhotoTest.php` : téléversement d'une photo de plat (disque `public`,
  `imageUrl()` → `/storage/menu/…`), collage d'URL toujours accepté, remplacement qui supprime
  l'ancien fichier.
- `tests/Feature/SiteSettingsTest.php` : tous les groupes affichés, mise à jour du nom d'hôtel
  répercutée dans `config()` et sur la vitrine, cast numérique (`min_nights` → int, taux → float),
  refus des valeurs invalides sans rien enregistrer, RBAC direction.
- `tests/Feature/EconomyTest.php` : CUMP, refus stock négatif, transfert, inventaire, workflow
  achat + écriture comptable, règlement fournisseur, journal financier, observer paiement,
  écart de caisse, contrôle d'équilibre des écritures, export CSV, RBAC.
- `tests/Feature/AccountingTest.php` : compte de résultat (produits − charges), bilan qui
  s'équilibre, déclaration de TVA (collectée − déductible), clôture d'exercice (regroupement
  des comptes de gestion + verrouillage de la période — `post()` refuse), ré-ouverture,
  contre-passation (miroir équilibré), formulaire de saisie refusant une date verrouillée, RBAC
  (états = finance/direction, clôture = direction).
- `tests/Feature/WorkforceTest.php` : pointage borne (retard / HS), congés (solde), conflit de
  planning, génération de paie + math + passation comptable + soldes d'avances, RBAC.
- `tests/Feature/CrmTest.php` : crédit de points à l'encaissement, upgrade de palier, rachat
  (minimum + solde insuffisant), segmentation (séjours / jamais venu / anniversaire),
  campagne (opt-in imposé, adresse manquante, anti-doublon, envoi + stats + **`CampaignMessage`
  mis en file**), rendu des jetons, code promo base appliqué / ignoré dans le devis, RBAC,
  inscription + consentement.
- `tests/Feature/EventsTest.php` : anti-double réservation de salle (chevauchement /
  hors chevauchement / annulation qui libère / revérif à la confirmation), calcul de devis
  (lignes + TVA + acompte), acceptation → événement en option + affaire gagnée, transitions
  de pipeline (illégales, motif de perte, devis requis pour gagné), acompte → trésorerie +
  comptabilité équilibrée, **envoi du devis par e-mail** (`EventQuoteSent` + `sent_at`, ou
  simple bascule de statut si le contact n'a pas d'adresse), RBAC, rendu devis / contrat
  imprimables.
- `tests/Feature/PoolTest.php` : réservation piscine tarifée au créneau, anti-double
  réservation (journée bloque une demi-journée, matinée + après-midi cohabitent, annulation
  qui libère), garde de capacité, création + avance de statut via l'écran, RBAC, ajout d'un
  emplacement au parc.
- `tests/Feature/MeetingRoomsTest.php` : le planning `/admin/salles` affiche une réservation
  de salle dans la grille 7 jours, RBAC (commercial / direction / réception).
- `tests/Feature/PaymentsTest.php` : ouverture d'intention idempotente, capture qui solde &
  confirme la réservation, capture idempotente (rejeu webhook), échec sans paiement,
  remboursement, commande QR payée → recette, acompte événement → comptabilité équilibrée,
  **reçu par e-mail au payeur** (repli sur l'e-mail du client), page hébergée (rendu puis
  redirection une fois payé), route `process` (paie / échoue), webhook idempotent + **rejet
  de signature invalide / acceptation d'une signature valide**, carte de refus du simulateur,
  RBAC, montant par défaut du solde.
- `tests/Feature/BiTest.php` : occupation reflète un séjour ajouté, `bi:snapshot` écrit les
  19 métriques + `series()` les relit, rapport « réservations » liste la période + export CSV,
  rapport « CA par jour » somme les recettes, commandes `bi:snapshot` / `bi:run-schedules`
  (exécution un lundi → `report_run` + **CSV envoyé aux destinataires**), RBAC, export CSV en flux.
- `tests/Feature/DistributionTest.php` : stop-sell / séjour minimum / plafond `rooms_open`
  bloquent la résa, `ingestReservation` crée un séjour `confirmed` + commission et est
  idempotent, refus sur stop-sell (trace `failed`), commission comptabilisée au check-out +
  balance équilibrée, `pushAvailability` journalise par canal, `channels:push`, flux iCal
  (`VEVENT` « Occupé »), webhook idempotent + **rejet / acceptation de signature HMAC**, RBAC.
- `tests/Feature/PosTest.php` : vente espèces (recette + monnaie), méthode mobile, imputation
  folio, remise plafonnée, article indisponible, mise en attente + reprise en place, annulation,
  ticket, RBAC, **filtre cuisine** (boissons hors KDS, commande mixte = plats seuls au KDS).
- `tests/Feature/GuestAppTest.php` : connexion référence + nom (nom erroné rejeté, séjour
  clôturé refusé), jeton requis, lien magique signé (non signé → 403), ouverture d'un paiement
  de solde, création + routage + annulation d'une demande, inscription fidélité, manifest & SW
  servis, RBAC + actions back-office.

---

## Prochaines étapes suggérées

1. E-mail transactionnel (confirmation réservation, ticket de commande) en queue +
   paiement en ligne (idempotence, callbacks) pour le booking et le QR.
2. Phase 8 : stocks + achats + finance + comptabilité (le stock restaurant se branche
   sur la consommation des commandes ; le folio et les paiements alimentent la finance).
3. Front Office : calendrier/planning des chambres (vue Gantt), overbooking contrôlé.
4. Notifications temps réel KDS / service (websockets) plutôt que rafraîchissement.
5. Intégrer le logo vectoriel officiel + vraies photos (CDN) ; vraies coordonnées.
6. Ajouter EN puis Wolof / Arabe (RTL) — la structure i18n est prête.
7. Analytics + consentement + événements (scan QR, commande QR, clic WhatsApp, réservation).
