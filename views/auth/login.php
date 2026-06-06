<section class="auth-wrap">
    <form method="post" action="/login" class="auth-card">
        <?= Csrf::field() ?>
        <h1>Đăng nhập</h1>
        <p class="muted">Tiếp tục chỉnh sửa các bản CV của bạn.</p>
        <?php foreach (($errors ?? []) as $error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endforeach; ?>
        <label>Email<input type="email" name="email" required autocomplete="email"></label>
        <label>Mật khẩu<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="button button-full" type="submit">Đăng nhập</button>
        <p class="auth-note">Chưa có tài khoản? <a href="/register">Đăng ký tại đây</a>.</p>
    </form>
</section>
