<?php
$noIndex = true;
$statusLabels = [
    'draft' => 'Taslak',
    'queued' => 'Sırada',
    'building' => 'Yayınlanıyor',
    'ready' => 'Hazır',
    'failed' => 'Başarısız',
];
?>

<section class="page-head">
    <h1><?= View::e($app['name']) ?> <span class="admin-badge">ADMIN</span></h1>
    <span class="status status-<?= View::e($app['status']) ?>"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span>
</section>
<p class="muted"><?= View::e($app['package_id']) ?> &middot; sürüm <?= View::e($app['version_name']) ?> (kod <?= (int) $app['version_code'] ?>)
    &middot; Toplam indirme: <strong><?= (int) DownloadLog::totalForApp((int) $app['id']) ?></strong>
    &middot; Toplam açılma: <strong><?= (int) UsageLog::totalForApp((int) $app['id']) ?></strong></p>

<div class="grid-2">
    <div class="card">
        <h2>Ayarlar</h2>
        <form method="post" action="/admin/apps/<?= (int) $app['id'] ?>/update" enctype="multipart/form-data" class="app-form">
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

            <button type="submit" class="btn">Kaydet</button>
        </form>
    </div>

    <div class="card">
        <h2>Sürüm Geçmişi</h2>
        <?php if (empty($builds)): ?>
            <p class="muted">Henüz bir sürüm yayınlanmadı.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Sürüm</th><th>Durum</th><th>Tarih</th></tr></thead>
                <tbody>
                <?php foreach ($builds as $build): ?>
                    <tr>
                        <td><?= View::e($build['version_name']) ?> (<?= (int) $build['version_code'] ?>)</td>
                        <td><span class="status status-<?= View::e($build['status']) ?>"><?= View::e($build['status']) ?></span></td>
                        <td><?= View::e($build['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <form method="post" action="/admin/apps/<?= (int) $app['id'] ?>/delete" class="delete-form" onsubmit="return confirm('Bu uygulamayı silmek istediğinize emin misiniz?');">
            <?= Csrf::field() ?>
            <button type="submit" class="link-button danger">Uygulamayı Sil</button>
        </form>
    </div>
</div>
