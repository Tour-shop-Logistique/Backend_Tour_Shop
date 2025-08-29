# TourShop Backend (Laravel 10)

Backend API pour la plateforme logistique Tour Shop Logistique SARL.
Ce projet expose des APIs pour 4 applications: Client, Agence, Livreur et Back‑office. Authentification via Laravel Sanctum.

## Stack
- Laravel 10 (PHP 8.1+)
- Sanctum (API tokens)
- MySQL/PostgreSQL
- MVC + Services + Enums

## Prérequis
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Node.js (pour Vite/asset build si nécessaire)

## Installation
1) Cloner le repo et installer dépendances
```bash
composer install
npm install
```

2) Configuration d'environnement
- Copier `.env.example` vers `.env` puis configurer:
```
APP_NAME="TourShop"
APP_URL=http://localhost
APP_ENV=local
APP_KEY= # sera générée

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tourshop
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

3) Générer la clé d'application et migrations
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed  # optionnel, si des seeders existent
php artisan storage:link  # pour l'accès public aux uploads
```

4) Lancer le serveur de dev
```bash
php artisan serve
```

5) Build des assets (si nécessaire pour les vues)
```bash
npm run dev
```

## Authentification
- Les endpoints sont protégés par Sanctum. Récupérez un token avec `/api/login`, puis incluez:
```
Authorization: Bearer {token}
Accept: application/json
```

## Structure des dossiers (extrait)
- `app/Enums/ColisStatus.php` — Enum des statuts colis
- `app/Http/Controllers/Api/` — Contrôleurs API
  - `AuthController.php`
  - `Client/ColisController.php`
  - `Client/TarificationController.php`
  - `Livreur/MissionController.php`
  - `Agence/AgenceController.php`
- `app/Models/` — Modèles Eloquent (`User`, `Colis`, `Agence`, ...)
- `routes/api.php` — Déclaration des routes API

## API Endpoints

### Authentification
- POST `/api/register` — Inscription (Client/Livreur/Agence/Admin)
- POST `/api/login` — Connexion
- POST `/api/logout` — Déconnexion
- GET `/api/user` — Profil de l'utilisateur connecté

### Client
- GET `/api/client/colis` — Lister les colis du client
- POST `/api/client/colis` — Créer une demande de colis
- GET `/api/client/colis/{id}` — Détails
- POST `/api/client/colis/{id}/annuler` — Annuler une demande
- GET `/api/client/suivre/{codesuivi}` — Suivi par code
- GET `/api/client/colis/search-destinataires` — Recherche destinataires
- POST `/api/client/tarification/simuler` — Simulation de tarif
- GET `/api/client/agences-proches` — Agences proches

### Livreur
- GET `/api/livreur/dashboard` — Dashboard
- GET `/api/livreur/missions-disponibles` — Missions disponibles
- POST `/api/livreur/missions/{colis}/accepter` — Accepter une mission
- GET `/api/livreur/mes-missions` — Mes missions
- POST `/api/livreur/missions/{colis}/confirmer-enlevement`
- POST `/api/livreur/missions/{colis}/confirmer-livraison`
- POST `/api/livreur/disponibilite` — Changer la disponibilité

### Agence (Nouveaux endpoints)
Contrôleur: `App\Http\Controllers\Api\Agence\AgenceController`

- GET `/api/agence/show` — Infos agence connectée
- PUT `/api/agence/update` — Modifier infos agence
- GET `/api/agence/expeditions` — Lister les expéditions de l'agence
  - Filtres: `status`, `from` (YYYY-MM-DD), `to` (YYYY-MM-DD)
- POST `/api/agence/expeditions/{colis}/accepter` — Accepter une demande (statut -> `valide`)
- POST `/api/agence/expeditions/{colis}/refuser` — Refuser une demande (statut -> `annule`)
  - Body: `{ "motif": "string" }`
- POST `/api/agence/expeditions/{colis}/assign-livreur` — Assigner un livreur
  - Body: `{ "livreur_id": "uuid" }`
- POST `/api/agence/expeditions/{colis}/statut` — Changer le statut
  - Body: `{ "status": "en_enlevement|recupere|en_transit|en_agence|en_livraison|livre" }`
- POST `/api/agence/expeditions/{colis}/preuves` — Ajouter des preuves (form-data)
  - Files: `photo_livraison` (image), `signature_destinataire` (image)
- POST `/api/agence/expeditions/{colis}/verifier` — Vérification entrepôt
  - Body: `{ "poids": number, "prix_total": number }`

Notes:
- Le champ `motif` de refus est validé mais pas encore persisté; il sera ajouté via migration.
- Les fichiers sont stockés sur le disque `public` (`storage/app/public`), accessibles via `storage` après `php artisan storage:link`.

### Tarifs
- GET `/api/tarifs/index`
- POST `/api/tarifs/store`
- GET `/api/tarifs/show/{tarif}`
- PUT `/api/tarifs/update/{tarif}`
- DELETE `/api/tarifs/destroy/{tarif}`

## Workflow Agence (simplifié)
1) Demande en attente (`en_attente`) -> Agence accepte (`valide`) ou refuse (`annule`)
2) Collecte: `en_enlevement` -> `recupere`
3) Arrivée agence: `en_agence` -> Vérification & ajustement tarif
4) Transit: `en_transit`
5) Livraison: `en_livraison` -> `livre` (avec preuves)

Remarque: l'enum `ColisStatus` sera étendue plus tard pour couvrir des étapes supplémentaires (ex: "enregistré", "arrivé entrepôt étranger").

## Tests
- Exécuter les tests
```bash
php artisan test
```

## Roadmap (prochaines étapes)
- Migrations complémentaires: `refuse_reason`, facturation (numéro facture, statut paiement), preuves (scan), `validated_at`.
- Rôles/Policies Agence et Back‑office.
- Événements & Notifications (acceptation, refus, changements de statut).
- Chat (conversations/messages) et statistiques.

## Dépannage
- Vérifiez les permissions d'écriture sur `storage/` et `bootstrap/cache/`.
- Assurez-vous d'avoir exécuté `php artisan storage:link` pour servir les images.

## Licence
MIT
