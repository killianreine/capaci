<div class="modal show waiting-rematch-modal">
    <div class="modal-content">
        <div class="spinner"></div>
        <h2>En attente de réponse...</h2>
        <div class="waiting-content">
            <p class="waiting-text">
                Invitation envoyée à <strong><?= htmlspecialchars($invitation['invited_username']) ?></strong>
            </p>
            <p class="waiting-details">
                En attente de sa réponse...
            </p>
            <p class="timer-info">
                <?php 
                if (!isset($_SESSION['rematch_wait_start'])) {
                    $_SESSION['rematch_wait_start'] = time();
                }
                $elapsed = time() - $_SESSION['rematch_wait_start'];
                ?>
                ⏱️ <strong><?= $elapsed ?>s</strong> écoulées
                <?php if ($elapsed > 60): ?>
                    <br><small class="warning-text">⚠️ Votre adversaire tarde à répondre...</small>
                <?php endif; ?>
            </p>
            <p class="auto-refresh-info">
                <small>Verification automatique toutes les 2 secondes</small>
            </p>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/annuler-rematch">
            <button type="submit" class="btn btn-cancel">
                Annuler l'invitation
            </button>
        </form>
    </div>
</div>

<style>
.waiting-rematch-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease-in;
    left: 50%;
    width: 100%;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.waiting-rematch-modal .modal-content {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 25px;
    padding: 2.5rem;
    width: 90%;
    max-width: 450px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 3px #667eea;
    animation: slideDown 0.4s ease-out;
    position: relative;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.waiting-rematch-modal h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Spinner */
.spinner {
    width: 60px;
    height: 60px;
    margin: 0 auto 1.5rem;
    border: 4px solid #e0e0e0;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Zone de contenu */
.waiting-content {
    margin: 2rem 0;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    border-radius: 15px;
    border: 2px solid rgba(102, 126, 234, 0.1);
}

.waiting-text {
    font-size: 1.2rem;
    color: #333;
    margin: 0.5rem 0;
    font-weight: 600;
}

.waiting-details {
    font-size: 1rem;
    color: #667;
    margin: 1rem 0;
}

.timer-info {
    font-size: 1rem;
    color: #333;
    margin: 1.5rem 0 1rem 0;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 10px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.warning-text {
    color: #ff9800;
    font-weight: 600;
    margin-top: 0.5rem;
    display: inline-block;
}

.auto-refresh-info {
    margin-top: 1rem;
}

.auto-refresh-info small {
    color: #999;
    font-size: 0.85rem;
}

/* Bouton */
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    margin-top: 1rem;
}

.btn-cancel {
    background: white;
    color: #e74c3c;
    border: 2px solid #e74c3c;
}

.btn-cancel:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Animation du timer qui pulse */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.timer-info strong {
    animation: pulse 2s ease-in-out infinite;
}
</style>

<script>
(() => {
    const endpoint = <?= json_encode(BASE_URL . '/check-rematch-status') ?>;
    let checking = false;

    const checkInvitation = async () => {
        if (checking || document.hidden) return;
        checking = true;
        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const state = await response.json();
            if (state.status === 'ACCEPTER') {
                window.location.assign(<?= json_encode(BASE_URL . '/creer-partie') ?>);
            } else if (state.status === 'REFUSER' || state.status === 'TIMEOUT') {
                window.location.assign(<?= json_encode(BASE_URL . '/home') ?>);
            }
        } catch (error) {
            console.warn('Verification du rematch impossible', error);
        } finally {
            checking = false;
        }
    };

    checkInvitation();
    window.setInterval(checkInvitation, 2000);
})();
</script>