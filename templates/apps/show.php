<?php
$noIndex = true;
$latestBuild = $builds[0] ?? null;
$statusLabels = [
    'draft' => 'Taslak',
    'queued' => 'Sırada',
    'building' => 'Yayınlanıyor',
    'ready' => 'Hazır',
    'failed' => 'Başarısız',
];
$buildLabel = ($app['status'] === 'ready' || $app['status'] === 'failed') ? 'Yeni Sürüm Yayınla' : 'Yayınlamayı Başlat';
?>

<section class="page-head">
    <h1><?= View::e($app['name']) ?></h1>
    <span class="status status-<?= View::e($app['status']) ?>" id="app-status-badge"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span>
</section>
<p class="muted"><?= View::e($app['package_id']) ?> &middot; sürüm <span id="app-version-name"><?= View::e($app['version_name']) ?></span> (kod <?= (int) $app['version_code'] ?>)</p>

<?php if (!$githubConfigured): ?>
    <div class="alert alert-error">Yayınlama sistemi henüz yapılandırılmadı. Lütfen sunucudaki
        <code>.env</code> dosyasındaki API ayarlarını tamamlayın.</div>
<?php endif; ?>

<div class="tabs">
    <button type="button" class="tab-btn active" data-tab="general">Genel</button>
    <button type="button" class="tab-btn" data-tab="splash">Splash &amp; Görünüm</button>
    <button type="button" class="tab-btn" data-tab="build">Yayınla &amp; İndir</button>
</div>

<form method="post" action="/apps/<?= (int) $app['id'] ?>/update" enctype="multipart/form-data" class="app-form">
    <?= Csrf::field() ?>

    <div class="tab-panel active" data-panel="general">
        <div class="card">
            <label>Uygulama Adı
                <input type="text" name="name" required maxlength="50" value="<?= View::e($app['name']) ?>">
            </label>

            <label>Web Sitesi Adresi
                <input type="url" name="target_url" required value="<?= View::e($app['target_url']) ?>">
            </label>

            <label>Paket Adı <span class="muted">(oluşturulduktan sonra değiştirilemez)</span>
                <input type="text" value="<?= View::e($app['package_id']) ?>" disabled>
            </label>

            <label>Uygulama İkonu (değiştirmek için yeni dosya seçin)
                <input type="file" name="icon" accept="image/png,image/jpeg">
            </label>
            <?php if ($app['icon_path']): ?>
                <img class="icon-preview" src="/uploads/icons/<?= View::e($app['icon_path']) ?>" alt="">
            <?php endif; ?>

            <label>Gizlilik Politikası Linki <span class="muted">(Play Store mağaza kaydınıza ekleyin)</span>
                <input type="text" readonly onclick="this.select()"
                    value="<?= View::e(rtrim((string) Config::get('APP_URL'), '/')) ?>/privacy/<?= View::e($app['package_id']) ?>">
            </label>
        </div>
    </div>

    <div class="tab-panel" data-panel="splash">
        <div class="card">
            <div class="color-grid">
                <label>Durum Çubuğu Rengi
                    <input type="color" name="header_color" value="<?= View::e($app['header_color']) ?>">
                </label>
                <label>Açılış Ekranı Arka Plan Rengi
                    <input type="color" name="splash_bg_color" value="<?= View::e($app['splash_bg_color']) ?>">
                </label>
                <label>Açılış Ekranı Yazı Rengi
                    <input type="color" name="splash_text_color" value="<?= View::e($app['splash_text_color']) ?>">
                </label>
            </div>

            <label>Açılış Ekranı Metni
                <input type="text" name="splash_text" maxlength="60" value="<?= View::e($app['splash_text']) ?>">
            </label>

            <label class="checkbox-label">
                <input type="checkbox" name="splash_show_icon" value="1" <?= !empty($app['splash_show_icon']) ? 'checked' : '' ?>>
                Açılış ekranında uygulama ikonunu göster
            </label>

            <label>Açılış Ekranı Süresi (saniye)
                <input type="number" name="splash_duration" min="1" max="10" value="<?= (int) $app['splash_duration'] ?>">
            </label>

            <label>Yazı Fontu
                <select name="font_name">
                    <?php foreach ($fonts as $key => $font): ?>
                        <option value="<?= View::e($key) ?>" <?= $key === $app['font_name'] ? 'selected' : '' ?>><?= View::e($font['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <p class="muted">Web adresi, renkler, splash metni/ikonu/süresi ve font kaydettiğiniz anda
                birkaç saniye içinde kurulu uygulamalara yansır. Yalnızca uygulama adı veya ikonunu
                değiştirirseniz yeni bir sürüm yayınlamanız gerekir.</p>
        </div>
    </div>

    <div class="tab-save-bar">
        <button type="submit" class="btn">Kaydet</button>
    </div>
</form>

<div class="tab-panel" data-panel="build">
    <div class="card">
        <h2>Yayınla &amp; İndir</h2>
        <form method="post" action="/apps/<?= (int) $app['id'] ?>/build">
            <?= Csrf::field() ?>
            <button type="submit" class="btn" <?= (!$githubConfigured || in_array($app['status'], ['queued', 'building'], true)) ? 'disabled' : '' ?>>
                <?= View::e($buildLabel) ?>
            </button>
        </form>

        <div id="build-progress" style="<?= in_array($app['status'], ['queued', 'building'], true) ? '' : 'display:none' ?>">
            <p class="muted">Yayınlanıyor, bu sayfa otomatik olarak güncellenecek…</p>
        </div>

        <div id="download-links" style="<?= $app['status'] === 'ready' ? '' : 'display:none' ?>">
            <?php if ($app['status'] === 'ready'): ?>
                <a class="btn btn-success" href="/apps/<?= (int) $app['id'] ?>/download/apk">APK İndir</a>
                <a class="btn btn-success" href="/apps/<?= (int) $app['id'] ?>/download/aab">AAB İndir (Play Store)</a>
            <?php endif; ?>
        </div>

        <h3>Sürüm Geçmişi</h3>
        <?php if (empty($builds)): ?>
            <p class="muted">Henüz bir sürüm yayınlanmadı.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Sürüm</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($builds as $build): ?>
                    <tr>
                        <td><?= View::e($build['version_name']) ?> (<?= (int) $build['version_code'] ?>)</td>
                        <td><span class="status status-<?= View::e($build['status']) ?>"><?= View::e($build['status']) ?></span></td>
                        <td><?= View::e($build['created_at']) ?></td>
                        <td>
                            <?php if ($build['status'] === 'success'): ?>
                                <a class="download-link" href="/apps/<?= (int) $app['id'] ?>/download/apk">APK</a> ·
                                <a class="download-link" href="/apps/<?= (int) $app['id'] ?>/download/aab">AAB</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <form method="post" action="/apps/<?= (int) $app['id'] ?>/delete" class="delete-form" onsubmit="return confirm('Bu uygulamayı silmek istediğinize emin misiniz?');">
        <?= Csrf::field() ?>
        <button type="submit" class="link-button danger">Uygulamayı Sil</button>
    </form>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('[data-panel="' + btn.dataset.tab + '"]').forEach(function (p) { p.classList.add('active'); });
    });
});
</script>

<?php if (in_array($app['status'], ['queued', 'building'], true)): ?>
<script>
(function poll() {
    fetch('/apps/<?= (int) $app['id'] ?>/status')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.app_status === 'ready' || data.app_status === 'failed') {
                window.location.reload();
                return;
            }
            setTimeout(poll, 8000);
        })
        .catch(function () { setTimeout(poll, 15000); });
})();
</script>
<?php endif; ?>
