<?php
$pageTitle = 'Giriş Yap — CreatorApp24';
$pageDescription = 'CreatorApp24 hesabınıza giriş yapın ve Android uygulamalarınızı yönetin.';
?>
<section class="card auth-card">
    <h1>Giriş Yap</h1>

    <?php if ($googleEnabled): ?>
        <a href="/auth/google" class="btn btn-google">Google ile Giriş Yap</a>
        <div class="divider">veya</div>
    <?php endif; ?>

    <form method="post" action="/login">
        <?= Csrf::field() ?>
        <label>E-posta
            <input type="email" name="email" required maxlength="190">
        </label>
        <label>Şifre
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn">Giriş Yap</button>
    </form>
    <p>Hesabınız yok mu? <a href="/register">Kayıt olun</a></p>
</section>
