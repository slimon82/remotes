<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RemoteCompatibilityRepository.php';
require __DIR__ . '/../src/AdminAuth.php';

use App\AdminAuth;
use App\Database;
use App\RemoteCompatibilityRepository;
use function App\e;

$repo = new RemoteCompatibilityRepository();
$query = trim($_GET['q'] ?? '');
$selectedDeviceId = isset($_GET['device_id']) ? (int) $_GET['device_id'] : 0;
$searchResults = $query !== '' ? $repo->searchDevices($query) : [];
$device = $selectedDeviceId > 0 ? $repo->getDeviceById($selectedDeviceId) : null;
$compatibilityRows = $device ? $repo->getDeviceCompatibility($selectedDeviceId) : [];
$hasSearch = $query !== '' || $selectedDeviceId > 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10233f">
    <title>ريموتك | ابحث عن ريموت جهازك</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <header class="topbar public-topbar">
        <a class="brand-mark" href="index.php">ريموتك<span>.</span></a>
        <div class="topbar-copy"><p class="eyebrow">قاعدة توافقات الريموتات</p><h1>اعثر على الريموت المناسب لجهازك</h1><p class="muted">ابحث باسم الشركة أو موديل الرسيفر لعرض الريموتات المتوافقة.</p></div>
        <a class="button-link secondary" href="login.php">دخول المشرف</a>
    </header>
    <main class="public-layout">
        <section class="panel search-panel">
            <h2>ابحث عن جهاز</h2>
            <p class="muted">اكتب جزءًا من اسم الشركة أو رقم الموديل وستظهر الاقتراحات مباشرة.</p>
            <div class="search-box"><span class="search-icon" aria-hidden="true">⌕</span><input type="search" id="deviceSearch" value="<?= e($query) ?>" placeholder="مثال: StarSat SR-2000" autocomplete="off" aria-label="البحث عن جهاز"><div id="suggestions" class="suggestions" role="listbox"></div></div>
            <?php if ($hasSearch && $searchResults): ?><div class="result-list"><p class="list-label">نتائج البحث</p><?php foreach ($searchResults as $result): ?><a href="index.php?q=<?= urlencode($result['brand'] . ' ' . $result['model']) ?>&device_id=<?= (int) $result['id'] ?>" class="result-item"><span class="device-icon">▣</span><span><?= e($result['brand'] . ' — ' . $result['model']) ?></span><span class="result-arrow">←</span></a><?php endforeach; ?></div><?php elseif ($hasSearch && !$device): ?><p class="empty-state">لم نعثر على جهاز مطابق. تحقق من الاسم أو الموديل.</p><?php endif; ?>
            <?php if ($device): ?><article class="device-card"><div class="device-card-heading"><span class="device-icon large">▣</span><div><span class="eyebrow">الجهاز المحدد</span><h3><?= e($device['brand'] . ' — ' . $device['model']) ?></h3></div></div><p class="device-notes"><?= e($device['notes'] ?: 'لا توجد ملاحظات إضافية للجهاز.') ?></p><h4>الريموتات المتوافقة <span class="count-pill"><?= count($compatibilityRows) ?></span></h4><?php if ($compatibilityRows): ?><div class="compatibility-list"><?php foreach ($compatibilityRows as $row): ?><?php $badgeClass = 'full'; if ($row['compatibility_type'] === 'يحتاج برمجة') { $badgeClass = 'program'; } elseif ($row['compatibility_type'] === 'توافق جزئي') { $badgeClass = 'partial'; } ?><div class="compatibility-item"><span class="remote-icon">◉</span><div class="compatibility-copy"><strong><?= e($row['remote_brand'] . ' — ' . $row['remote_model']) ?></strong><small><?= e($row['notes'] ?: $row['remote_notes'] ?: 'لا توجد ملاحظات إضافية.') ?></small></div><span class="badge <?= e($badgeClass) ?>"><?= e($row['compatibility_type']) ?></span></div><?php endforeach; ?></div><?php else: ?><p class="empty-state">لم تتم إضافة ريموتات متوافقة لهذا الجهاز بعد.</p><?php endif; ?></article><?php elseif (!$hasSearch): ?><div class="search-hint"><span>✦</span><p>ابدأ بكتابة اسم الشركة أو رقم موديل الجهاز للعثور على التوافقات.</p></div><?php endif; ?>
        </section>
        <aside class="help-card"><div class="help-symbol">✦</div><h2>كيف يعمل البحث؟</h2><p>اختر جهازك من الاقتراحات، وستظهر لك الريموتات المسجلة مع درجة التوافق والملاحظات المهمة.</p><div class="legend"><div><i class="legend-dot full"></i><span>متوافق بالكامل</span></div><div><i class="legend-dot program"></i><span>يحتاج برمجة</span></div><div><i class="legend-dot partial"></i><span>توافق جزئي</span></div></div></aside>
    </main>
    <footer class="site-footer">قاعدة بيانات مجتمعية لتسهيل العثور على ريموت جهازك.</footer>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
