<section class="auth-wrap">
    <div class="auth-card">
        <span class="eyebrow">Bảo vệ tài khoản</span>
        <h1>Xác minh email</h1>
        <p class="muted">Chúng tôi đã gửi link xác minh đến <strong><?= e((string) $user['email']) ?></strong>. Hãy mở email và bấm vào link để tiếp tục sử dụng khu vực CV.</p>
        <?php foreach (($errors ?? []) as $error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endforeach; ?>
        <form method="post" action="/verify-email/resend">
            <?= Csrf::field() ?>
            <button class="button button-full" type="submit">Gửi lại email xác minh</button>
        </form>
        <?php if (env_value('APP_ENV', 'local') !== 'production' && Mailer::mode() === 'log'): ?>
            <p class="auth-note">Đang chạy local: mở <a href="/dev/mailbox">hộp thư kiểm thử</a> để lấy link.</p>
        <?php endif; ?>
        <form method="post" action="/logout" class="auth-note">
            <?= Csrf::field() ?>
            <button class="link-button" type="submit">Đăng xuất và dùng tài khoản khác</button>
        </form>
    </div>
</section>
