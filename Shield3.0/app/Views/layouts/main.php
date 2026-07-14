<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title ?? 'Kenexoft SHIELD') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $escape($asset('public/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= $escape($asset('public/assets/css/app.css')) ?>">
</head>
<body>
    <?= $content ?>
    <?php foreach (($scripts ?? ['public/assets/js/app.js']) as $script): ?>
        <script src="<?= $escape($asset((string) $script)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
