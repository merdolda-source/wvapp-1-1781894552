<?php
$siteName = 'CreatorApp24';
$baseUrl = rtrim((string) Config::get('APP_URL'), '/');

$pageTitle = $pageTitle ?? "{$siteName} — Web Sitenizi Android Uygulamasına Dönüştürün";
$pageDescription = $pageDescription ?? "{$siteName} ile web sitenizi birkaç dakikada imzalı Android APK ve AAB uygulamasına dönüştürün, Play Store'a yükleyin. Kullanıcı başına 5 uygulama, tamamen ücretsiz.";
$noIndex = $noIndex ?? false;

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$canonicalUrl = $baseUrl !== '' ? $baseUrl . $requestPath : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= View::e($pageTitle) ?></title>
<meta name="description" content="<?= View::e($pageDescription) ?>">
<meta name="robots" content="<?= $noIndex ? 'noindex, nofollow' : 'index, follow' ?>">
<?php if ($canonicalUrl !== ''): ?>
<link rel="canonical" href="<?= View::e($canonicalUrl) ?>">
<?php endif; ?>

<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= View::e($siteName) ?>">
<meta property="og:title" content="<?= View::e($pageTitle) ?>">
<meta property="og:description" content="<?= View::e($pageDescription) ?>">
<meta property="og:locale" content="tr_TR">
<?php if ($canonicalUrl !== ''): ?>
<meta property="og:url" content="<?= View::e($canonicalUrl) ?>">
<meta property="og:image" content="<?= View::e($baseUrl) ?>/assets/og-image.png">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= View::e($pageTitle) ?>">
<meta name="twitter:description" content="<?= View::e($pageDescription) ?>">
<?php if ($baseUrl !== ''): ?>
<meta name="twitter:image" content="<?= View::e($baseUrl) ?>/assets/og-image.png">
<?php endif; ?>

<?php $cssVersion = @filemtime(BASE_PATH . '/assets/css/style.css') ?: time(); ?>
<link rel="stylesheet" href="/assets/css/style.css?v=<?= $cssVersion ?>">

<?php if (!$noIndex): ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => $siteName,
    'url' => $baseUrl,
    'description' => "Web sitenizi ücretsiz olarak imzalı Android APK ve AAB uygulamasına dönüştüren, kullanıcı başına 5 uygulamaya kadar destekleyen platform.",
    'applicationCategory' => 'DeveloperApplication',
    'operatingSystem' => 'Android',
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'TRY',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php endif; ?>
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
