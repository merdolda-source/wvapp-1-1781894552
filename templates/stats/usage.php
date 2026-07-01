<?php
$max = 1;
foreach ($monthly as $row) {
    $max = max($max, (int) $row['total']);
}
$monthNames = ['01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz',
               '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'];
?>

<section class="page-head">
    <h1>Kullanım İstatistikleri</h1>
</section>
<p class="muted">Tüm uygulamalarınızın son 12 aydaki toplam açılma sayısı. Toplam: <strong><?= (int) $total ?></strong></p>

<div class="card">
    <?php if (empty($monthly)): ?>
        <p class="muted">Henüz bir kullanım kaydı yok.</p>
    <?php else: ?>
        <div class="bar-chart">
            <?php foreach ($monthly as $row): ?>
                <?php
                $height = max(4, (int) round(((int) $row['total'] / $max) * 100));
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
