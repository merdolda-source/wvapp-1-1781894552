<?php $noIndex = true; ?>
<section class="page-head">
    <h1>Hesabım</h1>
</section>

<div class="grid-2">
    <div class="card">
        <h2>Profil</h2>
        <form method="post" action="/account/update">
            <?= Csrf::field() ?>
            <label>Ad Soyad
                <input type="text" name="name" required maxlength="120" value="<?= View::e($user['name']) ?>">
            </label>
            <label>E-posta
                <input type="email" value="<?= View::e($user['email']) ?>" disabled>
            </label>
            <button type="submit" class="btn">Kaydet</button>
        </form>
    </div>

    <div class="card">
        <h2>Şifre Değiştir</h2>
        <?php if ($user['password_hash'] === null): ?>
            <p class="muted">Hesabınız Google ile giriş yapıyor, bu hesap için ayrı bir şifre yoktur.</p>
        <?php else: ?>
            <form method="post" action="/account/password">
                <?= Csrf::field() ?>
                <label>Mevcut Şifre
                    <input type="password" name="current_password" required>
                </label>
                <label>Yeni Şifre
                    <input type="password" name="new_password" required minlength="6">
                </label>
                <label>Yeni Şifre (Tekrar)
                    <input type="password" name="confirm_password" required minlength="6">
                </label>
                <button type="submit" class="btn">Şifreyi Güncelle</button>
            </form>
        <?php endif; ?>
    </div>
</div>
