<section class="auth-wrap">
    <form method="post" action="/forgot-password" class="auth-card">
        <?= Csrf::field() ?>
        <h1>Quên mật khẩu</h1>
        <?php if (!empty($submitted)): ?>
            <div class="flash flash-success">Nếu email tồn tại trong hệ thống, một link đặt lại mật khẩu đã được gửi. Hãy kiểm tra cả hộp thư rác.</div>
        <?php endif; ?>
        <p class="muted">Nhập email tài khoản. Link đặt lại mật khẩu chỉ dùng được một lần và hết hạn sau 60 phút.</p>
        <label>Email<input type="email" name="email" required maxlength="190" autocomplete="email"></label>
        <button class="button button-full" type="submit">Gửi link đặt lại mật khẩu</button>
        <p class="auth-note"><a href="/login">← Quay lại đăng nhập</a></p>
    </form>
</section>
