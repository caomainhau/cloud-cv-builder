<section class="editor-shell">
    <form id="resume-form" method="post" action="/resume/save">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
        <input type="hidden" id="sections-json" name="sections_json" value="">

        <div class="editor-toolbar no-print">
            <div>
                <a class="back-link" href="/dashboard">← Quay lại</a>
                <strong><?= e($resume['title']) ?></strong>
            </div>
            <div class="toolbar-actions">
                <span id="autosave-status" class="autosave-status" aria-live="polite">Đã tải dữ liệu</span>
                <a id="preview-link" class="button button-secondary button-small" href="/resume/preview?id=<?= (int) $resume['id'] ?>" target="_blank" rel="noopener">Mở bản in</a>
                <button class="button button-small" type="submit">Lưu ngay</button>
            </div>
        </div>

        <div class="editor-grid">
            <aside class="editor-panel no-print">
                <div class="editor-intro">
                    <h1>Chỉnh sửa CV</h1>
                    <p>Nhập nội dung ngắn gọn. Bản xem trước sẽ cập nhật ngay bên phải.</p>
                </div>

                <div class="form-card">
                    <h2>Cài đặt CV</h2>
                    <label>Tên bản CV<input type="text" name="title" value="<?= e($resume['title']) ?>" maxlength="160" required></label>
                    <label>Mẫu CV
                        <select name="template" id="template-select">
                            <option value="ats-simple" <?= $resume['template'] === 'ats-simple' ? 'selected' : '' ?>>ATS Simple · một cột</option>
                            <option value="modern-minimal" <?= $resume['template'] === 'modern-minimal' ? 'selected' : '' ?>>Modern Minimal · có màu nhấn</option>
                        </select>
                    </label>
                </div>

                <div class="form-card">
                    <h2>Thông tin cá nhân</h2>
                    <label>Họ và tên<input type="text" name="full_name" value="<?= e($resume['full_name']) ?>" maxlength="160" placeholder="Nguyễn Văn A"></label>
                    <label>Vị trí ứng tuyển<input type="text" name="job_title" value="<?= e($resume['job_title']) ?>" maxlength="160" placeholder="Cybersecurity Intern"></label>
                    <label>Email<input type="email" name="email" value="<?= e($resume['email']) ?>" maxlength="190" placeholder="email@example.com"></label>
                    <label>Số điện thoại<input type="text" name="phone" value="<?= e($resume['phone']) ?>" maxlength="50" placeholder="0900 000 000"></label>
                    <label>Khu vực sinh sống<input type="text" name="location" value="<?= e($resume['location']) ?>" maxlength="190" placeholder="Đà Nẵng, Việt Nam"></label>
                    <label>Giới thiệu ngắn<textarea name="summary" rows="5" maxlength="2500" placeholder="Tóm tắt định hướng, kỹ năng và mục tiêu nghề nghiệp..."><?= e($resume['summary']) ?></textarea></label>
                </div>

                <div id="dynamic-sections"></div>
            </aside>

            <div class="preview-stage">
                <div class="preview-help no-print">Bản xem trước · Khi hoàn tất, bấm <strong>Mở bản in</strong> và chọn <strong>Save as PDF</strong>.</div>
                <article id="live-preview" class="cv-document"></article>
            </div>
        </div>
    </form>
</section>

<script id="initial-sections" type="application/json"><?= json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/assets/editor.js"></script>
