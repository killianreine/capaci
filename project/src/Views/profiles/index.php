<div class="profile-container">

	<div class="menu">
		<a href="<?= BASE_URL ?>/mon-compte"
		class="menu-link <?= $tab === 'infos' ? 'active' : '' ?>">
			Informations générales
		</a>

		<a href="<?= BASE_URL ?>/mon-compte/parties"
		class="menu-link <?= $tab === 'parties' ? 'active' : '' ?>">
			Anciennes parties
		</a>
	</div>

   <div class="info">

		<?php if ($tab === 'infos'): ?>
		<div class="info-left">

			<div class="title">Informations générales</div>

			<form method="POST" action="<?= BASE_URL ?>/mon-compte/update" class="profile-form">

				<div class="form-group">
					<label>Email</label>
					<input type="email" name="users_email" value="<?= htmlspecialchars($users['users_email']) ?>">
				</div>

				<div class="form-group">
					<label>Nom d'utilisateur</label>
					<input type="text" name="users_username" value="<?= htmlspecialchars($users['users_username']) ?>">
				</div>

				<hr>

				<div class="form-group">
					<label>Nouveau mot de passe</label>
					<input type="password" name="new_password">
				</div>

				<div class="form-group">
					<label>Confirmer le mot de passe</label>
					<input type="password" name="confirm_password">
				</div>

				<button type="submit" class="save-btn">
					Enregistrer les modifications
				</button>

			</form>

		</div>


		<div class="info-right">

			<div class="victory-card">
				<img src="<?= BASE_URL ?>/assets/images/coupe.png" class="trophy">

				<div class="victory-number">
									<?= $users['users_nbvictoire'] ?>
				</div>

				<div class="victory-label">
					Victoires
				</div>
			</div>

		</div>

		<?php elseif ($tab === 'parties'): ?>

		<div class="parties-section">
			<h2>Anciennes parties</h2>

			<?php foreach ($parties as $partie): ?>
				<a href="<?= BASE_URL ?>/partie/<?= $partie['partie_id'] ?>" class="partie-card-link">
					<div class="partie-card">

						<div class="partie-header">
							<span class="partie-date">
								<?= date('d/m/Y H:i', strtotime($partie['date_creation'])) ?>
							</span>

							<span class="partie-status <?= $partie['public'] ? 'public' : 'private' ?>">
								<?= $partie['public'] ? '🌍 Publique' : '🔒 Privée' ?>
							</span>
						</div>

						<div class="partie-body">
							<div class="joueurs">
								<span class="joueur">
									👤 <?= htmlspecialchars($partie['username1']) ?>
								</span>

								<span class="versus">VS</span>

								<span class="joueur">
									👤 <?= htmlspecialchars($partie['username2']) ?>
								</span>
							</div>

							<div class="resultat">
								<?php
									if ($partie['gagnant'] == 0) {
										echo "🤝 Égalité";
									} elseif ($partie['gagnant'] == 1) {
										echo "🏆 Victoire de " . htmlspecialchars($partie['username1']);
									} else {
										echo "🏆 Victoire de " . htmlspecialchars($partie['username2']);
									}
								?>
							</div>
						</div>
					</div>
			</a>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	</div>

</div>
