<?php $flashes = pull_flashes(); ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? app_name()) ?> · <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header no-print">
    <div class="container header-inner">
        <a class="brand" href="/">Cloud<span>CV</span></a>
        <nav class="nav">
            <?php if (Auth::check()): ?>
                <a href="/dashboard">CV của tôi</a>
                <form method="post" action="/logout" class="inline-form">
                    <?= Csrf::field() ?>
                    <button class="link-button" type="submit">Đăng xuất</button>
                </form>
            <?php else: ?>
                <a href="/login">Đăng nhập</a>
                <a class="button button-small" href="/register">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <div class="container flash-stack no-print">
        <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
        <?php endforeach; ?>
    </div>
    <?= $content ?>
</main>

<footer class="site-footer no-print">
    <div class="container">CloudCV Builder · MVP dành cho nhóm bạn và portfolio cá nhân.</div>
</footer>
</body>
</html>
