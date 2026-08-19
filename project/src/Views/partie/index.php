<?php
/** @var \App\Models\Classes\Jeu $jeu */

$joueur1Name = $jeu->getJoueur1() ? $jeu->getJoueur1()->getNom() : null;
$joueur2Name = $jeu->getJoueur2() ? $jeu->getJoueur2()->getNom() : null;

require __DIR__ . '/game_over_modal.php';
require __DIR__ . '/waiting_modal.php';

$plateau = $jeu->get_plateau();
$joueurCourant = $jeu->getJoueurActif();

use App\Models\Classes\Cases;
use App\Models\Classes\Plateau;

$depart = $_SESSION['depart'] ?? null;

$casesAccessibles = [];
$casesAttaquable = [];
$pieceSelectionnee = isset($depart);

if ($depart) {
	$caseDepart = $plateau->getCase($depart['ligne'], $depart['colonne']);
	$casesAccessibles = $jeu->getCasesAccessibles($caseDepart);
	$casesAttaquable = $jeu->getCasesAccessiblesAttaquable($caseDepart);
}

$currentUsername = null;
if (isset($currentUserId)) {
	$users = \App\Config\Database::select('users', ['users_username'], ['users_id' => $currentUserId]);
	if (!empty($users)) {
		$currentUsername = $users[0]['users_username'];
	}
}

$isGameOver = isset($gameOver) && $gameOver !== null;
$isPartieEnCours = isset($gameState) && $gameState['etat'] === 'EN COURS';
?>

<?php if (!$waitingForPlayer && !$isGameOver && $isPartieEnCours): ?>
<div data-game-date="<?= htmlspecialchars((string)($gameState['dateModif'] ?? '')) ?>" hidden></div>
<?php endif; ?>

<?php if (!$waitingForPlayer && !$isGameOver): ?>
<div class="turn-banner <?= (isset($isMyTurn) && $isMyTurn) ? 'my-turn' : 'their-turn' ?>">
	<?php if (isset($isMyTurn) && $isMyTurn): ?>
		C'est votre tour !
	<?php else: ?>
		Tour de <strong><?= htmlspecialchars($joueurCourant->getNom()) ?></strong>
		<span class="refresh-hint">— actualisation dans 3s...</span>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php

function genererCaseVide(Cases $case, array $casesAccessibles = [], bool $pieceSelectionnee = false, bool $isMyTurn = true): string
{
	$classe = "empty";
	$isAccessible = false;
	
	foreach ($casesAccessibles as $c) {
		if ($c->getLigne() === $case->getLigne() && $c->getColonne() === $case->getColonne()) {
			$classe .= " case-accessible";
			$isAccessible = true;
			break;
		}
	}

	$disabledAttr = (!$isMyTurn || !$pieceSelectionnee || !$isAccessible) ? 'disabled' : '';
	if (!$isMyTurn || !$pieceSelectionnee || !$isAccessible) {
		$classe .= ' case-disabled';
	}

	return sprintf(
		'<form method="POST" action="%s" class="case-form">
			<button class="%s" value="%d,%d" name="case" type="submit" %s></button>
		</form>',
		BASE_URL.'/creer-partie',
		$classe,
		$case->getLigne(),
		$case->getColonne(),
		$disabledAttr
	);
}

function genererCasePiece(Cases $case, bool $selected, array $casesAttaquable = [], bool $estJoueurCourant = false, bool $pieceSelectionnee = false, bool $isMyTurn = true): string
{   
	$piece = $case->getPiece();
	$type = strtolower($piece->getForme()->name);
	$couleur = strtolower($piece->getJoueur()->getCouleur());
	$cheminImage = BASE_URL . "/assets/pieces/{$type}/{$couleur}/{$type}.svg";
	
	$class = $selected ? 'case selected' : 'case';
	$peutSeDeplacer = $piece->getNbCaseDeplacement() > 0;

	if ($estJoueurCourant) $class .= ' case-joueur';
	if (!$peutSeDeplacer && $estJoueurCourant) $class .= ' case-sans-portee';

	$isAttaquable = false;
	foreach ($casesAttaquable as $c) {
		if ($c->getLigne() === $case->getLigne() && $c->getColonne() === $case->getColonne()) {
			$class .= ' case-attaquable';
			$isAttaquable = true;
			break;
		}
	}

	$disabled = '';
	if (!$isMyTurn) {
		$disabled = 'disabled';
		$class .= ' case-disabled';
	} elseif (!$pieceSelectionnee) {
		if (!$estJoueurCourant || !$peutSeDeplacer) {
			$disabled = 'disabled';
			$class .= ' case-disabled';
		}
	} else {
		if (!$selected && !$isAttaquable) {
			$disabled = 'disabled';
			$class .= ' case-disabled';
		}
	}

	return sprintf(
		'<form method="POST" action="%s" class="case-form">
			<button class="%s" type="submit" name="case" value="%d,%d" %s>
				<img src="%s" alt="%s %s">
			</button>
		</form>',
		BASE_URL.'/creer-partie',
		$class,
		$case->getLigne(),
		$case->getColonne(),
		$disabled,
		$cheminImage,
		$couleur,
		$type
	);
}

function createBoardGame($jeu, $depart, $isMyTurn = true): string
{
	$plateau = $jeu->get_plateau();
	$joueurCourant = $jeu->getJoueurActif();
	$res = "";

	$casesAccessibles = [];
	$casesAttaquable = [];
	$pieceSelectionnee = isset($depart);

	if (isset($depart)) {
		$caseDepart = $plateau->getCase($depart['ligne'], $depart['colonne']);
		$casesAccessibles = $jeu->getCasesAccessibles($caseDepart);
		$casesAttaquable = $jeu->getCasesAccessiblesAttaquable($caseDepart);
	}

	$res .= '<div class="plateau">';

	for ($ligne = 0; $ligne < Plateau::TAILLE; $ligne++) {
		for ($colonne = 0; $colonne < Plateau::TAILLE; $colonne++) {
			$case = $plateau->getCase($ligne, $colonne);
			$piece = $case->getPiece();

			$selected = false;
			$estJoueurCourant = false;

			if ($piece) {
				$estJoueurCourant = ($piece->getJoueur()->getNom() === $joueurCourant->getNom());
				if (isset($depart)) {
					$selected = ($piece->getLigne() === $depart['ligne'] && $piece->getColonne() === $depart['colonne']);
				}
				$res .= genererCasePiece($case, $selected, $casesAttaquable, $estJoueurCourant, $pieceSelectionnee, $isMyTurn);
			} else {
				$res .= genererCaseVide($case, $casesAccessibles, $pieceSelectionnee, $isMyTurn);
			}
		}	
	}

	$res .= '</div>';
	return $res;
}

function createInfo($joueur): string
{
    $username = htmlspecialchars($joueur->getNom());
    $pierre   = $joueur->getNbPierre();
    $feuille  = $joueur->getNbFeuille();
    $ciseaux  = $joueur->getNbCiseau();
    $couleur  = $joueur->getCouleur();
    $class    = "player-card {$couleur}";
    $elo      = $joueur->getElo();
    $base     = BASE_URL . "/assets/pieces";

    return "
    <div class='{$class}'>
        <div class='player-header'>
            <span class='player-name'>{$username}</span>
            <br/><span class='player-elo'>ELO : {$elo}</span>
        </div>
        <div class='player-stats'>
            <div class='stat pierre'>
                <img src='{$base}/pierres/{$couleur}/pierres.svg' alt='Pierre {$couleur}'>
                <span>{$pierre}</span>
            </div>
            <div class='stat feuille'>
                <img src='{$base}/feuilles/{$couleur}/feuilles.svg' alt='Feuille {$couleur}'>
                <span>{$feuille}</span>
            </div>
            <div class='stat ciseaux'>
                <img src='{$base}/ciseaux/{$couleur}/ciseaux.svg' alt='Ciseaux {$couleur}'>
                <span>{$ciseaux}</span>
            </div>
        </div>
    </div>
    ";
}
?>

<div class="page-border <?= (!$waitingForPlayer && !$isGameOver && isset($isMyTurn) && $isMyTurn) ? 'border-my-turn' : ((!$waitingForPlayer && !$isGameOver) ? 'border-their-turn' : '') ?>"></div>

<div class="content">
	<div class="game">
		<?= createInfo($joueur1); ?>
		<?= createBoardGame($jeu, $depart, $isMyTurn ?? true); ?>
		<?= createInfo($joueur2); ?>
	</div>
</div>

<form method="POST" action="<?= BASE_URL ?>/quitter-partie" style="position: fixed; bottom: 24px; right: 24px; z-index: 999;">
	<button type="submit" style="
		background: #e53935;
		color: white;
		border: none;
		padding: 12px 22px;
		border-radius: 8px;
		font-size: 14px;
		font-weight: bold;
		cursor: pointer;
		box-shadow: 0 4px 10px rgba(0,0,0,0.2);
		display: flex;
		align-items: center;
		gap: 8px;
		transition: background 0.2s;
	"
	onmouseover="this.style.background='#b71c1c'"
	onmouseout="this.style.background='#e53935'">
		Quitter la partie
	</button>
</form>