<?php

namespace App\Services;

use App\Config\Database;
use App\Models\Classes\Jeu;
use App\Models\Classes\Joueur;
use App\Models\Classes\TourDeJeu;

class ReplayService
{
	private $jeu;

	public function __construct(string $nom1, string $nom2)
	{
		$joueur1 = new Joueur($nom1, "rouge", 0); // on met elo à 0 car pas besoin de le gérer
		$joueur2 = new Joueur($nom2, "noir", 0);
		$this->jeu = new Jeu($joueur1, 0, $joueur2);
	}

	public function createReplayBoard()
	{
		return $this->jeu->get_plateau();
	}

   public function reconstruirePlateau(array $tours, int $tourActuel)
	{
		// recrée le plateau de départ
		$this->jeu->resetPlateau();
		
		for ($i = 0; $i <= $tourActuel; $i++) {

			if (!isset($tours[$i])) {
				break;
			}

			$tour = $tours[$i];

			$this->jeu->deplacerPiece(
				(int)$tour->getXDepart(),
				(int)$tour->getYDepart(),
				(int)$tour->getXArrive(),
				(int)$tour->getYArrive()
			);
		}

		return $this->jeu->get_plateau();
	}
	public function getAllTour(int $partieId): array
	{
		$replay = Database::select("archives_tourdejeu", ["*"], ["partie_id" => $partieId], "AND", "tdj_ntour", "ASC");

		$tours = [];

		if (!empty($replay)) {
			foreach ($replay as $tour) {

				$tours[] = new TourDeJeu(
					(int)$tour["tdj_id"],
					(int)$tour["tdj_ntour"],
					(int)$tour["tdj_joueuractif"],
					(int)$tour["xdepart"],
					(int)$tour["ydepart"],
					(int)$tour["xarrive"],
					(int)$tour["yarrive"],
					(bool)$tour["tdj_amanger"]
				);
			}
		}

		return $tours;
	}

	public static function getPlayers($partieId)
	{
		$partie = Database::select("archive_partie", ["username1, username2"], ["partie_id" => $partieId]);

		return $partie;
	}


}