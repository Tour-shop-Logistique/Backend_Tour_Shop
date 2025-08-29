<?php

namespace App\Http\Controllers\Api; // Assure-toi que ce namespace correspond bien à l'emplacement du fichier

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Agence;
use App\Enums\UserType;
use Illuminate\Support\Facades\Log;
use Exception; // Importer la classe Exception pour la gestion des erreurs

class AuthController extends Controller
{
    /**
     * Enregistre un nouvel utilisateur (client, livreur, agence ou backoffice).
     * Crée une agence si le type d'utilisateur est 'agence'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // Valide les données d'entrée de la requête
            $request->validate([
                'nom' => ['required', 'string', 'max:255'],
                'prenoms' => ['required', 'string', 'max:255'],
                // Validation du téléphone avec regex pour la Côte d'Ivoire et unicité
                'telephone' => ['required', 'string', 'unique:users,telephone'],
                'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'], // Mot de passe min 8 caractères et confirmation
                'type' => ['required', 'in:client,livreur,agence,backoffice'], // Type d'utilisateur prédéfini
            ]);

            // Crée le nouvel utilisateur
            $user = User::create([
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'type' => UserType::from($request->type), // Utilise l'Enum pour le type
                'password' => Hash::make($request->password),
                'actif' => true, // Active l'utilisateur par défaut
            ]);

            // Génère un jeton d'accès pour l'API pour le nouvel utilisateur
            $token = $user->createToken($user->type->value . '_token')->plainTextToken;

            // Retourne une réponse de succès avec les infos de l'utilisateur et le jeton
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'user' => $user->toArray(), // Convertit le modèle en tableau pour la réponse JSON
                'token' => $token,
                'requires_agence_setup' => $user->type === UserType::AGENCE // Indique si l'utilisateur doit configurer son agence
            ], 201); // Statut HTTP 201 Created

        } catch (ValidationException $e) {
            // Capture les erreurs de validation et retourne une réponse 422 Unprocessable Entity
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            // Capture toute autre exception inattendue
            Log::error('Erreur lors de l\'enregistrement de l\'utilisateur : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de l\'inscription. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500); // Statut HTTP 500 Internal Server Error
        }
    }

    /**
     * Authentifie un utilisateur et génère un jeton d'accès.
     * La connexion peut se faire par téléphone ou email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Valide les identifiants de connexion
            $request->validate([
                'telephone' => 'required_without_all:email|string', // Téléphone requis si email absent
                'email' => 'required_without_all:telephone|string|email', // Email requis si téléphone absent
                'password' => 'required|string',
                'type' => 'required|string|in:client,livreur,admin,backoffice,agence', // Type d'utilisateur
            ]);

            // Recherche l'utilisateur par téléphone ou email et par type
            $user = User::when($request->filled('telephone'), function ($query) use ($request) {
                $query->where('telephone', $request->telephone)->where('type', $request->type);
            })->when($request->filled('email'), function ($query) use ($request) {
                $query->where('email', $request->email)->where('type', $request->type);
            })->first();

            // Vérifie si l'utilisateur existe et si le mot de passe est correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'auth' => ['Les identifiants fournis sont incorrects.'],
                ]);
            }

            // Vérifie si le compte utilisateur est actif
            if (!$user->actif) {
                throw ValidationException::withMessages([
                    'account' => ['Votre compte est désactivé.'],
                ]);
            }

            // Supprime tous les jetons d'accès existants de cet utilisateur (stratégie de session unique)
            $user->tokens()->delete();

            // Génère un nouveau jeton d'accès
            $token = $user->createToken($user->type->value . '_token')->plainTextToken;

            // Retourne une réponse de succès avec les infos de l'utilisateur et le nouveau jeton
            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => $user->toArray(),
                'token' => $token
            ]);
        } catch (ValidationException $e) {
            // Capture les erreurs de validation spécifiques à la connexion
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des identifiants.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            // Capture toute autre exception inattendue
            Log::error('Erreur lors de la connexion de l\'utilisateur : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la connexion. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500);
        }
    }

    /**
     * Déconnecte l'utilisateur en révoquant son jeton d'accès actuel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Vérifie si un utilisateur est authentifié et révoque son jeton actuel
            if ($request->user()) {
                $request->user()->tokens()->where(column: 'id', operator: $request->user()->currentAccessToken()->id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Déconnexion réussie.'
                ]);
            }
            // Si aucun utilisateur n'est authentifié (ce cas ne devrait pas arriver avec le middleware)
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur authentifié pour la déconnexion.'
            ], 401); // Statut HTTP 401 Unauthorized

        } catch (Exception $e) {
            // Capture toute exception lors de la déconnexion
            Log::error('Erreur lors de la déconnexion de l\'utilisateur : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la déconnexion. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500);
        }
    }

    /**
     * Récupère les informations du profil de l'utilisateur authentifié.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            // Vérifie si un utilisateur est authentifié et retourne ses informations
            if ($request->user()) {
                return response()->json([
                    'success' => true,
                    'user' => $request->user()->toArray() // Convertit le modèle utilisateur en tableau
                ]);
            }
            // Si aucun utilisateur n'est authentifié
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur authentifié.'
            ], 401);
        } catch (Exception $e) {
            // Capture toute exception lors de la récupération du profil
            Log::error('Erreur lors de la récupération du profil utilisateur : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la récupération du profil. Veuillez réessayer ultérieurement.',
                // 'error_details' => $e->getMessage() // À décommenter pour le débogage seulement
            ], 500);
        }
    }
}
