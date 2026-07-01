<section class="page-head">
    <h1>Uygulamalarım</h1>
    <?php if ($canCreate): ?>
        <a class="btn" href="/apps/new">+ Yeni Uygulama</a>
    <?php endif; ?>
</section>

<p class="muted"><?= count($apps) ?> / <?= $maxApps ?> uygulama kullanıldı.</p>

<?php if (empty($apps)): ?>
    <div class="card">
        <p>Henüz bir uygulamanız yok. Web sitenizi Android uygulamasına dönüştürmek için ilk uygulamanızı oluşturun.</p>
        <a class="btn" href="/apps/new">İlk Uygulamamı Oluştur</a>
    </div>
<?php else: ?>
    <div class="app-grid">
        <?php foreach ($apps as $app): ?>
            <a class="app-card" href="/apps/<?= (int) $app['id'] ?>">
                <div class="app-icon" style="background: <?= View::e($app['header_color']) ?>">
                    <?php if ($app['icon_path']): ?>
                        <img src="/uploads/icons/<?= View::e($app['icon_path']) ?>" alt="">
                    <?php else: ?>
                        <span><?= View::e(mb_substr($app['name'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="app-info">
                    <h3><?= View::e($app['name']) ?></h3>
                    <p class="muted"><?= View::e($app['package_id']) ?></p>
                    <span class="status status-<?= View::e($app['status']) ?>"><?= View::e($app['status']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
