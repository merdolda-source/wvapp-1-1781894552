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
    <h1>Yönetici Paneli <span class="admin-badge">ADMIN</span></h1>
</section>

<div class="tabs">
    <a class="tab-btn" href="/admin">Genel Bakış</a>
    <a class="tab-btn" href="/admin/users">Kullanıcılar</a>
    <a class="tab-btn active" href="/admin/apps">Uygulamalar</a>
</div>

<div class="card">
    <?php if (empty($apps)): ?>
        <p class="muted">Henüz oluşturulmuş uygulama yok.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Uygulama</th><th>Sahibi</th><th>Paket Adı</th><th>Durum</th><th>Sürüm</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($apps as $app): ?>
                <tr>
                    <td><?= View::e($app['name']) ?></td>
                    <td><?= View::e($app['owner_name']) ?><br><span class="muted"><?= View::e($app['owner_email']) ?></span></td>
                    <td><?= View::e($app['package_id']) ?></td>
                    <td><span class="status status-<?= View::e($app['status']) ?>"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span></td>
                    <td><?= View::e($app['version_name']) ?> (<?= (int) $app['version_code'] ?>)</td>
                    <td>
                        <a href="/admin/apps/<?= (int) $app['id'] ?>">Düzenle</a>
                        <form method="post" action="/admin/apps/<?= (int) $app['id'] ?>/delete" class="inline-form" onsubmit="return confirm('Bu uygulamayı silmek istediğinize emin misiniz?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="link-button danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
