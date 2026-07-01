<section class="page-head">
    <h1>Yeni Uygulama Oluştur</h1>
</section>

<form method="post" action="/apps" enctype="multipart/form-data" class="card app-form">
    <?= Csrf::field() ?>

    <label>Uygulama Adı
        <input type="text" name="name" required maxlength="50" placeholder="Örn: Benim Mağazam">
    </label>

    <label>Web Sitesi Adresi
        <input type="url" name="target_url" required placeholder="https://www.siteniz.com">
    </label>

    <label>Paket Adı <span class="muted">(boş bırakılırsa otomatik oluşturulur, sonradan değiştirilemez)</span>
        <input type="text" name="package_id" pattern="[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+" placeholder="com.siteniz.uygulama">
    </label>

    <label>Uygulama İkonu (PNG/JPG, kare önerilir)
        <input type="file" name="icon" accept="image/png,image/jpeg">
    </label>

    <div class="color-grid">
        <label>Üst Bar Rengi
            <input type="color" name="header_color" value="#2563EB">
        </label>
        <label>Açılış Ekranı Arka Plan Rengi
            <input type="color" name="splash_bg_color" value="#2563EB">
        </label>
        <label>Açılış Ekranı Yazı Rengi
            <input type="color" name="splash_text_color" value="#FFFFFF">
        </label>
    </div>

    <label>Açılış Ekranı Metni
        <input type="text" name="splash_text" maxlength="60" placeholder="Örn: Hoş Geldiniz">
    </label>

    <label>Yazı Fontu
        <select name="font_name">
            <?php foreach ($fonts as $key => $font): ?>
                <option value="<?= View::e($key) ?>"><?= View::e($font['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <button type="submit" class="btn">Uygulamayı Oluştur</button>
</form>
