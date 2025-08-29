<?php

use App\Http\Controllers\Api\AgenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\Client\ColisController;
use App\Http\Controllers\Api\Client\TarificationController;
use App\Http\Controllers\Api\Livreur\MissionController;
use App\Http\Controllers\Api\Admin\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "api" middleware group. Make something great!
 * |
 */

// Routes publiques (sans authentification)
Route::post('/register', [AuthController::class, 'register']); // Inscription
Route::post('/login', [AuthController::class, 'login']); // Connexion

// Routes protégées par l'authentification (nécessitent un jeton API valide)
Route::middleware('auth:sanctum')->group(function () {
    // Routes d'authentification protégées
    Route::post('/logout', [AuthController::class, 'logout']); // Déconnexion
    Route::get('/user', [AuthController::class, 'profile']); // Profil de l'utilisateur connecté

    // Routes clients
    /*Route::prefix('client')->group(function () {
        Route::get('/colis', [ColisController::class, 'index']);
        Route::post('/colis', [ColisController::class, 'store']);
        Route::get('/colis/{id}', [ColisController::class, 'show']);
        Route::post('/colis/{id}/annuler', [ColisController::class, 'annuler']);
        Route::get('/suivre/{codesuivi}', [ColisController::class, 'suivre']);
        Route::get('/colis/search-destinataires', [ColisController::class, 'searchDestinataires']);
        Route::post('/tarification/simuler', [TarificationController::class, 'simuler']);
        Route::get('/agences-proches', [TarificationController::class, 'agencesProches']);
    });*/

    // Routes livreurs
    /*Route::prefix('livreur')->group(function () {
        Route::get('/dashboard', [MissionController::class, 'dashboard']);
        Route::get('/missions-disponibles', [MissionController::class, 'missionsDisponibles']);
        Route::post('/missions/{colis}/accepter', [MissionController::class, 'accepterMission']);
        Route::get('/mes-missions', [MissionController::class, 'mesMissions']);
        Route::post('/missions/{colis}/confirmer-enlevement', [MissionController::class, 'confirmerEnlevement']);
        Route::post('/missions/{colis}/confirmer-livraison', [MissionController::class, 'confirmerLivraison']);
        Route::post('/disponibilite', [MissionController::class, 'changerDisponibilite']);
    });*/

    // Routes agences
    Route::prefix('agence')->group(function () {
        Route::post('/setup', [AgenceController::class, 'setupAgence']);
        Route::get('/status', [AgenceController::class, 'checkAgenceStatus']);

        Route::get('/show', [AgenceController::class, 'show']);  // Afficher les informations de l'agence
        Route::put('/update', [AgenceController::class, 'update']);  // Mettre à jour les informations de l'agence

        // Application Agence: workflow opérationnel
        Route::get('/expeditions', [AgenceController::class, 'colis']);
        Route::post('/expeditions/{colis}/accepter', [AgenceController::class, 'accepter']);
        Route::post('/expeditions/{colis}/refuser', [AgenceController::class, 'refuser']);
        Route::post('/expeditions/{colis}/assign-livreur', [AgenceController::class, 'assignerLivreur']);
        Route::post('/expeditions/{colis}/statut', [AgenceController::class, 'changerStatut']);
        Route::post('/expeditions/{colis}/preuves', [AgenceController::class, 'ajouterPreuves']);
        Route::post('/expeditions/{colis}/verifier', [AgenceController::class, 'verifier']);
    });

    // Routes tarifs
    /*Route::prefix('tarifs')->group(function () {
        Route::get('/index', [TarifController::class, 'index']);  // Lister tous les tarifs
        Route::post('/store', [TarifController::class, 'store']);  // Créer un nouveau tarif
        Route::get('/show/{tarif}', [TarifController::class, 'show']);  // Afficher un tarif spécifique
        Route::put('/update/{tarif}', [TarifController::class, 'update']);  // Mettre à jour un tarif spécifique
        Route::delete('/destroy/{tarif}', [TarifController::class, 'destroy']);  // Supprimer un tarif spécifique
    });*/

    // Routes admin
    Route::prefix('admin')->group(function () {
        // TODO: Controllers admin
    });
});
