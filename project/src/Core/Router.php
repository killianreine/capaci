<?php

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PartieController;
use App\Controllers\ProfileController;
use App\Controllers\ReplayController;

class Router
{
	public function handleRequest(): void
	{
		$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
		$replayControleur = null;
		
		$uri = $_SERVER['REQUEST_URI'] ?? '/';
		$uri = parse_url($uri, PHP_URL_PATH);
		if ($basePath !== '' && str_starts_with($uri, $basePath)) {
			$uri = substr($uri, strlen($basePath));
		}
		
		if (empty($uri) || $uri === '/') {
			$uri = '/';
		}

		if (preg_match('#^/partie/([0-9]+)$#', $uri, $matches)) {
			(new ReplayController((int) $matches[1]))->show();
			return;
		}

		switch ($uri) {
			case '/':
			case '/home':
				(new HomeController())->index();
				break;

			case '/login':
				$controller = new AuthController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->login();
				} else {
					$controller->showLogin();
				}
				break;

			case '/register':
				$controller = new AuthController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->register();
				} else {
					$controller->showRegister();
				}
				break;

			case '/logout':
				(new AuthController())->logout();
				break;

			case '/creer-partie':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					if (isset($_POST['type_partie'])) {
						$controller->show();
					} else {
						$controller->play();
					}
				} else {
					$controller->show();
				}
				break;

			case '/replay/action':
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$partieId = $_POST['partie_id'];
					$controller = new ReplayController($partieId);
					$controller->action();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/partie/toggle-visibility':
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					(new PartieController())->toggleVisibility();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/mon-compte':
				(new ProfileController())->show();
				break;

			case '/mon-compte/update':
				(new ProfileController())->update();
				break;

			case '/mon-compte/parties':
				(new ProfileController())->show('parties');
				break;

			case '/creer-rematch':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->createRematch();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/recreer-partie':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->recreatePartie();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/attente-rematch':
				(new PartieController())->showWaitingRematch();
				break;

			case '/accepter-rematch':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->acceptRematch();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/refuser-rematch':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->refuseRematch();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;
			
				case '/annuler-rematch':
					$controller = new PartieController();
					if ($_SERVER['REQUEST_METHOD'] === 'POST') {
						$controller->cancelRematch();
					} else {
						header('Location: ' . BASE_URL . '/home');
						exit;
					}
					break;

			case '/check-rematch-status':
				(new PartieController())->checkRematchStatus();
				break;

			case '/nouvelle-partie':
				(new PartieController())->create();
				break;

			case '/rejoindre-partie':
				$controller = new PartieController();
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$controller->joinPartie();
				} else {
					header('Location: ' . BASE_URL . '/home');
					exit;
				}
				break;

			case '/check-player-joined':
				(new PartieController())->checkPlayerJoined();
				break;

			case '/cancel-partie':
			case '/partie/cancel':
				(new PartieController())->cancelPartie();
				break;

			case '/partie/get-state':
				(new PartieController())->getGameState();
				break;

			case '/quitter-partie':
				(new PartieController())->quitterPartie();
				break;

			default:
			http_response_code(404);
			echo "<h1>404 - Page introuvable</h1><p>La route <code>" . htmlspecialchars($uri) . "</code> n'existe pas.</p>";
			exit;
		}
	}
}