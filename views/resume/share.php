<section class="page-section">
    <div class="container narrow-container">
        <div class="page-heading share-heading">
            <div>
                <a class="back-link" href="/dashboard">← Quay lại dashboard</a>
                <h1>Chia sẻ CV</h1>
                <p class="muted">CV: <strong><?= e($resume['title']) ?></strong></p>
            </div>
        </div>

        <?php if (!empty($generatedUrl)): ?>
            <div class="share-link-panel">
                <h2>Link mới đã được tạo</h2>
                <p class="muted">Hãy sao chép ngay. Vì lý do bảo mật, hệ thống không thể hiển thị lại token sau khi bạn rời trang.</p>
                <div class="copy-row">
                    <input id="generated-share-url" type="text" readonly value="<?= e($generatedUrl) ?>">
                    <button class="button button-small" type="button" data-copy-share>Copy link</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="share-grid">
            <article class="form-card">
                <h2>Tạo hoặc thay thế link chia sẻ</h2>
                <p class="muted small">Khi tạo link mới, link cũ của CV này sẽ bị thu hồi. Người có link có thể xem và in CV mà không cần đăng nhập.</p>
                <form method="post" action="/resume/share/create">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
                    <label>Thời hạn link
                        <select name="valid_days">
                            <option value="7">7 ngày</option>
                            <option value="30" selected>30 ngày</option>
                            <option value="90">90 ngày</option>
                            <option value="0">Không đặt thời hạn</option>
                        </select>
                    </label>
                    <button class="button" type="submit">Tạo link chia sẻ mới</button>
                </form>
            </article>

            <article class="form-card">
                <h2>Trạng thái hiện tại</h2>
                <?php if ($share === null): ?>
                    <p class="muted">CV này chưa có link chia sẻ đang hoạt động.</p>
                <?php else: ?>
                    <dl class="share-stats">
                        <div><dt>Trạng thái</dt><dd><span class="status-pill status-success">Đang hoạt động</span></dd></div>
                        <div><dt>Ngày tạo</dt><dd><?= e(date('d/m/Y H:i', strtotime((string) $share['created_at']))) ?></dd></div>
                        <div><dt>Hết hạn</dt><dd><?= !empty($share['expires_at']) ? e(date('d/m/Y H:i', strtotime((string) $share['expires_at']))) : 'Không đặt thời hạn' ?></dd></div>
                        <div><dt>Số lượt xem</dt><dd><?= (int) $share['view_count'] ?></dd></div>
                        <div><dt>Xem gần nhất</dt><dd><?= !empty($share['last_viewed_at']) ? e(date('d/m/Y H:i', strtotime((string) $share['last_viewed_at']))) : 'Chưa có lượt xem' ?></dd></div>
                    </dl>
                    <form method="post" action="/resume/share/revoke" onsubmit="return confirm('Thu hồi link chia sẻ hiện tại?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
                        <button class="button button-danger" type="submit">Thu hồi link</button>
                    </form>
                <?php endif; ?>
            </article>
        </div>
    </div>
</section>
<script>
(() => {
    const button = document.querySelector('[data-copy-share]');
    const input = document.getElementById('generated-share-url');
    if (!button || !input) return;
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'Đã copy';
        } catch {
            input.select();
            document.execCommand('copy');
            button.textContent = 'Đã copy';
        }
    });
})();
</script>
