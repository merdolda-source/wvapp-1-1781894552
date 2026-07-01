<?php
$pageTitle = $app['name'] . ' — Gizlilik Politikası';
$pageDescription = $app['name'] . ' mobil uygulaması gizlilik politikası.';
$noIndex = true;
?>
<section class="page-head">
    <h1>Gizlilik Politikası</h1>
</section>

<div class="card">
    <h2><?= View::e($app['name']) ?></h2>
    <p class="muted">Son güncelleme: <?= date('d.m.Y') ?></p>

    <p><?= View::e($app['name']) ?> mobil uygulaması, <?= View::e($app['target_url']) ?> adresindeki
        web sitesini görüntülemenizi sağlar.</p>

    <h3>Toplanan Bilgiler</h3>
    <p>Uygulamanın kendisi kişisel verilerinizi toplamaz, saklamaz veya üçüncü taraflarla paylaşmaz.
        Uygulama içinde görüntülenen web sitesi kendi çerezlerini ve yerel depolama verilerini
        kullanabilir; bu verilerin toplanması ve kullanımı ilgili web sitesinin kendi gizlilik
        politikasına tabidir.</p>

    <h3>İzinler</h3>
    <ul>
        <li><strong>İnternet erişimi:</strong> Web sitesini yükleyebilmek için kullanılır.</li>
        <li><strong>Dosya erişimi:</strong> Yalnızca web sitesindeki formlarda dosya yükleme/indirme
            özelliklerini kullanmanız durumunda devreye girer.</li>
    </ul>

    <h3>Üçüncü Taraf Hizmetler</h3>
    <p>Uygulama Google Play Hizmetleri üzerinden dağıtılmaktadır; Google'ın kendi gizlilik politikası
        ayrıca geçerlidir.</p>

    <h3>Veri Güvenliği</h3>
    <p>Uygulama, verilerinizi kendi sunucularında saklamaz; tüm etkileşim doğrudan yukarıdaki web
        sitesiyle gerçekleşir.</p>

    <h3>İletişim</h3>
    <p>Bu gizlilik politikasıyla ilgili sorularınız için uygulamayı yayınlayan geliştiriciyle iletişime
        geçebilirsiniz.</p>
</div>
