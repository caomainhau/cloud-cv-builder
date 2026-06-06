<section class="page-section">
    <div class="container">
        <div class="page-heading">
            <div>
                <span class="eyebrow">Xin chào, <?= e($user['name']) ?></span>
                <h1>CV của tôi</h1>
                <p class="muted">Tạo nhiều phiên bản để điều chỉnh nội dung cho từng vị trí ứng tuyển.</p>
            </div>
            <form method="post" action="/resume/create" class="create-resume-form">
                <?= Csrf::field() ?>
                <input type="text" name="title" placeholder="Ví dụ: Cybersecurity Intern" maxlength="160" required>
                <button class="button" type="submit">+ Tạo CV mới</button>
            </form>
        </div>

        <?php if ($resumes === []): ?>
            <div class="empty-state">
                <h2>Bạn chưa có CV nào</h2>
                <p>Nhập tên vị trí ở phía trên và tạo bản CV đầu tiên.</p>
            </div>
        <?php else: ?>
            <div class="resume-card-grid">
                <?php foreach ($resumes as $resume): ?>
                    <article class="resume-card">
                        <div class="resume-card-top">
                            <span class="template-pill"><?= e($resume['template'] === 'modern-minimal' ? 'Modern Minimal' : 'ATS Simple') ?></span>
                            <span class="muted small">Cập nhật <?= e(date('d/m/Y H:i', strtotime((string) $resume['updated_at']))) ?></span>
                        </div>
                        <h2><?= e($resume['title']) ?></h2>
                        <p><?= e($resume['job_title'] !== '' ? $resume['job_title'] : 'Chưa nhập vị trí ứng tuyển') ?></p>
                        <div class="card-actions">
                            <a class="button button-small" href="/resume/edit?id=<?= (int) $resume['id'] ?>">Chỉnh sửa</a>
                            <a class="button button-secondary button-small" href="/resume/preview?id=<?= (int) $resume['id'] ?>" target="_blank" rel="noopener">Xem PDF</a>
                            <form method="post" action="/resume/clone" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
                                <button class="link-button" type="submit">Sao chép</button>
                            </form>
                            <form method="post" action="/resume/delete" class="inline-form" onsubmit="return confirm('Xóa CV này?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
                                <button class="link-button danger" type="submit">Xóa</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
