<?php
$dailyMax = 1;
foreach ($daily as $row) {
    $dailyMax = max($dailyMax, (int) $row['total']);
}
$monthlyMax = 1;
foreach ($monthly as $row) {
    $monthlyMax = max($monthlyMax, (int) $row['total']);
}
$monthNames = ['01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz',
               '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'];
?>

<section class="page-head">
    <h1>Yönetici Paneli <span class="admin-badge">ADMIN</span></h1>
</section>

<div class="tabs">
    <a class="tab-btn active" href="/admin">Genel Bakış</a>
    <a class="tab-btn" href="/admin/users">Kullanıcılar</a>
    <a class="tab-btn" href="/admin/apps">Uygulamalar</a>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-label">Toplam Kullanıcı</div>
        <div class="stat-value"><?= (int) $userCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toplam Uygulama</div>
        <div class="stat-value"><?= (int) $appCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toplam İndirme</div>
        <div class="stat-value"><?= (int) $downloadTotal ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toplam Açılma</div>
        <div class="stat-value"><?= (int) $usageTotal ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Bugün Açılma</div>
        <div class="stat-value"><?= (int) $usageToday ?></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Son 30 Gün — İndirmeler</h2>
        <?php if (empty($daily)): ?>
            <p class="muted">Henüz bir indirme kaydı yok.</p>
        <?php else: ?>
            <div class="bar-chart">
                <?php foreach ($daily as $row): ?>
                    <?php $height = max(4, (int) round(((int) $row['total'] / $dailyMax) * 100)); ?>
                    <div class="bar-col">
                        <span class="bar-value"><?= (int) $row['total'] ?></span>
                        <div class="bar" style="height: <?= $height ?>%"></div>
                        <span class="bar-label"><?= View::e(date('d.m', strtotime($row['day']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Son 12 Ay — Kullanım</h2>
        <?php if (empty($monthly)): ?>
            <p class="muted">Henüz bir kullanım kaydı yok.</p>
        <?php else: ?>
            <div class="bar-chart">
                <?php foreach ($monthly as $row): ?>
                    <?php
                    $height = max(4, (int) round(((int) $row['total'] / $monthlyMax) * 100));
                    [$year, $monthNum] = explode('-', $row['month']);
                    ?>
                    <div class="bar-col">
                        <span class="bar-value"><?= (int) $row['total'] ?></span>
                        <div class="bar bar-usage" style="height: <?= $height ?>%"></div>
                        <span class="bar-label"><?= View::e($monthNames[$monthNum] ?? $monthNum) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
