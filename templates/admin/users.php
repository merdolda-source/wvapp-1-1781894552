<section class="page-head">
    <h1>Yönetici Paneli <span class="admin-badge">ADMIN</span></h1>
</section>

<div class="tabs">
    <a class="tab-btn" href="/admin">Genel Bakış</a>
    <a class="tab-btn active" href="/admin/users">Kullanıcılar</a>
    <a class="tab-btn" href="/admin/apps">Uygulamalar</a>
</div>

<div class="card">
    <?php if (empty($users)): ?>
        <p class="muted">Henüz kayıtlı kullanıcı yok.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Ad</th><th>E-posta</th><th>Uygulama</th><th>Giriş Türü</th><th>Kayıt Tarihi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= View::e($u['name']) ?></td>
                    <td><?= View::e($u['email']) ?></td>
                    <td><?= (int) $u['app_count'] ?></td>
                    <td><?= $u['google_id'] ? 'Google' : 'E-posta' ?></td>
                    <td><?= View::e($u['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
