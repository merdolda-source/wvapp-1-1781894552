<?php
$max = 1;
foreach ($daily as $row) {
    $max = max($max, (int) $row['total']);
}
?>

<section class="page-head">
    <h1>İndirme İstatistikleri</h1>
</section>
<p class="muted">Tüm uygulamalarınız için son 30 gündeki APK/AAB indirme sayısı. Toplam: <strong><?= (int) $total ?></strong></p>

<div class="card">
    <?php if (empty($daily)): ?>
        <p class="muted">Henüz bir indirme kaydı yok.</p>
    <?php else: ?>
        <div class="bar-chart">
            <?php foreach ($daily as $row): ?>
                <?php $height = max(4, (int) round(((int) $row['total'] / $max) * 100)); ?>
                <div class="bar-col">
                    <span class="bar-value"><?= (int) $row['total'] ?></span>
                    <div class="bar" style="height: <?= $height ?>%"></div>
                    <span class="bar-label"><?= View::e(date('d.m', strtotime($row['day']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
