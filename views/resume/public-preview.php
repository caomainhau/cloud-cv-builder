<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= e($resume['title']) ?> · <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="print-body">
    <div class="print-toolbar no-print public-print-toolbar">
        <span class="muted small">Bản CV được chia sẻ công khai bằng link riêng.</span>
        <button class="button" type="button" onclick="window.print()">In hoặc lưu PDF</button>
    </div>
    <article class="cv-document <?= e($resume['template']) ?> print-document">
        <?php require __DIR__ . '/templates/content.php'; ?>
    </article>
</body>
</html>
