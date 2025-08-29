# API Colis - Exemples d'utilisation

## Créer un colis avec un destinataire utilisateur

### Requête
```http
POST /api/client/colis
Authorization: Bearer {token}
Content-Type: application/json

{
    "destinataire_id": 123,
    "adresse_destinataire": "123 Rue de la Paix, Abidjan",
    "description": "Livre technique",
    "poids": 2.5,
    "valeur_declaree": 15000,
    "adresse_enlevement": "456 Avenue des Arts, Abidjan",
    "lat_enlevement": 5.3600,
    "lng_enlevement": -4.0083,
    "lat_livraison": 5.3700,
    "lng_livraison": -4.0183,
    "enlevement_domicile": true,
    "livraison_express": false,
    "paiement_livraison": false,
    "instructions_enlevement": "Appeler avant de venir",
    "instructions_livraison": "Livrer entre 9h et 17h"
}
```

### Réponse
```json
{
    "message": "Colis créé avec succès",
    "colis": {
        "id": 1,
        "code_suivi": "COLABC12345",
        "expediteur_id": 1,
        "destinataire_id": 123,
        "destinataire_nom": null,
        "destinataire_telephone": null,
        "adresse_destinataire": "123 Rue de la Paix, Abidjan",
        "description": "Livre technique",
        "poids": "2.50",
        "prix_total": "2250.00",
        "status": "en_attente",
        "expediteur": {
            "id": 1,
            "nom": "Dupont",
            "prenoms": "Jean"
        },
        "destinataire": {
            "id": 123,
            "nom": "Martin",
            "prenoms": "Marie",
            "telephone": "+2250123456789"
        }
    }
}
```

## Créer un colis avec un destinataire externe

### Requête
```http
POST /api/client/colis
Authorization: Bearer {token}
Content-Type: application/json

{
    "destinataire_nom": "Pierre Durand",
    "destinataire_telephone": "+2250987654321",
    "adresse_destinataire": "789 Boulevard du Commerce, Yamoussoukro",
    "description": "Documents importants",
    "poids": 1.2,
    "valeur_declaree": 50000,
    "adresse_enlevement": "456 Avenue des Arts, Abidjan",
    "lat_enlevement": 5.3600,
    "lng_enlevement": -4.0083,
    "lat_livraison": 6.8167,
    "lng_livraison": -5.2833,
    "enlevement_domicile": false,
    "livraison_express": true,
    "paiement_livraison": true,
    "instructions_enlevement": "Récupérer au bureau",
    "instructions_livraison": "Remettre en main propre uniquement"
}
```

### Réponse
```json
{
    "message": "Colis créé avec succès",
    "colis": {
        "id": 2,
        "code_suivi": "COLDEF67890",
        "expediteur_id": 1,
        "destinataire_id": null,
        "destinataire_nom": "Pierre Durand",
        "destinataire_telephone": "+2250987654321",
        "adresse_destinataire": "789 Boulevard du Commerce, Yamoussoukro",
        "description": "Documents importants",
        "poids": "1.20",
        "prix_total": "3600.00",
        "status": "en_attente",
        "expediteur": {
            "id": 1,
            "nom": "Dupont",
            "prenoms": "Jean"
        },
        "destinataire": null
    }
}
```

## Rechercher des destinataires utilisateurs

### Requête
```http
GET /api/client/colis/search-destinataires?query=marie
Authorization: Bearer {token}
```

### Réponse
```json
{
    "destinataires": [
        {
            "id": 123,
            "nom": "Martin",
            "prenoms": "Marie",
            "telephone": "+2250123456789",
            "email": "marie.martin@email.com"
        },
        {
            "id": 456,
            "nom": "Marie",
            "prenoms": "Sophie",
            "telephone": "+2250123456790",
            "email": "sophie.marie@email.com"
        }
    ]
}
```

## Lister les colis de l'utilisateur

### Requête
```http
GET /api/client/colis
Authorization: Bearer {token}
```

### Réponse
```json
{
    "colis": {
        "data": [
            {
                "id": 1,
                "code_suivi": "COLABC12345",
                "expediteur_id": 1,
                "destinataire_id": 123,
                "status": "en_attente",
                "prix_total": "2250.00",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "expediteur": {
                    "id": 1,
                    "nom": "Dupont",
                    "prenoms": "Jean"
                },
                "destinataire": {
                    "id": 123,
                    "nom": "Martin",
                    "prenoms": "Marie"
                }
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

## Afficher un colis spécifique

### Requête
```http
GET /api/client/colis/1
Authorization: Bearer {token}
```

### Réponse
```json
{
    "colis": {
        "id": 1,
        "code_suivi": "COLABC12345",
        "expediteur_id": 1,
        "destinataire_id": 123,
        "destinataire_nom": "Martin Marie",
        "destinataire_telephone": "+2250123456789",
        "adresse_destinataire": "123 Rue de la Paix, Abidjan",
        "description": "Livre technique",
        "poids": "2.50",
        "valeur_declaree": "15000.00",
        "prix_total": "2250.00",
        "status": "en_attente",
        "enlevement_domicile": true,
        "livraison_express": false,
        "paiement_livraison": false,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "expediteur": {
            "id": 1,
            "nom": "Dupont",
            "prenoms": "Jean"
        },
        "destinataire": {
            "id": 123,
            "nom": "Martin",
            "prenoms": "Marie",
            "telephone": "+2250123456789"
        },
        "agence": null,
        "livreur": null
    }
}
```

## Avantages de cette approche

1. **Flexibilité** : Les utilisateurs peuvent envoyer des colis à d'autres utilisateurs ou à des destinataires externes
2. **Expérience utilisateur améliorée** : Les utilisateurs existants peuvent être recherchés et sélectionnés facilement
3. **Données cohérentes** : Les informations des utilisateurs sont automatiquement récupérées
4. **Sécurité** : Seuls les utilisateurs concernés peuvent voir leurs colis
5. **Traçabilité** : Possibilité de notifier les utilisateurs destinataires des mises à jour de leurs colis

## Utilisation des accesseurs

Le modèle `Colis` utilise des accesseurs pour gérer automatiquement l'affichage des informations du destinataire :

- Si `destinataire_id` est défini, les informations viennent de l'utilisateur
- Sinon, les informations viennent des champs `destinataire_nom` et `destinataire_telephone`

```php
// Exemple d'utilisation
$colis = Colis::find(1);
echo $colis->destinataire_nom; // Affiche le nom de l'utilisateur ou le nom externe
echo $colis->destinataire_telephone; // Affiche le téléphone de l'utilisateur ou le téléphone externe
echo $colis->isDestinataireUser(); // true si c'est un utilisateur, false si externe
``` 