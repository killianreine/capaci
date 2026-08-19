<?php
declare(strict_types=1);

/**
 * Classe Jeu
 * @author Thomas   Boudeele    GrA
 * @author Louis    Hagues      GrA
 * @author Killian  Reine       GrA
 * 
 * Dernière modification : Jeudi 05 janvier 2026
 */
namespace App\Models\Classes;

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Plateau.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Joueur.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Pieces.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Cases.php';

require_once __DIR__.'/../enums/Formes.php';

use App\Models\Enums\Forme;
use App\Models\Classes\Plateau;
use App\Models\Classes\Joueur;
use App\Models\Classes\Piece;
use App\Models\Classes\Cases;
use App\Services\PartieService;
use App\Services\Partie\GameStateService;

use App\Models\Enums\Formes;

/**
 * Classe principale gérant la logique globale du jeu.
 */
final class Jeu
{
	/** @var Plateau Instance du plateau de jeu */
	private Plateau $plateau;

	/** @var Joueur Référence au premier joueur */
	private Joueur $joueur1;

	/** @var Joueur Référence au second joueur */
	private ?Joueur $joueur2;

	/** @var int Détermine quel joueur doit jouer (0 ou 1) */
	private int $tour = 0;

	private int $tourDeJeu = 0;

	/** @var int Code d'accès permettant de rejoindre la partie au second joueur */
	private int $code;

	/** @var PartieService Service pour gérer les parties */
	private PartieService $partieService;

	/** @var GameStateService Service pour gérer l'état des parties */
	private GameStateService $gameStateService;

	public function __construct(Joueur $joueurHost, int $codeAccess, ?Joueur $joueur2 = null)
	{
		$this->joueur1 = $joueurHost;
		$this->joueur2 = $joueur2 ?? new Joueur("joueur 2", "rouge", 2);
		$this->plateau = new Plateau($this);
		$this->code = $codeAccess;

		$this->partieService = new PartieService();
		$this->gameStateService = new GameStateService();
		$this->init();
	}

	public function get_plateau(): Plateau
	{
		return $this->plateau;
	}

	public function getPlateau(): Plateau
	{
		return $this->plateau;
	}

	public function getJoueur1(): Joueur
	{
		return $this->joueur1;
	}

	public function getJoueur2(): Joueur
	{
		return $this->joueur2;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function getTdj() : int
	{
		return $this->tourDeJeu;
	}

	public function setTdj($tdj) 
	{
		$this->tourDeJeu = $tdj;
	}

	/**
	 * Initialise le plateau et les pièces (UML: init()).
	 */
	public function init(): void
	{
		$ligneA = [Forme::FEUILLES, Forme::CISEAUX, Forme::PIERRES, 
				Forme::PIERRES, Forme::CISEAUX, Forme::FEUILLES];
		$ligneB = [Forme::CISEAUX, Forme::PIERRES, Forme::FEUILLES, 
				Forme::FEUILLES, Forme::PIERRES, Forme::CISEAUX];

		// Placement Joueur 1 (Haut)
		$this->placerLigne(0, $ligneA, $this->joueur1);
		$this->placerLigne(1, $ligneB, $this->joueur1);

		if ($this->joueur2 !== null) {
			$this->placerLigne(4, $ligneB, $this->joueur2);
			$this->placerLigne(5, $ligneA, $this->joueur2);
		}

		$this->maj();
	}
	

	public function resetPlateau()
	{
		$this->plateau = new Plateau($this);
		$this->init();
	}
	// Lorsque le second joueur rejoint la partie
	public function ajouterJoueur2(Joueur $joueur): void
	{
		$this->joueur2 = $joueur;
		
		// Placer les pièces du joueur 2 maintenant
		$ligneA = [Forme::FEUILLES, Forme::CISEAUX, Forme::PIERRES, 
				Forme::PIERRES, Forme::CISEAUX, Forme::FEUILLES];
		$ligneB = [Forme::CISEAUX, Forme::PIERRES, Forme::FEUILLES, 
				Forme::FEUILLES, Forme::PIERRES, Forme::CISEAUX];
		
		$this->placerLigne(4, $ligneB, $this->joueur2);
		$this->placerLigne(5, $ligneA, $this->joueur2);
		
		$this->maj();
	}

	public function setJoueur2(Joueur $joueur): void
	{
		$this->joueur2 = $joueur;
	}

	public function joueur2Pret(): bool
	{
		return $this->joueur2 !== null;
	}

	/**
	 * Met à jour l'état du plateau (UML: maj()).
	 */
	public function maj(): void
	{
		// Recalculer la portée de toutes les pièces
		$this->plateau->recalculePortee();
		$this->tourDeJeu++;
	}

	/**
	 * Vérifie si un joueur a gagné (UML: verifVictoire()).
	 * @return string|null Retourne 'J1' si joueur1 gagne, 'J2' si joueur2 gagne, null sinon
	 */
	public function verifVictoire(): ?string
	{
		if ($this->joueur1->aPerduUneFamille()) {
			return 'J2';
		}
		if ($this->joueur2->aPerduUneFamille()) {
			return 'J1';
		}

		$doitJouer = $this->getJoueurActif();
		if (!$this->joueurPeutBouger($doitJouer)) {
			return ($doitJouer === $this->joueur1) ? 'J2' : 'J1';
		}

		return null;
	}

	/**
	 * Vérifie si un joueur peut encore bouger au moins une pièce.
	 * @param Joueur $joueur
	 * @return bool true si au moins un mouvement
	 */
	private function joueurPeutBouger(Joueur $joueur): bool
	{
		for ($ligne = 0; $ligne < Plateau::TAILLE; $ligne++) {
			for ($colonne = 0; $colonne < Plateau::TAILLE; $colonne++) {
				$case = $this->plateau->getCase($ligne, $colonne);
				$piece = $case->getPiece();

				if ($piece === null || $piece->getJoueur() !== $joueur) {
					continue;
				}

				if ($piece->getNbCaseDeplacement() === 0) {
					continue; 
				}

				$libres   = $this->getCasesAccessibles($case);
				$attaques = $this->getCasesAccessiblesAttaquable($case);

				if (!empty($libres) || !empty($attaques)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Boucle principale de jeu (UML: boucleJeu()).
	 */
	public function boucleJeu(): void
	{
		$this->init();

		while ($this->verifVictoire() === null) {
			$this->maj();
		}
	}

	// --- LOGIQUE MÉTIER & HELPERS ---

	/**
	 * Gère le déplacement d'une pièce et les conséquences (combat).
	 */
	public function jouerCoup(Piece $piece, int $ligneA, int $colonneA)
	{
		// 1. Vérifier que c'est bien le tour du joueur propriétaire de la pièce
		$joueurActif = $this->getJoueurActif();

		if ($piece->getJoueur() !== $joueurActif) {
			return 'BAD_PLAYER';
		}

		// 2. Vérifier le déplacement
		if (!$piece->verifDeplacement($this->plateau, $ligneA, $colonneA)) {
			return 'INVALID_MOVE';
		}

		// 3. Gestion de la capture
		$caseArrivee = $this->plateau->getCase($ligneA, $colonneA);
		$cible = $caseArrivee->getPiece();

		if ($cible !== null) {
			$joueurCible = $cible->getJoueur();
			if ($piece->getForme()->domine($cible->getForme())) {
				$joueurCible->supprimer($cible);
				$this->plateau->retirerPiece($cible);
			} else {
				return 'BLOCKED_MOVE';
			}
		}

		// 4. Effectuer le déplacement
		$ancienneLigne = $piece->getLigne();
		$ancienneColonne = $piece->getColonne();
		
		$ancienneCase = $this->plateau->getCase($ancienneLigne, $ancienneColonne);
		$ancienneCase->setPiece(null);

		$caseArrivee->setPiece($piece);
		
		$piece->setLigne($ligneA);
		$piece->setColonne($colonneA);

		
		$this->partieService->savePlay(
			$this->tourDeJeu,
			$joueurActif,
			$ancienneCase->getLigne(),
			$ancienneCase->getColonne(),
			$caseArrivee->getLigne(),
			$caseArrivee->getColonne(),
			$cible !== null,
			$this->code
		);

		$this->maj();  
		$this->finTour();

		return 'OK';
	}

	public function deplacerPiece(int $xDepart,int $yDepart,int $xArrive,int $yArrive )
	{
		$case  = $this->plateau->getCase($xDepart,$yDepart);
		$cible  = $this->plateau->getCase($xArrive,$yArrive);
		$piece = $case->getPiece();
		

		if (!$piece) {
			return $this->get_plateau();
		}

		$case->setPiece(null);

		$piece->setLigne($xArrive);
		$piece->setColonne($yArrive);

		$cible->setPiece($piece);

		return $this->get_plateau();
	}

	/**
	 * Permet de placer sur une ligne du plateau une liste de pièce
	 * @param int $ligne indice de la ligne
	 * @param array $formes formes à placer dans l'ordre
	 * @param Joueur $joueur joueur pour déterminer la couleur des pièces
	 * @return void
	 */
	private function placerLigne(int $ligne, array $formes, Joueur $joueur): void
	{
		foreach ($formes as $col => $forme) 
		{
			$p = new Piece($joueur, $forme, $ligne, $col);
			$joueur->ajouterPiece($p);
			$this->plateau->placerPiece($p,$ligne,$col);
		}
	}

	public function finTour(): void
	{
		$this->tour = 1 - $this->tour;
	}

	public function getJoueurActif(): Joueur
	{
		return $this->tour === 0 ? $this->joueur1 : $this->joueur2;
	}

	public function getJoueurInactif(): Joueur
	{
		return $this->tour === 0 ? $this->joueur2 : $this->joueur1;
	}

	public function getCaseJoueurCourant() : array{
		$joueur = $this->getJoueurActif();
		$caseJoueur = [];
		for ($ligne = 0; $ligne < Plateau::TAILLE; $ligne++) {
			for ($colonne = 0; $colonne < Plateau::TAILLE; $colonne++) {
				$case = $this->plateau->getCase($ligne, $colonne);
				if($case->getJoueur() === $joueur && 
					$case->getPiece()->getNbCaseDeplacement() > 0) {
					$caseJoueur[] = $case;
				}
			}
		}
		return $caseJoueur;
	}

	public function getCaseJoueur(Joueur $joueur) : array{
		$caseJoueur = [];
		for ($ligne = 0; $ligne < Plateau::TAILLE; $ligne++) {
			for ($colonne = 0; $colonne < Plateau::TAILLE; $colonne++) {
				$case = $this->plateau->getCase($ligne, $colonne);
				if(!$case->estVide() && $case->getJoueur() === $joueur) {
					$caseJoueur[] = $case;
				}
			}
		}
		return $caseJoueur;
	}

	public function getCasesAccessibles(Cases $case): array
	{
		$plateau = $this->get_plateau();
		$accessibles = [];
		$piece = $case->getPiece();

		if (!$piece) return $accessibles;

		$portee = $piece->getNbCaseDeplacement(); 
		$joueur = $piece->getJoueur();

		$directions = [
			[1,0], [-1,0], [0,1], [0,-1],
			[1,1], [1,-1], [-1,1], [-1,-1]
		];

		foreach ($directions as [$dx, $dy]) {
			for ($step = 1; $step <= $portee; $step++) {
				$x = $case->getLigne() + $dx * $step;
				$y = $case->getColonne() + $dy * $step;

				if ($plateau->estDehors($x, $y)) break;

				$cible = $plateau->getCase($x, $y);
				if (!$cible) continue;

				$pieceCible = $cible->getPiece();

				if ($pieceCible === null) {
					$accessibles[] = $cible;
				}
			}
		}
		return $accessibles;
	}

	public function getCasesAccessiblesAttaquable(Cases $case): array
	{
		$plateau = $this->get_plateau();
		$attaquable = [];
		$piece = $case->getPiece();

		if (!$piece) return $attaquable;

		$portee = $piece->getNbCaseDeplacement(); 
		$joueur = $piece->getJoueur();

		$directions = [
			[1,0], [-1,0], [0,1], [0,-1],
			[1,1], [1,-1], [-1,1], [-1,-1]
		];

		foreach ($directions as [$dx, $dy]) {
			for ($step = 1; $step <= $portee; $step++) {
				$x = $case->getLigne() + $dx * $step;
				$y = $case->getColonne() + $dy * $step;

				if ($plateau->estDehors($x, $y)) break;

				$cible = $plateau->getCase($x, $y);
				if (!$cible) continue;

				$pieceCible = $cible->getPiece();

				if ($pieceCible !== null) {
					if($joueur !== $pieceCible->getJoueur()){
						if($case->getForme()->domine($cible->getForme())){
							$attaquable[] = $cible;
						}
					}
				}
			}
		}
		return $attaquable;
	}

}