<?php 
$currentCode = $codePartie ?? null;
$isVisible   = $isPublic ?? false; 
?>

<?php if (isset($waitingForPlayer) && $waitingForPlayer && $currentCode): ?>
<div class="modal-waiting show" id="waitingModal">
    <div class="modal-content-waiting">
        <div class="loader-container">
            <div class="loader"></div>
        </div>

        <h2>En attente d'un adversaire</h2>

        <?php if (!$isVisible): ?>
        <div class="code-display">
            <p class="code-label">Partagez ce code à votre adversaire :</p>
            <div class="code-box">
                <input
                    class="code-value-input"
                    type="text"
                    value="<?= htmlspecialchars((string)$currentCode) ?>"
                    readonly
                    onclick="this.select()">
            </div>
            <p class="code-hint">Cliquez sur le code pour le sélectionner, puis Ctrl+C</p>
        </div>
        <?php endif; ?>

        <div class="player-status">
            <div class="player-box connected">
                <span class="player-icon">👤</span>
                <span class="player-name"><?= htmlspecialchars($joueur1Name ?? 'Moi') ?></span>
                <span class="status-badge">Connecté</span>
            </div>
            <div class="vs-separator">VS</div>
            <div class="player-box waiting">
                <span class="player-icon">👤</span>
                <span class="player-name">En attente...</span>
                <span class="status-badge">Recherche</span>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/cancel-partie">
            <button type="submit" class="btn-cancel-waiting">Annuler la partie</button>
        </form>

    </div>
</div>
<script>
(() => {
    const endpoint = <?= json_encode(BASE_URL . '/check-player-joined') ?>;
    const destination = <?= json_encode(BASE_URL . '/creer-partie') ?>;
    let checking = false;

    const checkPlayer = async () => {
        if (checking || document.hidden) return;
        checking = true;
        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const state = await response.json();
            if (state.joined) window.location.assign(destination);
        } catch (error) {
            console.warn('Verification du joueur impossible', error);
        } finally {
            checking = false;
        }
    };

    checkPlayer();
    window.setInterval(checkPlayer, 2000);
})();
</script>
<?php endif; ?>