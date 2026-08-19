<?php
namespace App\Controllers;

use App\Config\Database;
use App\Services\ProfilesServices;
use App\Core\View;
use App\Middleware\AuthMiddleware;

class ProfileController
{
	private ProfilesServices $service;

	public function __construct()
	{
		$this->service = new ProfilesServices();
	}

	public function show(string $tab = 'infos')
	{
		AuthMiddleware::requireAuth();
			
		$isConnected = AuthMiddleware::isConnect();
		$users = $this->service->getUsers($_SESSION['users_id']);

		$parties = null;

		// On charge les parties seulement si nécessaire
		if ($tab === 'parties') {
			$parties = $this->service->getUserParties($_SESSION['users_id']);

		}

		View::render("profiles/index",
		[
			'users'       => $users,
			'isConnected' => $isConnected,
			'tab'         => $tab,
			'parties'     => $parties,
			'styles'      => ['/base/header.css','/pages/profiles.css']
		]);
	}

   public function update()
	{
		if (!isset($_SESSION['users_id'])) {
			header('Location: /login');
			exit;
		}

		$email            = $_POST['users_email'] ?? null;
		$username         = $_POST['users_username'] ?? null;
		$newPassword      = $_POST['new_password'] ?? null;
		$confirmPassword  = $_POST['confirm_password'] ?? null;

		$email    = trim($email);
		$username = trim($username);

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			die("Email invalide");
		}

		$hashedPassword = null;

		if (!empty($newPassword)) {

			if ($newPassword !== $confirmPassword) {
				die("Les mots de passe ne correspondent pas");
			}

			if (strlen($newPassword) < 6) {
				die("Mot de passe trop court");
			}

			$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
		}


		$this->service->update(
			$_SESSION['users_id'],
			$email,
			$username,
			$hashedPassword
		);

		header('Location: ' . BASE_URL . '/mon-compte');
		exit;
	}


}