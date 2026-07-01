<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="/">WebView App Builder</a>
        <nav>
            <?php if (Auth::check()): ?>
                <a href="/dashboard">Panel</a>
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
