# API - Recherche de Destinataires Utilisateurs

## Vue d'ensemble

Cette fonctionnalité permet de rechercher des utilisateurs existants dans la base de données pour les utiliser comme destinataires lors de la création d'un colis. Cela améliore l'expérience utilisateur en évitant la saisie manuelle des informations.

## Endpoint de recherche

### Rechercher des destinataires utilisateurs

**URL :** `GET /api/client/colis/search-destinataires`

**Paramètres :**
- `query` (requis) : Terme de recherche (minimum 2 caractères)

**Headers :**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Exemple de requête :**
```http
GET /api/client/colis/search-destinataires?query=marie
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Réponse réussie (200) :**
```json
{
    "success": true,
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

**Réponse d'erreur (422) :**
```json
{
    "message": "The query field is required.",
    "errors": {
        "query": ["The query field is required."]
    }
}
```

## Utilisation dans la création de colis

### Créer un colis avec un destinataire utilisateur

**URL :** `POST /api/client/colis`

**Body :**
```json
{
    "destinataire_id": 123,
    "adresse_destinataire": "123 Rue de la Paix, Abidjan",
    "description": "Livre technique",
    "poids": 2.5,
    "adresse_enlevement": "456 Avenue des Arts, Abidjan",
    "lat_enlevement": 5.3600,
    "lng_enlevement": -4.0083,
    "lat_livraison": 5.3700,
    "lng_livraison": -4.0183,
    "enlevement_domicile": true,
    "livraison_express": false,
    "paiement_livraison": false
}
```

**Réponse :**
```json
{
    "success": true,
    "message": "Colis créé avec succès",
    "colis": {
        "id": 1,
        "code_suivi": "TS202501150001",
        "expediteur_id": 1,
        "destinataire_id": 123,
        "destinataire_nom": null,
        "destinataire_telephone": null,
        "adresse_destinataire": "123 Rue de la Paix, Abidjan",
        "description": "Livre technique",
        "poids": "2.50",
        "prix_total": "2250.00",
        "status": "en_attente",
        "destinataire": {
            "id": 123,
            "nom": "Martin",
            "prenoms": "Marie",
            "telephone": "+2250123456789",
            "email": "marie.martin@email.com"
        }
    },
    "tarification": {
        "prix_total": 2250,
        "commission_livreur": 450,
        "commission_agence": 225
    }
}
```

### Créer un colis avec un destinataire externe

**Body :**
```json
{
    "destinataire_nom": "Pierre Durand",
    "destinataire_telephone": "+2250987654321",
    "adresse_destinataire": "789 Boulevard du Commerce, Yamoussoukro",
    "description": "Documents importants",
    "poids": 1.2,
    "adresse_enlevement": "456 Avenue des Arts, Abidjan",
    "lat_enlevement": 5.3600,
    "lng_enlevement": -4.0083,
    "lat_livraison": 6.8167,
    "lng_livraison": -5.2833,
    "enlevement_domicile": false,
    "livraison_express": true,
    "paiement_livraison": true
}
```

## Logique de validation

### Règles de validation conditionnelles

1. **Si `destinataire_id` est fourni :**
   - `destinataire_nom` et `destinataire_telephone` sont optionnels
   - Les informations viennent automatiquement de l'utilisateur

2. **Si `destinataire_id` n'est pas fourni :**
   - `destinataire_nom` et `destinataire_telephone` sont obligatoires
   - Les informations sont saisies manuellement

### Exemple de validation

```php
// Validation conditionnelle selon le type de destinataire
if ($request->destinataire_id) {
    // Destinataire utilisateur : les champs nom et téléphone ne sont pas nécessaires
    $request->validate([
        'destinataire_nom' => 'nullable',
        'destinataire_telephone' => 'nullable',
    ]);
} else {
    // Destinataire externe : les champs nom et téléphone sont obligatoires
    $request->validate([
        'destinataire_nom' => 'required|string|max:255',
        'destinataire_telephone' => 'required|string',
    ]);
}
```

## Avantages de cette approche

### 1. **Flexibilité**
- Possibilité de choisir entre utilisateur existant ou destinataire externe
- Pas de contrainte sur le type de destinataire

### 2. **Expérience utilisateur améliorée**
- Recherche en temps réel des utilisateurs
- Auto-complétion des informations
- Réduction des erreurs de saisie

### 3. **Données cohérentes**
- Informations utilisateur toujours à jour
- Pas de duplication de données
- Traçabilité complète

### 4. **Sécurité**
- Validation des utilisateurs existants
- Contrôle d'accès approprié
- Données fiables

## Cas d'usage

### Scénario 1 : Envoi à un ami utilisateur
1. L'utilisateur tape le nom de son ami dans la recherche
2. L'API retourne les résultats correspondants
3. L'utilisateur sélectionne son ami
4. Les informations sont automatiquement remplies
5. L'utilisateur saisit seulement l'adresse de livraison

### Scénario 2 : Envoi à un destinataire externe
1. L'utilisateur saisit manuellement le nom et téléphone
2. L'utilisateur saisit l'adresse de livraison
3. Le colis est créé avec les informations externes

### Scénario 3 : Recherche par téléphone
1. L'utilisateur tape le numéro de téléphone
2. L'API trouve l'utilisateur correspondant
3. L'utilisateur confirme la sélection
4. Les informations sont automatiquement remplies

## Intégration frontend

### Exemple avec JavaScript

```javascript
// Recherche de destinataires
async function searchDestinataires(query) {
    try {
        const response = await fetch(`/api/client/colis/search-destinataires?query=${query}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayDestinataires(data.destinataires);
        }
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
    }
}

// Affichage des résultats
function displayDestinataires(destinataires) {
    const container = document.getElementById('destinataires-results');
    container.innerHTML = '';
    
    destinataires.forEach(destinataire => {
        const div = document.createElement('div');
        div.className = 'destinataire-item';
        div.innerHTML = `
            <strong>${destinataire.nom} ${destinataire.prenoms}</strong><br>
            <small>${destinataire.telephone} - ${destinataire.email}</small>
            <button onclick="selectDestinataire(${destinataire.id})">Sélectionner</button>
        `;
        container.appendChild(div);
    });
}

// Sélection d'un destinataire
function selectDestinataire(destinataireId) {
    document.getElementById('destinataire_id').value = destinataireId;
    document.getElementById('destinataire_nom').value = '';
    document.getElementById('destinataire_telephone').value = '';
    document.getElementById('destinataire_nom').disabled = true;
    document.getElementById('destinataire_telephone').disabled = true;
}
```

## Gestion des erreurs

### Erreurs courantes

1. **Utilisateur non trouvé**
   - Retourne une liste vide
   - L'utilisateur peut saisir manuellement

2. **Requête trop courte**
   - Validation côté serveur
   - Message d'erreur explicite

3. **Token expiré**
   - Redirection vers la connexion
   - Message d'erreur approprié

### Messages d'erreur

```json
{
    "success": false,
    "message": "Le terme de recherche doit contenir au moins 2 caractères"
}
```

Cette fonctionnalité offre une expérience utilisateur fluide et intuitive pour la gestion des destinataires dans l'application de livraison. 