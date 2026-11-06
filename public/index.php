<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RemoteCompatibilityRepository.php';

use App\RemoteCompatibilityRepository;

$repo = new RemoteCompatibilityRepository();
$query = trim($_GET['q'] ?? '');
$selectedDeviceId = isset($_GET['device_id']) ? (int) $_GET['device_id'] : 0;

$searchResults = [];
if ($query !== '') {
    $searchResults = $repo->searchDevices($query);
}

$device = null;
$compatibilityRows = [];
if ($selectedDeviceId > 0) {
    $device = $repo->getDeviceById($selectedDeviceId);
    if ($device) {
        $compatibilityRows = $repo->getDeviceCompatibility($selectedDeviceId);
    }
}

$hasSearch = $query !== '' || $selectedDeviceId > 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>قاعدة بيانات التوافقات</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">لوحة البحث</p>
                <h1>مكتبة التوافقات بين الأجهزة والريموتات</h1>
            </div>
        </header>

        <main class="layout">
            <section class="panel search-panel">
                <h2>البحث عن جهاز</h2>

                <div class="search-box">
                    <input
                        type="text"
                        id="deviceSearch"
                        value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="اكتب اسم الشركة أو الموديل..."
                        autocomplete="off"
                    />
                    <div id="suggestions" class="suggestions"></div>
                </div>

                <?php if ($hasSearch): ?>
                    <?php if ($searchResults): ?>
                        <div class="result-list">
                            <?php foreach ($searchResults as $result): ?>
                                <a href="index.php?q=<?= urlencode($result['brand'] . ' ' . $result['model']) ?>&device_id=<?= (int) $result['id'] ?>" class="result-item">
                                    <?= htmlspecialchars($result['brand'] . ' - ' . $result['model'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">لم يتم العثور على نتائج مطابقة.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($device): ?>
                    <div class="device-card">
                        <h3><?= htmlspecialchars($device['brand'] . ' - ' . $device['model'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($device['notes'] ?: 'لا توجد ملاحظات إضافية.', ENT_QUOTES, 'UTF-8') ?></p>

                        <?php if ($compatibilityRows): ?>
                            <div class="compatibility-list">
                                <?php foreach ($compatibilityRows as $row): ?>
                                    <?php
                                        $badgeClass = 'full';

                                        if ($row['compatibility_type'] === 'مناسب بالكامل') {
                                            $badgeClass = 'full';
                                        } elseif ($row['compatibility_type'] === 'يحتاج برمجة') {
                                            $badgeClass = 'program';
                                        } elseif ($row['compatibility_type'] === 'توافق جزئي') {
                                            $badgeClass = 'partial';
                                        }
                                    ?>
                                    <div class="compatibility-item">
                                        <div>
                                            <div class="remote-name"><?= htmlspecialchars($row['remote_brand'] . ' - ' . $row['remote_model'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small><?= htmlspecialchars($row['notes'] ?: $row['remote_notes'] ?: 'لا توجد ملاحظات إضافية', ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['compatibility_type'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">لا توجد ريموتات متوافقة مسجلة لهذا الجهاز بعد.</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($hasSearch): ?>
                    <div class="device-card">
                        <p class="empty-state">اختر جهازًا من النتائج لعرض الريموتات المتوافقة معه.</p>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="panel form-panel">
                <h2>إضافة ريموت</h2>
                <form method="post" action="admin.php">
                    <input type="hidden" name="action" value="save_remote" />

                    <div class="field">
                        <label>اسم الشركة</label>
                        <input type="text" name="brand" required />
                    </div>

                    <div class="field">
                        <label>موديل الريموت</label>
                        <input type="text" name="model" required />
                    </div>

                    <div class="field">
                        <label>ملاحظات</label>
                        <textarea name="notes" rows="4"></textarea>
                    </div>

                    <button type="submit">حفظ الريموت</button>
                </form>
            </aside>

            <aside class="panel form-panel">
                <h2>إضافة جهاز وترابط الريموتات</h2>
                <form method="post" action="admin.php">
                    <input type="hidden" name="action" value="save_device" />

                    <div class="field">
                        <label>اسم الشركة</label>
                        <input type="text" name="brand" required />
                    </div>

                    <div class="field">
                        <label>موديل الجهاز</label>
                        <input type="text" name="model" required />
                    </div>

                    <div class="field">
                        <label>نوع التوافق</label>
                        <select name="compatibility_type">
                            <option value="مناسب بالكامل">مناسب بالكامل</option>
                            <option value="يحتاج برمجة">يحتاج برمجة</option>
                            <option value="توافق جزئي">توافق جزئي</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>اختيار الريموتات</label>
                        <?php
                            $remotes = $repo->getAllRemotes();
                        ?>

                        <?php if ($remotes): ?>
                            <div class="checkbox-list">
                                <?php foreach ($remotes as $remote): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="remote_ids[]" value="<?= (int) $remote['id'] ?>" />
                                        <span><?= htmlspecialchars($remote['brand'] . ' - ' . $remote['model'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">لا توجد ريموتات بعد، أضف ريموتًا أولًا.</p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label>ملاحظات الجهاز</label>
                        <textarea name="notes" rows="4"></textarea>
                    </div>

                    <button type="submit">حفظ الجهاز</button>
                </form>
            </aside>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
