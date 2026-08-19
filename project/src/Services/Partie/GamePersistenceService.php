<?php

namespace App\Services\Partie;

use App\Models\Classes\Jeu;
use App\Models\Classes\Joueur;
use App\Config\Database;

/**
 * Service de persistance des parties
 * Responsable du chargement et de la sauvegarde des parties en base de données
 */
class GamePersistenceService
{
	/**
	 * Charge une partie depuis la base de données
	 */
	public function loadGameFromDB(int $code): ?Jeu
	{
		$parties = Database::select("partie", ["*"], ["partie_code" => $code]);
		if (empty($parties)) {
			return null;
		}

		$partie = $parties[0];

		$tdj=$partie["partie_tdj"];
		$salons = Database::select("salon", ["joueur1", "joueur2"], [
			"partie_id" => $partie['partie_id']
		]);
		if (empty($salons)) {
			return null;
		}

		$salon = $salons[0];

		// Récupérer le joueur 1
		$joueur1Data = Database::select("joueur", ["users_id","joueur_id"], ["joueur_id" => $salon['joueur1']]);

		$joueur1UserId = $joueur1Data[0]['users_id'] ?? null;
		$joueur1Id = $joueur1Data[0]['joueur_id'] ?? null;

		$joueur1User = Database::select("users", ["users_username", "users_elo"], ["users_id" => $joueur1UserId]);
		$joueur1Name = $joueur1User[0]['users_username'] ?? null;
		$joueur1Elo = $joueur1User[0]["users_elo"] ?? 1000;


		// Récupérer le joueur 2
		$joueur2Data = Database::select("joueur", ["users_id","joueur_id"], ["joueur_id" => $salon['joueur2']]);
		$joueur2UserId = $joueur2Data[0]['users_id'] ?? null;
		$joueur2Id = $joueur2Data[0]['joueur_id'] ?? null;
		$joueur2User = Database::select("users", ["users_username", "users_elo"], ["users_id" => $joueur2UserId]);
		$joueur2Name = $joueur2User[0]['users_username'] ?? null;
		$joueur2Elo = $joueur2User[0]["users_elo"] ?? 1000;

		// Créer les objets joueurs
		$joueur1 = new Joueur($joueur1Name, "noir",$joueur1Elo,$joueur1Id);
		$joueur2 = new Joueur($joueur2Name, "rouge", $joueur2Elo,$joueur2Id );

		// Créer le jeu avec les deux joueurs dès la construction
		$jeu = new Jeu($joueur1, $code, $joueur2);
		$jeu->setTdj($tdj);

		$joueur1->resetCompteurs();
		$joueur2->resetCompteurs();

		$jeu->get_plateau()->fromJson($partie['partie_plateau'], $joueur1, $joueur2);

		// Définir le joueur actif
		if ((int)$partie['partie_joueuractif'] !== (int)$joueur1UserId) {
			$jeu->finTour();
		}

		return $jeu;
	}

	/**
	 * Sauvegarde une partie en base de données
	 */
	public function saveGame(Jeu $jeu): void
	{
		$code = $jeu->getCode();
		$joueurActif = $jeu->getJoueurActif();
		$userActif = $joueurActif ? $joueurActif->getNom() : null;

		$users = Database::select('users', ['users_id'], ['users_username' => $userActif]);
		$userIdActif = !empty($users) ? (int)$users[0]['users_id'] : null;

		Database::update('partie', [
			'partie_plateau' => $jeu->get_plateau()->toJson(),
			'partie_joueurActif' => $userIdActif,
			'partie_date_modif' => date('Y-m-d H:i:s'),
			'partie_tdj' => $jeu->getTdj()
		], [
			'partie_code' => $code
		]);

		$this->updateJoueurStats($jeu);
	}

	/**
	 * Met à jour les statistiques des joueurs (nombre de pièces)
	 */
	private function updateJoueurStats(Jeu $jeu): void
	{
		$code = $jeu->getCode();
		$parties = Database::select("partie", ["partie_id"], ["partie_code" => $code]);
		if (empty($parties)) {
			return;
		}

		$partieId = $parties[0]['partie_id'];
		$salons = Database::select("salon", ["joueur1", "joueur2"], ["partie_id" => $partieId]);
		if (empty($salons)) {
			return;
		}

		$salon = $salons[0];
		$joueur1 = $jeu->getJoueur1();
		$joueur2 = $jeu->getJoueur2();

		Database::update("joueur", [
			'joueur_nbPierre' => $joueur1->nbPierre,
			'joueur_nbFeuille' => $joueur1->nbFeuille,
			'joueur_nbCiseau' => $joueur1->nbCiseau,
		], ['joueur_id' => (int)$salon['joueur1']]);

		Database::update("joueur", [
			'joueur_nbPierre' => $joueur2->nbPierre,
			'joueur_nbFeuille' => $joueur2->nbFeuille,
			'joueur_nbCiseau' => $joueur2->nbCiseau,
		], ['joueur_id' => (int)$salon['joueur2']]);
	}

	/**
	 * Termine une partie et enregistre le gagnant
	 */
	public function endGame(int $code, string $gagnantUsername): void
	{
		$parties = Database::select("partie", ["partie_id","partie_date_creation"], ["partie_code" => $code]);
		if (empty($parties)) {
			return;
		}

		$partieId     = $parties[0]['partie_id'];
		$dateCreation = $parties[0]['partie_date_creation'];
		$salons = Database::select("salon", ["joueur1", "joueur2"], ["partie_id" => $partieId]);
		if (empty($salons)) {
			return;
		}

		$salon = $salons[0];

		$users = Database::select('users', ['users_id'], ['users_username' => $gagnantUsername]);
		if (empty($users)) {
			return;
		}
		$gagnantUserId = (int)$users[0]['users_id'];

		$joueur1Data = Database::select("joueur", ["users_id"], ["joueur_id" => $salon['joueur1']]);
		$joueur2Data = Database::select("joueur", ["users_id"], ["joueur_id" => $salon['joueur2']]);

		$joueur1UserId = !empty($joueur1Data) ? (int)$joueur1Data[0]['users_id'] : null;
		$joueur2UserId = !empty($joueur2Data) ? (int)$joueur2Data[0]['users_id'] : null;

		$joueur1User = Database::select("users", ["users_username", "users_elo"], ["users_id" => $joueur1UserId]);
		$joueur1Name = $joueur1User[0]['users_username'] ?? null;
		$joueur1Elo  = (int)($joueur1User[0]["users_elo"] ?? 1000);

		$joueur2User = Database::select("users", ["users_username", "users_elo"], ["users_id" => $joueur2UserId]);
		$joueur2Name = $joueur2User[0]['users_username'] ?? null;
		$joueur2Elo  = (int)($joueur2User[0]["users_elo"] ?? 1000);


		$joueurGagnantId = null;
		if ($joueur1UserId === $gagnantUserId) 
		{
			$joueurGagnantId = (int)$salon['joueur1'];
			$newElo = $joueur1Elo + ($joueur2Elo * 0.10);
		} elseif ($joueur2UserId === $gagnantUserId) 
		{
			$joueurGagnantId = (int)$salon['joueur2'];
			$newElo = $joueur2Elo + ($joueur1Elo * 0.10);
		}

		Database::update("users",["users_nbVictoire" => Database::raw("users_nbVictoire + 1")], ["users_id" => $gagnantUserId]);
		Database::update("users",["users_elo" => $newElo], ["users_id" => $gagnantUserId]);


		if ($joueurGagnantId === null) {
			return;
		}

		Database::update('partie', [
			'partie_etat' => 'TERMINEE',
			'partie_joueurGagnant' => $joueurGagnantId,
			'partie_date_modif' => date('Y-m-d H:i:s')
		], [
			'partie_code' => $code
		]);


		$this->archivePartie($partieId,$joueur1UserId,$joueur2UserId,$joueur1Name,$joueur2Name,$dateCreation,$gagnantUserId);
	}

	public function archivePartie(
		int $partieId,
		int $joueur1UserId,
		int $joueur2UserId,
		string $joueur1Name,
		string $joueur2Name,
		string $dateCreation,
		int $gagnantUserId
	): void
	{

		$partie_id = Database::insert('archive_partie', [
			'partie_id'      => $partieId,
			'users_id'       => $joueur1UserId,
			'users2_id'      => $joueur2UserId,
			'username1'      => $joueur1Name,
			'username2'      => $joueur2Name,
			'date_creation'  => $dateCreation,
			'date_fin'       => date('Y-m-d H:i:s'),
			'gagnant'        => $gagnantUserId === $joueur1UserId ? 1 : 2
		]);

		Database::insertFromSelect(
			"archives_tourdejeu",
			[
				"tdj_nTour",
				"tdj_joueurActif",
				"xDepart",
				"yDepart",
				"xArrive",
				"yArrive",
				"tdj_aManger",
				"partie_id"
			],
			"tourdejeu",
			[
				"tdj_nTour",
				"tdj_joueurActif",
				"xDepart",
				"yDepart",
				"xArrive",
				"yArrive",
				"tdj_aManger",
				"partie_id"
			],
			[
				"partie_id" => $partieId
			]
		);
	}

	function dateEnFrancais(string $dateStr): string
	{
		$jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
		$mois  = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']; 
		
		$ts = strtotime($dateStr);
		return $jours[date('w', $ts)] . ' ' . date('d', $ts) . ' ' . $mois[(int)date('n', $ts)-1] . ' ' . date('Y', $ts) . ' à ' . date('H:i', $ts);
	}

	public function getVisibleGamesForUser(int $userId): array
    {
        $publiques = [];
        $privees = [];

        $waitingCodes = Database::select("codes_attente");
        foreach ($waitingCodes as $wc) {
            $ownerId = (int)$wc['user_id'];
            $gameData = [
                'nom_hote'    => $wc['username'],
                'adversaire'  => "En attente...",
                'code'        => $wc['code'],
                'nb_joueurs'  => 1,
                'max_joueurs' => 2,
                'date_tri'    => $wc['created_at'],
                'date_aff' => $this->dateEnFrancais($wc['created_at'])
            ];

            if ($ownerId === $userId) {
                $privees[] = $gameData;
            } elseif ((int)($wc['is_public'] ?? 0) === 1) {
                $publiques[] = $gameData;
            }
        }

        $allActiveParties = Database::select("partie", ["*"]); 
        foreach ($allActiveParties as $p) {
            if ($p['partie_etat'] === 'TERMINEE') continue;

            $salon = Database::select("salon", ["joueur1", "joueur2"], ["partie_id" => $p['partie_id']]);
            if (empty($salon)) continue;
            $s = $salon[0];
            
            $j1Res = Database::select("joueur", ["users_id"], ["joueur_id" => $s['joueur1']]);
            $j2Res = !empty($s['joueur2']) ? Database::select("joueur", ["users_id"], ["joueur_id" => $s['joueur2']]) : [];

            $j1UserId = !empty($j1Res) ? (int)$j1Res[0]['users_id'] : null;
            $j2UserId = !empty($j2Res) ? (int)$j2Res[0]['users_id'] : null;

            if ($j1UserId === $userId || $j2UserId === $userId) {
                $adversaireId = ($j1UserId === $userId) ? $j2UserId : $j1UserId;
                $nomAdversaire = "En attente...";

                if ($adversaireId) {
                    $userRes = Database::select("users", ["users_username"], ["users_id" => $adversaireId]);
                    $nomAdversaire = !empty($userRes) ? $userRes[0]['users_username'] : "Joueur inconnu";
                }

                $privees[] = [
                    'adversaire'  => $nomAdversaire,
                    'nom_hote'    => $nomAdversaire,
                    'code'        => $p['partie_code'],
                    'nb_joueurs'  => $j2UserId ? 2 : 1,
                    'max_joueurs' => 2,
                    'date_tri'    => $p['partie_date_creation'],
                    'date_aff' => $this->dateEnFrancais($p['partie_date_creation'])
                ];
            }
        }

        $sortFn = fn($a, $b) => strtotime($b['date_tri']) - strtotime($a['date_tri']);
        usort($publiques, $sortFn);
        usort($privees, $sortFn);

        return ['publiques' => $publiques, 'privees' => $privees];
    }
}