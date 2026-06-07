<section class="auth-wrap">
    <form method="post" action="/reset-password" class="auth-card">
        <?= Csrf::field() ?>
        <h1>Đặt lại mật khẩu</h1>
        <?php if (!empty($invalidToken)): ?>
            <div class="flash flash-error">Link đặt lại mật khẩu không hợp lệ, đã được sử dụng hoặc đã hết hạn.</div>
            <p class="auth-note"><a href="/forgot-password">Yêu cầu một link mới</a></p>
        <?php else: ?>
            <?php foreach (($errors ?? []) as $error): ?>
                <div class="flash flash-error"><?= e($error) ?></div>
            <?php endforeach; ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <p class="muted">Chọn mật khẩu mới có ít nhất 8 ký tự.</p>
            <label>Mật khẩu mới<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label>Xác nhận mật khẩu mới<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
            <button class="button button-full" type="submit">Đặt lại mật khẩu</button>
        <?php endif; ?>
        <p class="auth-note"><a href="/login">← Quay lại đăng nhập</a></p>
    </form>
</section>
