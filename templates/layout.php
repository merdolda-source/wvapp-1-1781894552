<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>WebView App Builder</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require TEMPLATES_PATH . '/partials/header.php'; ?>
<main class="container">
    <?php foreach (Flash::pull() as $flash): ?>
        <div class="alert alert-<?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<?php require TEMPLATES_PATH . '/partials/footer.php'; ?>
</body>
</html>
