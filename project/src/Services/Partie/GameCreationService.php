<?php

namespace App\Services\Partie;

use App\Models\Classes\Jeu;
use App\Models\Classes\Joueur;
use App\Config\Database;

class GameCreationService
{
    private CodeManagementService $codeService;
    private bool $public;

    public function __construct(CodeManagementService $codeService, bool $public = true)
    {
        $this->codeService = $codeService;
        $this->public = $public;
    }

    /**
     * Crée une nouvelle partie par l'host
     */
    public function createGame(): Jeu
    {
        // 1. Sécurisation de la session
        if (!isset($_SESSION['users_id'])) {
            throw new \Exception("Utilisateur non connecté.");
        }

        $users = Database::select("users", ['users_id', 'users_username'], [
            'users_id' => (int)$_SESSION['users_id']
        ]);

        // 2. Vérification que l'utilisateur existe bien en base
        if (empty($users)) {
             throw new \Exception("Utilisateur introuvable dans la base de données.");
        }

        $host = $users[0];
        
        // 3. Création des objets (le nom n'est plus NULL ici)
        $joueur1 = new Joueur($host['users_username'], "noir", 1);
        $code = $this->codeService->generateCode();
        $jeu = new Jeu($joueur1, $code);
        
        $_SESSION['joueur1_users_id'] = $host['users_id'];

        return $jeu;
    }

    /**
     * Rejoindre une partie existante
     */
    public function joinGame(int $code, Joueur $joueur): ?Jeu
    {
        $waitingData = $this->codeService->findByCode($code);
        if (!$waitingData) {
            return null;
        }

        $waitingPlayer = Database::select("users", ['*'], ['users_id' => $waitingData['user_id']]);

        // On récupère la visibilité stockée dans codes_attente (défaut à true/1)
        $isPublic = isset($waitingData['is_public']) ? (bool)$waitingData['is_public'] : true;

        $partieExistante = Database::select("partie", ['partie_id'], ['partie_code' => $code]);
        if (!empty($partieExistante)) {
            return null;
        }

        $users = Database::select("users", ['users_id', 'users_username', 'users_elo'], [
            'users_id' => (int)$_SESSION['users_id']
        ]);
        
        if (empty($users)) {
            return null;
        }
        $currentUser = $users[0];

        if ((int)$waitingData['user_id'] === (int)$currentUser['users_id']) {
            return null;
        }

        $dateNow = date('Y-m-d H:i:s');

        $joueur1 = new Joueur($waitingData['username'], "noir", $waitingPlayer[0]['users_elo']);
        $joueur2 = new Joueur($currentUser['users_username'], "rouge", $currentUser['users_elo']);

        $jeu = new Jeu($joueur1, $code, $joueur2);

        // On transmet $isPublic lors de la création en base
        $this->createGameInDatabase(
            $code,
            $jeu,
            (int)$waitingData['user_id'],
            (int)$currentUser['users_id'],
            $dateNow,
            $isPublic
        );

        $this->codeService->removeWaitingCode($code);

        return $jeu;
    }

    /**
     * Crée une partie en base de données
     */
    public function createGameInDatabase(
        int $code,
        Jeu $jeu,
        int $joueur1UserId,
        int $joueur2UserId,
        string $dateNow,
        bool $isPublic = true
    ): int {
        $joueur1Id = Database::insert("joueur", [
            'joueur_numero' => 1,
            'joueur_nbCiseau' => 4,
            'joueur_nbFeuille' => 4,
            'joueur_nbPierre' => 4,
            'users_id' => $joueur1UserId
        ]);

        $joueur2Id = Database::insert("joueur", [
            'joueur_numero' => 2,
            'joueur_nbCiseau' => 4,
            'joueur_nbFeuille' => 4,
            'joueur_nbPierre' => 4,
            'users_id' => $joueur2UserId
        ]);

        $partieId = Database::insert("partie", [
            'partie_code' => $code,
            'partie_plateau' => $jeu->get_plateau()->toJson(),
            'partie_etat' => 'EN COURS',
            'partie_joueurActif' => $joueur1UserId,
            'partie_date_creation' => $dateNow,
            'partie_tdj' => 0,
            'partie_date_modif' => $dateNow,
            'partie_public' => $isPublic ? 1 : 0 // Conversion bool vers int pour le SQL
        ]);

        Database::insert("salon", [
            'partie_id' => $partieId,
            'joueur1' => $joueur1Id,
            'joueur2' => $joueur2Id
        ]);

        return $partieId;
    }
}