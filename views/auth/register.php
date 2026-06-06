<section class="auth-wrap">
    <form method="post" action="/register" class="auth-card">
        <?= Csrf::field() ?>
        <h1>Tạo tài khoản</h1>
        <p class="muted">Mỗi người dùng có khu vực CV riêng biệt.</p>
        <?php foreach (($errors ?? []) as $error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endforeach; ?>
        <label>Họ và tên<input type="text" name="name" value="<?= old_input('name') ?>" required maxlength="120" autocomplete="name"></label>
        <label>Email<input type="email" name="email" value="<?= old_input('email') ?>" required maxlength="190" autocomplete="email"></label>
        <label>Mật khẩu<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Tối thiểu 8 ký tự.</small></label>
        <button class="button button-full" type="submit">Đăng ký</button>
        <p class="auth-note">Đã có tài khoản? <a href="/login">Đăng nhập</a>.</p>
    </form>
</section>
