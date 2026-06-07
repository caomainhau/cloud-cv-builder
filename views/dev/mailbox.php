<section class="page-section">
    <div class="container narrow-container">
        <span class="eyebrow">Chỉ hiển thị ở môi trường local</span>
        <h1>Hộp thư kiểm thử</h1>
        <p class="muted">Khi <code>MAIL_MODE=log</code>, email không được gửi ra Internet. Nội dung được lưu trong <code>storage/mail.log</code> để kiểm thử xác minh email và quên mật khẩu.</p>
        <?php if (($messages ?? []) === []): ?>
            <div class="empty-state"><h2>Chưa có email nào</h2><p>Thử đăng ký tài khoản mới hoặc yêu cầu đặt lại mật khẩu.</p></div>
        <?php else: ?>
            <div class="mailbox-list">
                <?php foreach ($messages as $message): ?>
                    <article class="form-card mailbox-item">
                        <div class="mailbox-meta"><strong><?= e((string) ($message['subject'] ?? '')) ?></strong><span class="muted small"><?= e((string) ($message['created_at'] ?? '')) ?></span></div>
                        <p class="muted small">Đến: <?= e((string) ($message['to'] ?? '')) ?></p>
                        <div class="mailbox-preview"><?= (string) ($message['html'] ?? '') ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
