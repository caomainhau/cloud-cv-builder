<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($resume['title']) ?> · <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="print-body">
    <div class="print-toolbar no-print">
        <a class="button button-secondary" href="/resume/edit?id=<?= (int) $resume['id'] ?>">← Tiếp tục chỉnh sửa</a>
        <button class="button" type="button" onclick="window.print()">In hoặc lưu PDF</button>
    </div>
    <article class="cv-document <?= e($resume['template']) ?> print-document">
        <?php require __DIR__ . '/templates/content.php'; ?>
    </article>
</body>
</html>
