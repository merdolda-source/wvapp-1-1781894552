<?php
$pageTitle = 'Ücretsiz Kayıt Ol — CreatorApp24';
$pageDescription = 'CreatorApp24\'e ücretsiz kayıt olun, web sitenizi Android uygulamasına dönüştürmeye hemen başlayın.';
?>
<section class="card auth-card">
    <h1>Kayıt Ol</h1>

    <?php if ($googleEnabled): ?>
        <a href="/auth/google" class="btn btn-google">Google ile Kayıt Ol</a>
        <div class="divider">veya</div>
    <?php endif; ?>

    <form method="post" action="/register">
        <?= Csrf::field() ?>
        <label>Ad Soyad
            <input type="text" name="name" required maxlength="120">
        </label>
        <label>E-posta
            <input type="email" name="email" required maxlength="190">
        </label>
        <label>Şifre
            <input type="password" name="password" required minlength="6">
        </label>
        <button type="submit" class="btn">Kayıt Ol</button>
    </form>
    <p>Zaten hesabınız var mı? <a href="/login">Giriş yapın</a></p>
</section>
