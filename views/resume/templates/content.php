<?php
$fullName = trim((string) $resume['full_name']) !== '' ? (string) $resume['full_name'] : 'HỌ VÀ TÊN';
$jobTitle = trim((string) $resume['job_title']);
$contactParts = array_values(array_filter([
    trim((string) $resume['email']),
    trim((string) $resume['phone']),
    trim((string) $resume['location']),
]));
$visibleSections = [];
foreach ($sections as $sectionKey => $items) {
    $visibleSections[$sectionKey] = visible_items(is_array($items) ? $items : []);
}
?>
<header class="cv-header">
    <h1><?= e($fullName) ?></h1>
    <?php if ($jobTitle !== ''): ?><p class="cv-job-title"><?= e($jobTitle) ?></p><?php endif; ?>
    <?php if ($contactParts !== []): ?><p class="cv-contact"><?= e(implode(' · ', $contactParts)) ?></p><?php endif; ?>
</header>

<?php if (trim((string) $resume['summary']) !== ''): ?>
<section class="cv-section">
    <h2>Giới thiệu</h2>
    <p><?= nl2br(e((string) $resume['summary'])) ?></p>
</section>
<?php endif; ?>

<?php if (($visibleSections['experiences'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Kinh nghiệm</h2>
    <?php foreach ($visibleSections['experiences'] as $item): ?>
        <div class="cv-entry">
            <div class="cv-entry-heading">
                <strong><?= e($item['role'] ?? '') ?></strong>
                <span><?= e(trim(($item['start'] ?? '') . ' – ' . ($item['end'] ?? ''), " –")) ?></span>
            </div>
            <?php if (($item['company'] ?? '') !== ''): ?><div class="cv-entry-subtitle"><?= e($item['company']) ?></div><?php endif; ?>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= nl2br(e($item['description'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($visibleSections['projects'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Dự án</h2>
    <?php foreach ($visibleSections['projects'] as $item): ?>
        <div class="cv-entry">
            <div class="cv-entry-heading">
                <strong><?= e($item['name'] ?? '') ?></strong>
                <?php $projectUrl = safe_url($item['url'] ?? ''); ?>
                <?php if ($projectUrl !== ''): ?><a href="<?= e($projectUrl) ?>" target="_blank" rel="noopener">Liên kết</a><?php endif; ?>
            </div>
            <?php if (($item['technologies'] ?? '') !== ''): ?><div class="cv-entry-subtitle"><?= e($item['technologies']) ?></div><?php endif; ?>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= nl2br(e($item['description'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($visibleSections['educations'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Học vấn</h2>
    <?php foreach ($visibleSections['educations'] as $item): ?>
        <div class="cv-entry">
            <div class="cv-entry-heading">
                <strong><?= e($item['school'] ?? '') ?></strong>
                <span><?= e(trim(($item['start'] ?? '') . ' – ' . ($item['end'] ?? ''), " –")) ?></span>
            </div>
            <?php if (($item['degree'] ?? '') !== ''): ?><div class="cv-entry-subtitle"><?= e($item['degree']) ?></div><?php endif; ?>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= nl2br(e($item['description'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($visibleSections['skills'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Kỹ năng</h2>
    <p class="cv-tags">
        <?php foreach ($visibleSections['skills'] as $item): ?>
            <?php if (($item['name'] ?? '') !== ''): ?><span><?= e($item['name']) ?></span><?php endif; ?>
        <?php endforeach; ?>
    </p>
</section>
<?php endif; ?>

<?php if (($visibleSections['certificates'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Chứng chỉ</h2>
    <?php foreach ($visibleSections['certificates'] as $item): ?>
        <div class="cv-entry compact">
            <div class="cv-entry-heading">
                <strong><?= e($item['name'] ?? '') ?></strong>
                <span><?= e($item['year'] ?? '') ?></span>
            </div>
            <?php if (($item['issuer'] ?? '') !== ''): ?><div class="cv-entry-subtitle"><?= e($item['issuer']) ?></div><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($visibleSections['languages'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Ngoại ngữ</h2>
    <p class="cv-list-inline">
        <?php foreach ($visibleSections['languages'] as $item): ?>
            <?php if (($item['name'] ?? '') !== ''): ?><span><strong><?= e($item['name']) ?></strong><?= ($item['level'] ?? '') !== '' ? ': ' . e($item['level']) : '' ?></span><?php endif; ?>
        <?php endforeach; ?>
    </p>
</section>
<?php endif; ?>

<?php if (($visibleSections['links'] ?? []) !== []): ?>
<section class="cv-section">
    <h2>Liên kết</h2>
    <p class="cv-list-inline">
        <?php foreach ($visibleSections['links'] as $item): ?>
            <?php $linkUrl = safe_url($item['url'] ?? ''); ?>
            <?php if ($linkUrl !== ''): ?><a href="<?= e($linkUrl) ?>" target="_blank" rel="noopener"><?= e(($item['label'] ?? '') !== '' ? $item['label'] : $linkUrl) ?></a><?php endif; ?>
        <?php endforeach; ?>
    </p>
</section>
<?php endif; ?>
