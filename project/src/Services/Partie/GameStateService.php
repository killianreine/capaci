<?php

namespace App\Services\Partie;

use App\Config\Database;

/**
 * Service de gestion de l'état des parties
 * Responsable de la récupération des informations sur les parties en cours
 */
class GameStateService
{
    /**
     * Récupère l'état complet d'une partie
     */
    public function getGameState(int $code): ?array
    {
        $parties = Database::select("partie", ["*"], ["partie_code" => $code]);
        if (empty($parties)) {
            return null;
        }

        $partie = $parties[0];
        $salons = Database::select("salon", ["joueur1", "joueur2"], [
            "partie_id" => $partie['partie_id']
        ]);
        if (empty($salons)) {
            return null;
        }

        $salon = $salons[0];

        // Récupérer joueur 1
        $joueur1Data = Database::select("joueur", ["users_id"], ["joueur_id" => $salon['joueur1']]);
        $joueur1UserId = $joueur1Data[0]['users_id'] ?? null;
        $joueur1User = Database::select("users", ["users_username"], ["users_id" => $joueur1UserId]);
        $joueur1Name = $joueur1User[0]['users_username'] ?? null;

        // Récupérer joueur 2
        $joueur2Data = Database::select("joueur", ["users_id"], ["joueur_id" => $salon['joueur2']]);
        $joueur2UserId = $joueur2Data[0]['users_id'] ?? null;
        $joueur2User = Database::select("users", ["users_username"], ["users_id" => $joueur2UserId]);
        $joueur2Name = $joueur2User[0]['users_username'] ?? null;

        // Déterminer le gagnant si la partie est terminée
        $gagnantId = null;
        if ($partie['partie_etat'] === 'TERMINEE' && !empty($partie['partie_joueurgagnant'])) {
            $joueurGagnant = Database::select("joueur", ["users_id"], [
            "joueur_id" => $partie['partie_joueurgagnant']
            ]);
            $gagnantId = !empty($joueurGagnant) ? (int)$joueurGagnant[0]['users_id'] : null;
        }

        return [
            'plateau' => $partie['partie_plateau'],
            'joueurActifId' => (int)($partie['partie_joueuractif'] ?? 0),
            'etat' => $partie['partie_etat'],
            'joueur1Name' => $joueur1Name,
            'joueur2Name' => $joueur2Name,
            'joueur1Id' => (int)$joueur1UserId,
            'joueur2Id' => (int)$joueur2UserId,
            'dateModif' => $partie['partie_date_modif'],
            'gagnantId' => $gagnantId
        ];
    }

    /**
     * Vérifie si c'est le tour d'un joueur
     */
    public function isPlayerTurn(int $code, int $userId): bool
    {
        $state = $this->getGameState($code);
        return $state && ((int)$state['joueurActifId'] === (int)$userId);
    }

    /**
     * Vérifie si une partie existe
     */
    public function partieExists(int $code): bool
    {
        $parties = Database::select("partie", ['partie_id'], ['partie_code' => $code]);
        return !empty($parties);
    }
}