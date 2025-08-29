<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Agence;
use App\Models\Colis;
use App\Models\User;
use App\Enums\UserType;
use App\Enums\ColisStatus;
use Illuminate\Support\Facades\Log;
use Exception; // Importer la classe Exception pour la gestion des erreurs
use Illuminate\Support\Facades\Storage;

class AgenceController extends Controller
{

    /**
     * Enregistre les informations d'agence pour un utilisateur de type 'agence'.
     * L'utilisateur doit être authentifié et de type 'agence'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setupAgence(Request $request)
    {
        try {
            $user = $request->user();

            // Vérifie si l'utilisateur authentifié est bien de type 'agence'
            if ($user->type !== UserType::AGENCE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seuls les utilisateurs de type agence peuvent configurer une agence.'
                ], 403);
            }

            // Vérifie si l'utilisateur a déjà une agence configurée
            $existingAgence = Agence::where('user_id', $user->id)->first();
            if ($existingAgence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une agence est déjà configurée pour cet utilisateur.'
                ], 422);
            }

            // Valide les données d'entrée de la requête
            $request->validate([
                'nom_agence' => ['required', 'string', 'max:255'],
                'telephone' => ['required', 'string', 'max:20'],
                'description' => ['nullable', 'string', 'max:1000'],
                'adresse' => ['required', 'string', 'max:255'],
                'ville' => ['required', 'string', 'max:255'],
                'commune' => ['required', 'string', 'max:255'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'zone_couverture_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
                'horaires' => ['nullable', 'array'],
                'horaires.*.jour' => ['required_with:horaires', 'string', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche'],
                'horaires.*.ouverture' => ['required_with:horaires', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
                'horaires.*.fermeture' => ['required_with:horaires', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            ]);

            // Crée l'agence
            $agence = Agence::create([
                'user_id' => $user->id,
                'nom_agence' => $request->nom_agence,
                'telephone' => $request->telephone,
                'description' => $request->description,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'commune' => $request->commune,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'horaires' => $request->horaires ?? [],
                'zone_couverture_km' => $request->zone_couverture_km ?? 10.00,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agence configurée avec succès.',
                'agence' => $agence->toArray()
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Erreur lors de la configuration de l\'agence : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la configuration de l\'agence.'
            ], 500);
        }
    }

    /**
     * Vérifie le statut de configuration d'agence pour l'utilisateur authentifié.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAgenceStatus(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->type !== UserType::AGENCE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé.'
                ], 403);
            }

            $agence = Agence::where('user_id', $user->id)->first();

            return response()->json([
                'success' => true,
                'has_agence' => !is_null($agence),
                'agence' => $agence ? $agence->toArray() : null
            ]);
        } catch (Exception $e) {
            Log::error('Erreur lors de la vérification du statut agence : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut.'
            ], 500);
        }
    }

    /**
     * Affiche les informations de l'agence associée à l'utilisateur authentifié.
     * Accessible uniquement par un utilisateur de type 'agence'.
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            // Vérifie si l'utilisateur authentifié est bien de type 'agence'
            if ($user->type !== UserType::AGENCE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seules les agences peuvent consulter leur profil.'
                ], 403); // Statut HTTP 403 Forbidden
            }

            // Récupère l'agence liée à l'ID de l'utilisateur
            $agence = Agence::where('user_id', $user->id)->first();

            // Si aucune agence n'est trouvée pour cet utilisateur
            if (!$agence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil d\'agence introuvable pour cet utilisateur.'
                ], 404); // Statut HTTP 404 Not Found
            }

            // Retourne les informations de l'agence
            return response()->json([
                'success' => true,
                'agence' => $agence->toArray() // Convertit le modèle en tableau pour la réponse JSON
            ]);
        } catch (Exception $e) {
            // Log l'erreur pour le débogage et retourne une réponse générique
            Log::error('Erreur lors de la récupération du profil agence : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la récupération du profil agence. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500); // Statut HTTP 500 Internal Server Error
        }
    }

    /**
     * Met à jour les informations du profil de l'agence associée à l'utilisateur authentifié.
     * Accessible uniquement par un utilisateur de type 'agence'.
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            // Vérifie si l'utilisateur authentifié est bien de type 'agence'
            if ($user->type !== UserType::AGENCE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seules les agences peuvent modifier leur profil.'
                ], 403);
            }

            // Récupère l'agence liée à l'ID de l'utilisateur
            $agence = Agence::where('user_id', $user->id)->first();

            // Si aucune agence n'est trouvée pour cet utilisateur
            if (!$agence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil d\'agence introuvable pour cet utilisateur.'
                ], 404);
            }

            // Valide les données d'entrée de la requête pour la mise à jour
            $request->validate([
                'nom_agence' => ['sometimes', 'string', 'max:255'], // 'sometimes' : le champ n'est validé que s'il est présent
                'telephone' => ['sometimes', 'string', 'max:20'],
                'description' => ['sometimes', 'string', 'max:1000'],
                'adresse' => ['sometimes', 'string', 'max:255'],
                'ville' => ['sometimes', 'string', 'max:255'],
                'commune' => ['sometimes', 'string', 'max:255'],
                'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
                'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
                'horaires' => ['sometimes', 'array'], // Les horaires peuvent être un tableau d'objets JSON
                'horaires.*.jour' => ['required_with:horaires', 'string'],
                'horaires.*.ouverture' => ['required_with:horaires', 'string'],
                'horaires.*.fermeture' => ['required_with:horaires', 'string'],
                'zone_couverture_km' => ['sometimes', 'numeric', 'min:0'],
            ]);

            // Met à jour l'agence avec les données validées
            $agence->update($request->all());

            // Retourne une réponse de succès avec les informations de l'agence mises à jour
            return response()->json([
                'success' => true,
                'message' => 'Profil de l\'agence mis à jour avec succès.',
                'agence' => $agence->toArray()
            ]);
        } catch (ValidationException $e) {
            // Capture les erreurs de validation et retourne une réponse 422
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données de l\'agence.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            // Log l'erreur et retourne une réponse générique pour les erreurs inattendues
            Log::error('Erreur lors de la mise à jour du profil agence : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la mise à jour du profil agence. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500);
        }
    }

    // ================= Ajouts pour l'application Agence =================

    /**
     * Liste des colis opérés par l'agence (avec filtres simples).
     */
    public function colis(Request $request)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $agence = Agence::where('user_id', $user->id)->first();
            if (!$agence) {
                return response()->json(['success' => false, 'message' => 'Agence introuvable.'], 404);
            }

            $query = Colis::query()->where('agence_id', $agence->id);
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->get('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->get('to'));
            }

            $colis = $query->latest()->paginate(20);
            return response()->json(['success' => true, 'data' => $colis]);
        } catch (Exception $e) {
            Log::error('Erreur liste colis agence : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Accepter une demande de colis (statut -> VALIDE) et rattacher à l'agence.
     */
    public function accepter(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $agence = Agence::where('user_id', $user->id)->first();
            if (!$agence) {
                return response()->json(['success' => false, 'message' => 'Agence introuvable.'], 404);
            }

            if ($colis->status !== ColisStatus::EN_ATTENTE && $colis->status !== ColisStatus::VALIDE) {
                return response()->json(['success' => false, 'message' => 'Ce colis ne peut pas être accepté dans son état actuel.'], 422);
            }

            $colis->agence_id = $agence->id;
            $colis->status = ColisStatus::VALIDE;
            $colis->save();

            return response()->json(['success' => true, 'message' => 'Demande acceptée.', 'colis' => $colis]);
        } catch (Exception $e) {
            Log::error('Erreur acceptation colis : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Refuser une demande de colis (statut -> ANNULE). Motif pris en entrée (stockage détaillé à ajouter via migration dédiée).
     */
    public function refuser(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $request->validate([
                'motif' => ['required', 'string', 'max:500'],
            ]);

            if ($colis->status !== ColisStatus::EN_ATTENTE) {
                return response()->json(['success' => false, 'message' => 'Seules les demandes en attente peuvent être refusées.'], 422);
            }

            $colis->status = ColisStatus::ANNULE;
            // persister le motif de refus quand le champ/migration sera en place
            $colis->save();

            return response()->json(['success' => true, 'message' => 'Demande refusée.', 'colis' => $colis]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Erreur refus colis : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Assigner un livreur à un colis.
     */
    public function assignerLivreur(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $request->validate([
                'livreur_id' => ['required', 'exists:users,id'],
            ]);
            $livreur = User::find($request->livreur_id);
            if ($livreur->type !== UserType::LIVREUR) {
                return response()->json(['success' => false, 'message' => 'L\'utilisateur choisi n\'est pas un livreur.'], 422);
            }
            $colis->livreur_id = $livreur->id;
            $colis->save();
            return response()->json(['success' => true, 'message' => 'Livreur assigné.', 'colis' => $colis]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Erreur assignation livreur : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Changer le statut d'un colis selon le workflow.
     */
    public function changerStatut(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $request->validate([
                'status' => ['required', 'in:en_enlevement,recupere,en_transit,en_agence,en_livraison,livre']
            ]);
            $colis->status = ColisStatus::from($request->status);
            if ($request->status === ColisStatus::LIVRE->value) {
                $colis->date_livraison = now();
            }
            $colis->save();
            return response()->json(['success' => true, 'message' => 'Statut mis à jour.', 'colis' => $colis]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Erreur changement statut colis : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Ajouter des preuves (photo de livraison, signature). Fichiers optionnels.
     */
    public function ajouterPreuves(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $request->validate([
                'photo_livraison' => ['sometimes', 'file', 'image', 'max:5120'], // 5MB
                'signature_destinataire' => ['sometimes', 'file', 'image', 'max:5120'],
            ]);

            if ($request->hasFile('photo_livraison')) {
                $path = $request->file('photo_livraison')->store('colis/preuves', 'public');
                $colis->photo_livraison = $path;
            }
            if ($request->hasFile('signature_destinataire')) {
                $path = $request->file('signature_destinataire')->store('colis/preuves', 'public');
                $colis->signature_destinataire = $path;
            }
            $colis->save();

            return response()->json(['success' => true, 'message' => 'Preuves ajoutées.', 'colis' => $colis]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Erreur ajout preuves colis : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Vérifier un colis à l'entrepôt (poids réel, ajustement prix si fourni).
     */
    public function verifier(Request $request, Colis $colis)
    {
        try {
            $user = $request->user();
            if ($user->type !== UserType::AGENCE) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            }
            $request->validate([
                'poids' => ['sometimes', 'numeric', 'min:0.01'],
                'prix_total' => ['sometimes', 'numeric', 'min:0'],
            ]);

            if ($request->filled('poids')) {
                $colis->poids = $request->poids;
            }
            if ($request->filled('prix_total')) {
                $colis->prix_total = $request->prix_total;
            }
            // recalcul via TarificationService si besoin

            $colis->save();
            return response()->json(['success' => true, 'message' => 'Colis vérifié.', 'colis' => $colis]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Erreur vérification colis : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    // D'autres méthodes (par exemple, pour la gestion spécifique des tarifs ou des missions) pourraient être ajoutées ici.
}
