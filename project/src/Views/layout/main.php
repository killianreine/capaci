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

</body>
</html>
