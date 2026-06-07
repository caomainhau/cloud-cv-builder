<section class="page-section">
    <div class="container">
        <div class="page-heading account-heading">
            <div>
                <span class="eyebrow">Hồ sơ và bảo mật</span>
                <h1>Tài khoản</h1>
                <p class="muted">Quản lý thông tin cá nhân, mật khẩu và dữ liệu của bạn.</p>
            </div>
            <a class="button button-secondary" href="/account/export">Tải toàn bộ dữ liệu JSON</a>
        </div>

        <div class="account-grid">
            <div>
                <form method="post" action="/account/profile" class="form-card account-card">
                    <?= Csrf::field() ?>
                    <h2>Hồ sơ tài khoản</h2>
                    <?php foreach (($profileErrors ?? []) as $error): ?>
                        <div class="flash flash-error"><?= e($error) ?></div>
                    <?php endforeach; ?>
                    <label>Họ và tên<input type="text" name="name" value="<?= e((string) $user['name']) ?>" required minlength="2" maxlength="120" autocomplete="name"></label>
                    <label>Email đăng nhập<input type="email" value="<?= e((string) $user['email']) ?>" readonly></label>
                    <div class="verification-row">
                        <span>Trạng thái email</span>
                        <?php if (Auth::isVerified($user)): ?>
                            <span class="status-pill status-success">Đã xác minh</span>
                        <?php else: ?>
                            <span class="status-pill status-blocked">Chưa xác minh</span>
                        <?php endif; ?>
                    </div>
                    <button class="button" type="submit">Lưu hồ sơ</button>
                    <?php if (!Auth::isVerified($user)): ?>
                        <a class="button button-secondary" href="/verify-email">Xác minh email</a>
                    <?php endif; ?>
                </form>

                <form method="post" action="/account/password" class="form-card account-card">
                    <?= Csrf::field() ?>
                    <h2>Đổi mật khẩu</h2>
                    <?php foreach (($passwordErrors ?? []) as $error): ?>
                        <div class="flash flash-error"><?= e($error) ?></div>
                    <?php endforeach; ?>
                    <label>Mật khẩu hiện tại<input type="password" name="current_password" required autocomplete="current-password"></label>
                    <label>Mật khẩu mới<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
                    <label>Xác nhận mật khẩu mới<input type="password" name="new_password_confirmation" required minlength="8" autocomplete="new-password"></label>
                    <button class="button" type="submit">Đổi mật khẩu</button>
                </form>

                <article class="form-card account-card danger-zone">
                    <h2>Xóa tài khoản</h2>
                    <p class="muted small">Thao tác này xóa CV, link chia sẻ và dữ liệu tài khoản. Không thể hoàn tác. Hãy tải dữ liệu JSON trước khi tiếp tục.</p>
                    <?php foreach (($deleteErrors ?? []) as $error): ?>
                        <div class="flash flash-error"><?= e($error) ?></div>
                    <?php endforeach; ?>
                    <form method="post" action="/account/delete" onsubmit="return confirm('Xóa vĩnh viễn tài khoản và toàn bộ CV?');">
                        <?= Csrf::field() ?>
                        <label>Mật khẩu hiện tại<input type="password" name="password" required autocomplete="current-password"></label>
                        <label>Nhập XOA để xác nhận<input type="text" name="confirmation" required autocomplete="off"></label>
                        <button class="button button-danger" type="submit">Xóa vĩnh viễn tài khoản</button>
                    </form>
                </article>
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
    </div>
</section>
