<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="/">CreatorApp24</a>
        <nav>
            <?php if (Auth::check()): ?>
                <a href="/dashboard">Panel</a>
                <a href="/stats/downloads">İndirmeler</a>
                <a href="/stats/usage">Kullanım</a>
                <a href="/account">Hesabım</a>
                <?php if (Auth::isAdmin()): ?>
                    <a href="/admin">Admin</a>
                <?php endif; ?>
                <form action="/logout" method="post" class="inline-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="link-button">Çıkış Yap</button>
                </form>
            <?php else: ?>
                <a href="/login">Giriş Yap</a>
                <a href="/register" class="btn btn-small">Kayıt Ol</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
