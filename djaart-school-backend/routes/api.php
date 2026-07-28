<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\RapportController;
use App\Http\Controllers\Api\Documents\AttestationController;
use App\Http\Controllers\Api\Documents\CarteScolaireController;
use App\Http\Controllers\Api\Finance\FraisScolariteController;
use App\Http\Controllers\Api\Finance\PaiementController;
use App\Http\Controllers\Api\Finance\RecuController;
use App\Http\Controllers\Api\Inscription\ApprenantController;
use App\Http\Controllers\Api\Inscription\InscriptionController;
use App\Http\Controllers\Api\Inscription\PhotoMasseController;
use App\Http\Controllers\Api\Inscription\PhotoSeanceController;
use App\Http\Controllers\Api\Landing\DemandeDemoController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Http\Controllers\Api\Parametrage\ClasseController;
use App\Http\Controllers\Api\Parametrage\DepartementController;
use App\Http\Controllers\Api\Parametrage\EtablissementController;
use App\Http\Controllers\Api\Parametrage\FiliereController;
use App\Http\Controllers\Api\Parametrage\MatiereController;
use App\Http\Controllers\Api\Parametrage\NiveauController;
use App\Http\Controllers\Api\Parametrage\UniteEnseignementController;
use App\Http\Controllers\Api\Pedagogie\AffectationController;
use App\Http\Controllers\Api\Pedagogie\BulletinController;
use App\Http\Controllers\Api\Pedagogie\ConduiteController;
use App\Http\Controllers\Api\Pedagogie\NoteController;
use App\Http\Controllers\Api\Pedagogie\PvController;
use App\Http\Controllers\Api\Pedagogie\ReleveController;
use App\Http\Controllers\Api\Pedagogie\SemestreController;
use App\Http\Controllers\Api\Pedagogie\SequenceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/demandes-demo', [DemandeDemoController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/demandes-demo', [DemandeDemoController::class, 'index']);
    });

    // Gestion des comptes/roles : jamais delegable via une permission (risque
    // d'auto-elevation de privileges), reste strictement liee au role.
    Route::middleware('role:super_admin|admin_etablissement')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.parametrage_etablissement')->group(function () {
        Route::apiResource('etablissements', EtablissementController::class)->except('show');
        Route::post('/etablissements/{etablissement}/logo', [EtablissementController::class, 'updateLogo']);
        Route::post('/etablissements/{etablissement}/signature', [EtablissementController::class, 'updateSignature']);
        Route::post('/etablissements/{etablissement}/entete', [EtablissementController::class, 'updateEntete']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.parametrage_academique')->group(function () {
        Route::apiResource('annees-academiques', AnneeAcademiqueController::class)->except('show')
            ->parameters(['annees-academiques' => 'anneeAcademique']);
        Route::apiResource('departements', DepartementController::class)->except(['show', 'index']);
        Route::apiResource('filieres', FiliereController::class)->except('show');
        Route::apiResource('classes', ClasseController::class)->except(['show', 'index'])
            ->parameters(['classes' => 'classe']);
        Route::apiResource('matieres', MatiereController::class)->except('show');
        Route::apiResource('unites-enseignement', UniteEnseignementController::class)->except(['show', 'index'])
            ->parameters(['unites-enseignement' => 'uniteEnseignement']);

        Route::get('/filieres/{filiere}/niveaux', [NiveauController::class, 'index']);
        Route::post('/niveaux', [NiveauController::class, 'store']);
        Route::put('/niveaux/{niveau}', [NiveauController::class, 'update']);
        Route::delete('/niveaux/{niveau}', [NiveauController::class, 'destroy']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.frais_scolarite')->group(function () {
        Route::apiResource('frais-scolarite', FraisScolariteController::class)->except(['show', 'index'])
            ->parameters(['frais-scolarite' => 'fraisScolarite']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.sequences')->group(function () {
        Route::apiResource('sequences', SequenceController::class)->except(['show', 'index']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.semestres')->group(function () {
        Route::apiResource('semestres', SemestreController::class)->except(['show', 'index']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|acces.affectations')->group(function () {
        Route::post('/affectations', [AffectationController::class, 'store']);
        Route::delete('/affectations/{affectation}', [AffectationController::class, 'destroy']);
        Route::post('/affectations/{affectation}/notes/deverrouiller', [NoteController::class, 'deverrouiller']);
    });

    // Lectures partagees par plusieurs ecrans (listes deroulantes) : le OR
    // s'elargit avec toutes les permissions des ecrans qui en dependent.
    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|comptable|acces.inscriptions|acces.caisse|acces.frais_scolarite|acces.parametrage_academique')->group(function () {
        Route::get('/classes', [ClasseController::class, 'index']);
        Route::get('/frais-scolarite', [FraisScolariteController::class, 'index']);
        Route::get('/departements', [DepartementController::class, 'index']);
        Route::get('/semestres/{semestre}/unites-enseignement', [UniteEnseignementController::class, 'index']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|comptable|acces.apprenants')->group(function () {
        Route::get('/apprenants', [ApprenantController::class, 'index']);
        Route::get('/apprenants/{apprenant}', [ApprenantController::class, 'show']);
        Route::get('/apprenants/{apprenant}/echeancier', [ApprenantController::class, 'echeancier']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|comptable|acces.inscriptions')->group(function () {
        Route::get('/inscriptions', [InscriptionController::class, 'index']);
        Route::post('/inscriptions', [InscriptionController::class, 'store']);
        Route::post('/inscriptions/{inscription}/annuler', [InscriptionController::class, 'cancel']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|comptable|acces.caisse')->group(function () {
        Route::get('/paiements', [PaiementController::class, 'index']);
        Route::post('/paiements', [PaiementController::class, 'store']);
        Route::get('/recus/{recu}/telecharger', [RecuController::class, 'telecharger']);

        Route::get('/rapports/impayes', [RapportController::class, 'impayes']);
        Route::get('/rapports/statistiques-reussite', [RapportController::class, 'statistiquesReussite']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|enseignant|secretaire|acces.sequences|acces.semestres|acces.notes|acces.conduite|acces.bulletins|acces.releves')->group(function () {
        Route::get('/sequences', [SequenceController::class, 'index']);
        Route::get('/semestres', [SemestreController::class, 'index']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|enseignant|acces.affectations|acces.notes')->group(function () {
        Route::get('/affectations', [AffectationController::class, 'index']);
        Route::get('/affectations/{affectation}/notes', [NoteController::class, 'show']);
        Route::post('/affectations/{affectation}/notes', [NoteController::class, 'store']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|enseignant|secretaire|acces.notes')->group(function () {
        Route::get('/affectations/{affectation}/sequences/{sequence}/pv', [PvController::class, 'pourSequence']);
        Route::get('/affectations/{affectation}/semestres/{semestre}/pv', [PvController::class, 'pourSemestre']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|enseignant|acces.conduite')->group(function () {
        Route::get('/classes/{classe}/sequences/{sequence}/conduite', [ConduiteController::class, 'show']);
        Route::post('/classes/{classe}/sequences/{sequence}/conduite', [ConduiteController::class, 'store']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|acces.bulletins')->group(function () {
        Route::get('/bulletins', [BulletinController::class, 'index']);
        Route::post('/classes/{classe}/sequences/{sequence}/cloturer', [BulletinController::class, 'store']);
        Route::get('/bulletins/{bulletin}/telecharger', [BulletinController::class, 'telecharger']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|acces.releves')->group(function () {
        Route::get('/releves', [ReleveController::class, 'index']);
        Route::post('/classes/{classe}/releves/annuel', [ReleveController::class, 'storeAnnuel']);
        Route::get('/releves/{releve}/telecharger', [ReleveController::class, 'telecharger']);
    });

    // Photo apprenant : partagee par la fiche Apprenant et la Seance photo.
    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|acces.apprenants|acces.seance_photo')->group(function () {
        Route::post('/apprenants/{apprenant}/photo', [ApprenantController::class, 'updatePhoto']);
    });

    // Attestations/cartes scolaires : generation individuelle (fiche Apprenant)
    // et en masse (Documents en masse) partagent les memes routes.
    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|acces.apprenants|acces.documents_masse')->group(function () {
        Route::get('/apprenants/{apprenant}/attestations', [AttestationController::class, 'index']);
        Route::post('/apprenants/{apprenant}/attestations', [AttestationController::class, 'store']);
        Route::get('/attestations/{attestation}/telecharger', [AttestationController::class, 'telecharger']);
        Route::post('/classes/{classe}/attestations/masse', [AttestationController::class, 'storeMasse']);
        Route::get('/attestations/zip', [AttestationController::class, 'zip']);

        Route::get('/apprenants/{apprenant}/cartes-scolaires', [CarteScolaireController::class, 'index']);
        Route::post('/apprenants/{apprenant}/cartes-scolaires', [CarteScolaireController::class, 'store']);
        Route::get('/cartes-scolaires/{carte}/telecharger', [CarteScolaireController::class, 'telecharger']);
        Route::post('/classes/{classe}/cartes-scolaires/masse', [CarteScolaireController::class, 'storeMasse']);
        Route::get('/cartes-scolaires/zip', [CarteScolaireController::class, 'zip']);
    });

    Route::middleware('role_or_permission:super_admin|admin_etablissement|secretaire|acces.seance_photo')->group(function () {
        Route::get('/classes/{classe}/photos/liste', [PhotoSeanceController::class, 'liste']);
        Route::post('/classes/{classe}/apprenants/photos-masse', [PhotoMasseController::class, 'store']);
    });
});
