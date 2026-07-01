<?php
$latestBuild = $builds[0] ?? null;
$statusLabels = [
    'draft' => 'Taslak',
    'queued' => 'Sırada',
    'building' => 'Derleniyor',
    'ready' => 'Hazır',
    'failed' => 'Başarısız',
];
$buildLabel = ($app['status'] === 'ready' || $app['status'] === 'failed') ? 'Yeni Sürüm Derle' : 'Derlemeyi Başlat';
?>

<section class="page-head">
    <h1><?= View::e($app['name']) ?></h1>
    <span class="status status-<?= View::e($app['status']) ?>" id="app-status-badge"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span>
</section>
<p class="muted"><?= View::e($app['package_id']) ?> &middot; sürüm <span id="app-version-name"><?= View::e($app['version_name']) ?></span> (kod <?= (int) $app['version_code'] ?>)</p>

<?php if (!$githubConfigured): ?>
    <div class="alert alert-error">GitHub bağlantısı henüz yapılandırılmadı. Derleme başlatmak için sunucudaki
        <code>.env</code> dosyasında <code>GITHUB_TOKEN</code>, <code>GITHUB_OWNER</code> ve <code>GITHUB_REPO</code>
        değerlerini ayarlayın.</div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h2>Derleme &amp; İndirme</h2>
        <form method="post" action="/apps/<?= (int) $app['id'] ?>/build">
            <?= Csrf::field() ?>
            <button type="submit" class="btn" <?= (!$githubConfigured || in_array($app['status'], ['queued', 'building'], true)) ? 'disabled' : '' ?>>
                <?= View::e($buildLabel) ?>
            </button>
        </form>

        <div id="build-progress" style="<?= in_array($app['status'], ['queued', 'building'], true) ? '' : 'display:none' ?>">
            <p class="muted">Derleme sürüyor, bu sayfa otomatik olarak güncellenecek…</p>
        </div>

        <div id="download-links" style="<?= $app['status'] === 'ready' ? '' : 'display:none' ?>">
            <?php if ($app['status'] === 'ready'): ?>
                <a class="btn btn-secondary" href="/apps/<?= (int) $app['id'] ?>/download/apk">APK İndir</a>
                <a class="btn btn-secondary" href="/apps/<?= (int) $app['id'] ?>/download/aab">AAB İndir (Play Store)</a>
            <?php endif; ?>
        </div>

        <h3>Sürüm Geçmişi</h3>
        <?php if (empty($builds)): ?>
            <p class="muted">Henüz derleme yapılmadı.</p>
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
                                <a href="/apps/<?= (int) $app['id'] ?>/download/apk">APK</a> ·
                                <a href="/apps/<?= (int) $app['id'] ?>/download/aab">AAB</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Ayarlar</h2>
        <form method="post" action="/apps/<?= (int) $app['id'] ?>/update" enctype="multipart/form-data" class="app-form">
            <?= Csrf::field() ?>

            <label>Uygulama Adı
                <input type="text" name="name" required maxlength="50" value="<?= View::e($app['name']) ?>">
            </label>

            <label>Web Sitesi Adresi
                <input type="url" name="target_url" required value="<?= View::e($app['target_url']) ?>">
            </label>

            <label>Uygulama İkonu (değiştirmek için yeni dosya seçin)
                <input type="file" name="icon" accept="image/png,image/jpeg">
            </label>
            <?php if ($app['icon_path']): ?>
                <img class="icon-preview" src="/uploads/icons/<?= View::e($app['icon_path']) ?>" alt="">
            <?php endif; ?>

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

            <label>Yazı Fontu
                <select name="font_name">
                    <?php foreach ($fonts as $key => $font): ?>
                        <option value="<?= View::e($key) ?>" <?= $key === $app['font_name'] ? 'selected' : '' ?>><?= View::e($font['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit" class="btn">Kaydet</button>
        </form>

        <form method="post" action="/apps/<?= (int) $app['id'] ?>/delete" class="delete-form" onsubmit="return confirm('Bu uygulamayı silmek istediğinize emin misiniz?');">
            <?= Csrf::field() ?>
            <button type="submit" class="link-button danger">Uygulamayı Sil</button>
        </form>
    </div>
</div>

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
