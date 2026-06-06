<section class="page-section">
    <div class="container account-grid">
        <div>
            <span class="eyebrow">Bảo mật tài khoản</span>
            <h1>Tài khoản</h1>
            <p class="muted">Email đăng nhập: <?= e($user['email']) ?></p>

            <form method="post" action="/account/password" class="form-card account-card">
                <?= Csrf::field() ?>
                <h2>Đổi mật khẩu</h2>
                <?php foreach (($errors ?? []) as $error): ?>
                    <div class="flash flash-error"><?= e($error) ?></div>
                <?php endforeach; ?>
                <label>Mật khẩu hiện tại<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Mật khẩu mới<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
                <label>Xác nhận mật khẩu mới<input type="password" name="new_password_confirmation" required minlength="8" autocomplete="new-password"></label>
                <button class="button" type="submit">Đổi mật khẩu</button>
            </form>
        </div>

        <div class="form-card account-card">
            <h2>Hoạt động gần đây</h2>
            <p class="muted small">Nhật ký giúp bạn nhận biết thao tác bất thường. Hệ thống không lưu mật khẩu hoặc nội dung CV trong log.</p>
            <?php if (($activities ?? []) === []): ?>
                <p class="muted">Chưa có hoạt động nào.</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div>
                                <strong><?= e(activity_label((string) $activity['action'])) ?></strong>
                                <div class="muted small">IP: <?= e((string) $activity['ip_address']) ?></div>
                            </div>
                            <div class="activity-meta">
                                <span class="status-pill status-<?= e((string) $activity['status']) ?>"><?= e((string) $activity['status']) ?></span>
                                <span class="muted small"><?= e(date('d/m/Y H:i', strtotime((string) $activity['created_at']))) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
