<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Capaci' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base/layout.css">

    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $style ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</head>
<body>

<?php require __DIR__ . '/header.php'; ?>

<main>
    <?= $content ?>
</main>

<?php require __DIR__.'/footer.php'; ?>

<script>
(() => {
    const baseUrl = <?= json_encode(BASE_URL) ?>;
    let gamePolling = null;
    let updatingMain = false;

    const replaceMain = (html) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const nextMain = parsed.querySelector('main');
        const currentMain = document.querySelector('main');
        if (!nextMain || !currentMain) return;
        currentMain.innerHTML = nextMain.innerHTML;
        startGamePolling();
    };

    const refreshMain = async () => {
        if (updatingMain) return;
        updatingMain = true;
        try {
            const response = await fetch(window.location.href, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            replaceMain(await response.text());
        } catch (error) {
            console.warn('Mise a jour de la partie impossible', error);
        } finally {
            updatingMain = false;
        }
    };

    const submitCase = async (form) => {
        if (updatingMain) return;
        updatingMain = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            replaceMain(await response.text());
        } catch (error) {
            console.warn('Action de jeu impossible', error);
        } finally {
            updatingMain = false;
        }
    };

    const startGamePolling = () => {
        if (gamePolling) window.clearInterval(gamePolling);
        gamePolling = null;
        if (!document.querySelector('.plateau')) return;

        let checking = false;
        gamePolling = window.setInterval(async () => {
            if (checking || updatingMain || document.hidden) return;
            checking = true;
            try {
                const response = await fetch(baseUrl + '/partie/get-state', {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' }
                });
                const state = await response.json();
                const currentDate = document.querySelector('[data-game-date]')?.dataset.gameDate;
                if (!state.error && currentDate && state.dateModif !== currentDate) {
                    await refreshMain();
                }
            } catch (error) {
                console.warn('Verification de la partie impossible', error);
            } finally {
                checking = false;
            }
        }, 2000);
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form.case-form');
        if (!form) return;
        event.preventDefault();
        submitCase(form);
    });

    startGamePolling();
})();
</script>

</body>
</html>
