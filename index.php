<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$router = new Router();

// ---------------------------------------------------------------- Landing

$router->get('/', function (): void {
    if (Auth::check()) {
        header('Location: /dashboard');
        exit;
    }
    View::render('home', []);
});

$router->get('/robots.txt', function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    $baseUrl = rtrim((string) Config::get('APP_URL'), '/');
    echo "User-agent: *\n";
    echo "Disallow: /dashboard\n";
    echo "Disallow: /apps/\n";
    echo "Disallow: /account\n";
    echo "Disallow: /stats/\n";
    echo "Disallow: /admin\n";
    echo "Disallow: /auth/\n";
    echo "Allow: /\n";
    if ($baseUrl !== '') {
        echo "Sitemap: {$baseUrl}/sitemap.xml\n";
    }
});

$router->get('/sitemap.xml', function (): void {
    header('Content-Type: application/xml; charset=utf-8');
    $baseUrl = rtrim((string) Config::get('APP_URL'), '/');

    $urls = [
        ['loc' => $baseUrl . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => $baseUrl . '/register', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => $baseUrl . '/login', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ];

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $url) {
        echo '  <url>' . "\n";
        echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc>' . "\n";
        echo '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
        echo '    <priority>' . $url['priority'] . '</priority>' . "\n";
        echo '  </url>' . "\n";
    }
    echo '</urlset>';
});

// Public - no login required, so Play Store / users can open it without an account.
$router->get('/privacy/{packageId}', function (string $packageId): void {
    $app = AppProject::findByPackageId($packageId);

    if ($app === null) {
        http_response_code(404);
        View::render('errors/404', []);
        return;
    }

    View::render('privacy', ['app' => $app]);
});

// ---------------------------------------------------------------- Auth

$router->get('/register', function (): void {
    if (Auth::check()) {
        header('Location: /dashboard');
        exit;
    }
    View::render('auth/register', ['googleEnabled' => GoogleOAuth::isConfigured()]);
});

$router->post('/register', function (): void {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.');
        header('Location: /register');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        Flash::set('error', 'Lütfen adınızı, geçerli bir e-posta adresi ve en az 6 karakterli bir şifre girin.');
        header('Location: /register');
        exit;
    }

    if (User::findByEmail($email) !== null) {
        Flash::set('error', 'Bu e-posta adresi zaten kayıtlı.');
        header('Location: /register');
        exit;
    }

    $user = User::create($name, $email, $password);
    Auth::login($user);
    header('Location: /dashboard');
    exit;
});

$router->get('/login', function (): void {
    if (Auth::check()) {
        header('Location: /dashboard');
        exit;
    }
    View::render('auth/login', ['googleEnabled' => GoogleOAuth::isConfigured()]);
});

$router->post('/login', function (): void {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.');
        header('Location: /login');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $user = User::findByEmail($email);

    if ($user === null || !User::verifyPassword($user, $password)) {
        Flash::set('error', 'E-posta veya şifre hatalı.');
        header('Location: /login');
        exit;
    }

    Auth::login($user);
    header('Location: /dashboard');
    exit;
});

$router->post('/logout', function (): void {
    Auth::logout();
    header('Location: /');
    exit;
});

$router->get('/auth/google', function (): void {
    if (!GoogleOAuth::isConfigured()) {
        Flash::set('error', 'Google ile giriş şu anda kullanılamıyor.');
        header('Location: /login');
        exit;
    }
    header('Location: ' . GoogleOAuth::authorizeUrl());
    exit;
});

$router->get('/auth/google/callback', function (): void {
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;

    if (!is_string($code) || !is_string($state)) {
        Flash::set('error', 'Google girişi tamamlanamadı.');
        header('Location: /login');
        exit;
    }

    $profile = GoogleOAuth::handleCallback($code, $state);
    if ($profile === null) {
        Flash::set('error', 'Google girişi doğrulanamadı. Lütfen tekrar deneyin.');
        header('Location: /login');
        exit;
    }

    $user = User::findByGoogleId($profile['id']);
    if ($user === null) {
        $user = User::findByEmail($profile['email']);
        if ($user !== null) {
            User::linkGoogleAccount((int) $user['id'], $profile['id'], $profile['picture']);
            $user = User::find((int) $user['id']);
        } else {
            $user = User::create($profile['name'], $profile['email'], null, $profile['id'], $profile['picture']);
        }
    }

    Auth::login($user);
    header('Location: /dashboard');
    exit;
});

// ---------------------------------------------------------------- Dashboard

$router->get('/dashboard', function (): void {
    Auth::requireLogin();
    $user = Auth::user();
    $apps = AppProject::allForUser((int) $user['id']);
    $maxApps = (int) Config::get('APP_MAX_APPS_PER_USER', (string) AppProject::MAX_PER_USER_DEFAULT);

    View::render('dashboard/index', [
        'apps' => $apps,
        'maxApps' => $maxApps,
        'canCreate' => count($apps) < $maxApps,
    ]);
});

// ---------------------------------------------------------------- Apps

$router->get('/apps/new', function (): void {
    Auth::requireLogin();
    $user = Auth::user();
    $maxApps = (int) Config::get('APP_MAX_APPS_PER_USER', (string) AppProject::MAX_PER_USER_DEFAULT);

    if (AppProject::countForUser((int) $user['id']) >= $maxApps) {
        Flash::set('error', "En fazla {$maxApps} uygulama oluşturabilirsiniz.");
        header('Location: /dashboard');
        exit;
    }

    View::render('apps/form', ['app' => null, 'fonts' => Fonts::OPTIONS]);
});

$router->post('/apps', function (): void {
    Auth::requireLogin();
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.');
        header('Location: /apps/new');
        exit;
    }

    $user = Auth::user();
    $maxApps = (int) Config::get('APP_MAX_APPS_PER_USER', (string) AppProject::MAX_PER_USER_DEFAULT);

    if (AppProject::countForUser((int) $user['id']) >= $maxApps) {
        Flash::set('error', "En fazla {$maxApps} uygulama oluşturabilirsiniz.");
        header('Location: /dashboard');
        exit;
    }

    [$fields, $errors] = validate_app_input($_POST);

    $customPackageId = trim($_POST['package_id'] ?? '');
    if ($customPackageId !== '') {
        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $customPackageId)) {
            $errors[] = 'Paket adı "com.siteniz.uygulama" biçiminde, küçük harflerle, en az iki bölümden oluşmalıdır.';
        } elseif (AppProject::packageIdExists($customPackageId)) {
            $errors[] = 'Bu paket adı zaten kullanılıyor, başka bir tane seçin.';
        }
    }

    if (!empty($errors)) {
        Flash::set('error', implode(' ', $errors));
        header('Location: /apps/new');
        exit;
    }

    if ($customPackageId !== '') {
        $fields['package_id'] = $customPackageId;
    } else {
        $fields['package_id'] = AppProject::suggestPackageId($fields['name']);
        while (AppProject::packageIdExists($fields['package_id'])) {
            $fields['package_id'] = AppProject::suggestPackageId($fields['name']);
        }
    }

    $fields['icon_path'] = handle_icon_upload();

    $app = AppProject::create((int) $user['id'], $fields);
    Flash::set('success', 'Uygulama oluşturuldu. Şimdi ilk yayını başlatabilirsiniz.');
    header('Location: /apps/' . $app['id']);
    exit;
});

$router->get('/apps/{id}', function (string $id): void {
    Auth::requireLogin();
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app === null) {
        http_response_code(404);
        View::render('errors/404', []);
        return;
    }

    View::render('apps/show', [
        'app' => $app,
        'fonts' => Fonts::OPTIONS,
        'builds' => Build::historyForApp((int) $app['id']),
        'githubConfigured' => (new GitHubBuildService())->isConfigured(),
    ]);
});

$router->post('/apps/{id}/update', function (string $id): void {
    Auth::requireLogin();
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app === null || !Csrf::verify($_POST['csrf_token'] ?? null)) {
        header('Location: /dashboard');
        exit;
    }

    [$fields, $errors] = validate_app_input($_POST, requirePackageCheck: false);

    if (!empty($errors)) {
        Flash::set('error', implode(' ', $errors));
        header('Location: /apps/' . $app['id']);
        exit;
    }

    $newIcon = handle_icon_upload();
    if ($newIcon !== null) {
        $fields['icon_path'] = $newIcon;
    }

    AppProject::update((int) $app['id'], $fields);
    Flash::set('success', 'Ayarlar kaydedildi. Web adresi, renkler ve splash ayarları birkaç saniye içinde otomatik yansır; uygulama adını veya ikonunu değiştirdiyseniz yeni bir sürüm yayınlamanız gerekir.');
    header('Location: /apps/' . $app['id']);
    exit;
});

$router->post('/apps/{id}/build', function (string $id): void {
    Auth::requireLogin();
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app === null || !Csrf::verify($_POST['csrf_token'] ?? null)) {
        header('Location: /dashboard');
        exit;
    }

    $service = new GitHubBuildService();
    if (!$service->isConfigured()) {
        Flash::set('error', 'Yayınlama sistemi henüz yapılandırılmamış. Lütfen sunucudaki .env dosyasındaki API ayarlarını tamamlayın.');
        header('Location: /apps/' . $app['id']);
        exit;
    }

    $isNewVersion = ($app['status'] === 'ready' || $app['status'] === 'failed');
    $service->triggerBuild($app, $isNewVersion);

    Flash::set('success', 'Yayınlama başlatıldı. Bu birkaç dakika sürebilir, sayfa otomatik olarak güncellenecektir.');
    header('Location: /apps/' . $app['id']);
    exit;
});

$router->get('/apps/{id}/status', function (string $id): void {
    Auth::requireLogin();
    header('Content-Type: application/json');
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app === null) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }

    $build = Build::latestForApp((int) $app['id']);
    $service = new GitHubBuildService();

    if ($build !== null && in_array($build['status'], ['queued', 'building'], true) && $service->isConfigured()) {
        $build = $service->refresh($build, $app);
        $app = AppProject::find((int) $app['id']);
    }

    echo json_encode([
        'app_status' => $app['status'],
        'build_status' => $build['status'] ?? null,
        'version_name' => $app['version_name'],
    ]);
});

$router->get('/apps/{id}/download/{type}', function (string $id, string $type): void {
    Auth::requireLogin();
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app === null || !in_array($type, ['apk', 'aab'], true)) {
        http_response_code(404);
        exit('Bulunamadı');
    }

    $build = Build::latestForApp((int) $app['id']);
    $path = $type === 'apk' ? ($build['apk_path'] ?? null) : ($build['aab_path'] ?? null);

    if ($path === null) {
        http_response_code(404);
        exit('Dosya bulunamadı');
    }

    $fullPath = BASE_PATH . '/storage/builds/' . $path;
    if (!is_file($fullPath)) {
        http_response_code(404);
        exit('Dosya bulunamadı');
    }

    DownloadLog::log((int) $app['id'], $type);

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
});

$router->post('/apps/{id}/delete', function (string $id): void {
    Auth::requireLogin();
    $user = Auth::user();
    $app = AppProject::findForUser((int) $id, (int) $user['id']);

    if ($app !== null && Csrf::verify($_POST['csrf_token'] ?? null)) {
        AppProject::delete((int) $app['id']);
        Flash::set('success', 'Uygulama silindi.');
    }

    header('Location: /dashboard');
    exit;
});

// ---------------------------------------------------------------- Account

$router->get('/account', function (): void {
    Auth::requireLogin();
    View::render('account', ['user' => Auth::user()]);
});

$router->post('/account/update', function (): void {
    Auth::requireLogin();
    $user = Auth::user();

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        header('Location: /account');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        Flash::set('error', 'Ad 2-120 karakter olmalıdır.');
        header('Location: /account');
        exit;
    }

    User::updateName((int) $user['id'], $name);
    Flash::set('success', 'Ad güncellendi.');
    header('Location: /account');
    exit;
});

$router->post('/account/password', function (): void {
    Auth::requireLogin();
    $user = Auth::user();

    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        header('Location: /account');
        exit;
    }

    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($user['password_hash'] !== null && !User::verifyPassword($user, $current)) {
        Flash::set('error', 'Mevcut şifre yanlış.');
        header('Location: /account');
        exit;
    }

    if (strlen($new) < 6) {
        Flash::set('error', 'Yeni şifre en az 6 karakter olmalıdır.');
        header('Location: /account');
        exit;
    }

    if ($new !== $confirm) {
        Flash::set('error', 'Yeni şifreler eşleşmiyor.');
        header('Location: /account');
        exit;
    }

    User::updatePassword((int) $user['id'], $new);
    Flash::set('success', 'Şifre güncellendi.');
    header('Location: /account');
    exit;
});

// ---------------------------------------------------------------- Stats (own apps)

$router->get('/stats/downloads', function (): void {
    Auth::requireLogin();
    $user = Auth::user();

    View::render('stats/downloads', [
        'daily' => DownloadLog::dailyForUser((int) $user['id'], 30),
        'total' => DownloadLog::totalForUser((int) $user['id']),
    ]);
});

$router->get('/stats/usage', function (): void {
    Auth::requireLogin();
    $user = Auth::user();

    View::render('stats/usage', [
        'monthly' => UsageLog::monthlyForUser((int) $user['id'], 12),
        'total' => UsageLog::totalForUser((int) $user['id']),
    ]);
});

// ---------------------------------------------------------------- Admin (hidden, owner only)

$router->get('/admin', function (): void {
    Auth::requireAdmin();

    View::render('admin/dashboard', [
        'userCount' => User::count(),
        'appCount' => AppProject::countAll(),
        'downloadTotal' => DownloadLog::totalGlobal(),
        'usageTotal' => UsageLog::totalGlobal(),
        'usageToday' => UsageLog::todayGlobal(),
        'daily' => DownloadLog::dailyGlobal(30),
        'monthly' => UsageLog::monthlyGlobal(12),
    ]);
});

$router->get('/admin/users', function (): void {
    Auth::requireAdmin();
    View::render('admin/users', ['users' => User::allWithAppCounts()]);
});

$router->get('/admin/apps', function (): void {
    Auth::requireAdmin();
    View::render('admin/apps', ['apps' => AppProject::allWithOwners()]);
});

$router->get('/admin/apps/{id}', function (string $id): void {
    Auth::requireAdmin();
    $app = AppProject::find((int) $id);

    if ($app === null) {
        http_response_code(404);
        View::render('errors/404', []);
        return;
    }

    View::render('admin/app-show', [
        'app' => $app,
        'fonts' => Fonts::OPTIONS,
        'builds' => Build::historyForApp((int) $app['id']),
    ]);
});

$router->post('/admin/apps/{id}/update', function (string $id): void {
    Auth::requireAdmin();
    $app = AppProject::find((int) $id);

    if ($app === null || !Csrf::verify($_POST['csrf_token'] ?? null)) {
        header('Location: /admin/apps');
        exit;
    }

    [$fields, $errors] = validate_app_input($_POST, requirePackageCheck: false);

    if (!empty($errors)) {
        Flash::set('error', implode(' ', $errors));
        header('Location: /admin/apps/' . $app['id']);
        exit;
    }

    $newIcon = handle_icon_upload();
    if ($newIcon !== null) {
        $fields['icon_path'] = $newIcon;
    }

    AppProject::update((int) $app['id'], $fields);
    Flash::set('success', 'Ayarlar kaydedildi.');
    header('Location: /admin/apps/' . $app['id']);
    exit;
});

$router->post('/admin/apps/{id}/delete', function (string $id): void {
    Auth::requireAdmin();
    $app = AppProject::find((int) $id);

    if ($app !== null && Csrf::verify($_POST['csrf_token'] ?? null)) {
        AppProject::delete((int) $app['id']);
        Flash::set('success', 'Uygulama silindi.');
    }

    header('Location: /admin/apps');
    exit;
});

// ---------------------------------------------------------------- Public config API (called by installed apps)

// No login required: every installed app calls this on startup with its own
// package name to pick up the latest target URL / colors / splash text /
// font without needing a rebuild. Only non-secret display fields are
// returned - never signing keys, tokens or user data.
$router->get('/api/config/{packageId}', function (string $packageId): void {
    header('Content-Type: application/json');

    $app = AppProject::findByPackageId($packageId);

    if ($app === null) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }

    UsageLog::log((int) $app['id']);

    echo json_encode([
        'target_url' => $app['target_url'],
        'header_color' => $app['header_color'],
        'splash_bg_color' => $app['splash_bg_color'],
        'splash_text_color' => $app['splash_text_color'],
        'splash_text' => $app['splash_text'],
        'splash_show_icon' => (bool) $app['splash_show_icon'],
        'splash_duration_ms' => ((int) $app['splash_duration']) * 1000,
        'font_name' => $app['font_name'],
        'version_name' => $app['version_name'],
    ]);
});

// ---------------------------------------------------------------- Helpers

function validate_app_input(array $input, bool $requirePackageCheck = true): array
{
    $errors = [];

    $name = trim($input['name'] ?? '');
    $targetUrl = trim($input['target_url'] ?? '');
    $headerColor = trim($input['header_color'] ?? '#2563EB');
    $splashBg = trim($input['splash_bg_color'] ?? '#2563EB');
    $splashTextColor = trim($input['splash_text_color'] ?? '#FFFFFF');
    $splashText = trim($input['splash_text'] ?? '');
    $splashShowIcon = !empty($input['splash_show_icon']) ? 1 : 0;
    $splashDuration = (int) ($input['splash_duration'] ?? 2);
    $fontName = trim($input['font_name'] ?? 'default');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        $errors[] = 'Uygulama adı 2-50 karakter olmalıdır.';
    }

    if (!filter_var($targetUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $targetUrl)) {
        $errors[] = 'Geçerli bir web sitesi adresi girin (https:// ile başlamalı).';
    }

    $colorPattern = '/^#[0-9A-Fa-f]{6}$/';
    foreach (['header_color' => $headerColor, 'splash_bg_color' => $splashBg, 'splash_text_color' => $splashTextColor] as $label => $color) {
        if (!preg_match($colorPattern, $color)) {
            $errors[] = 'Renk kodları #RRGGBB formatında olmalıdır.';
            break;
        }
    }

    if (!Fonts::isValid($fontName)) {
        $fontName = 'default';
    }

    if (mb_strlen($splashText) > 60) {
        $errors[] = 'Açılış ekranı metni en fazla 60 karakter olabilir.';
    }

    if ($splashDuration < 1 || $splashDuration > 10) {
        $errors[] = 'Açılış ekranı süresi 1-10 saniye arasında olmalıdır.';
    }

    return [[
        'name' => $name,
        'target_url' => $targetUrl,
        'header_color' => strtoupper($headerColor),
        'splash_bg_color' => strtoupper($splashBg),
        'splash_text_color' => strtoupper($splashTextColor),
        'splash_text' => $splashText,
        'splash_show_icon' => $splashShowIcon,
        'splash_duration' => max(1, min(10, $splashDuration)),
        'font_name' => $fontName,
        'version_name' => '1.0.0',
    ], $errors];
}

function handle_icon_upload(): ?string
{
    if (empty($_FILES['icon']) || $_FILES['icon']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
        Flash::set('error', 'İkon yüklenirken bir hata oluştu (kod: ' . (int) $_FILES['icon']['error'] . ').');
        return null;
    }

    $tmpPath = $_FILES['icon']['tmp_name'];
    $info = @getimagesize($tmpPath);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    if ($info === false || !isset($allowed[$info['mime']])) {
        Flash::set('error', 'İkon yalnızca PNG veya JPG formatında olabilir.');
        return null;
    }

    if ($_FILES['icon']['size'] > 2 * 1024 * 1024) {
        Flash::set('error', 'İkon dosyası 2MB\'dan küçük olmalıdır.');
        return null;
    }

    $iconsDir = BASE_PATH . '/uploads/icons';
    if (!is_dir($iconsDir) && !mkdir($iconsDir, 0755, true) && !is_dir($iconsDir)) {
        Flash::set('error', 'Sunucuda uploads/icons klasörü oluşturulamadı. Dosya izinlerini kontrol edin.');
        return null;
    }

    if (!is_writable($iconsDir)) {
        Flash::set('error', 'uploads/icons klasörüne yazma izni yok. Sunucudaki dosya izinlerini kontrol edin.');
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$info['mime']];
    $destination = $iconsDir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        Flash::set('error', 'İkon dosyası kaydedilemedi. Sunucudaki dosya izinlerini kontrol edin.');
        return null;
    }

    return $filename;
}

// ---------------------------------------------------------------- Dispatch

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
