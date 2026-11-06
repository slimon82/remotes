<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/AdminAuth.php';

use App\AdminAuth;
use App\Database;
use function App\e;

AdminAuth::requireLogin();
$db = Database::getConnection();
$allowedTypes = ['مناسب بالكامل', 'يحتاج برمجة', 'توافق جزئي'];

if (empty($_SESSION['flash'])) {
    $_SESSION['flash'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuth::verifyToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['flash'] = 'انتهت صلاحية الطلب. حدّث الصفحة وحاول مرة أخرى.';
        header('Location: admin.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_remote') {
            $id = (int) ($_POST['id'] ?? 0);
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            if ($brand === '' || $model === '') {
                throw new RuntimeException('اسم الشركة والموديل مطلوبان.');
            }
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE remotes SET brand = :brand, model = :model, notes = :notes WHERE id = :id');
                $stmt->execute([':brand' => $brand, ':model' => $model, ':notes' => $notes, ':id' => $id]);
                $_SESSION['flash'] = 'تم تحديث بيانات الريموت.';
            } else {
                $stmt = $db->prepare('INSERT INTO remotes (brand, model, notes) VALUES (:brand, :model, :notes)');
                $stmt->execute([':brand' => $brand, ':model' => $model, ':notes' => $notes]);
                $_SESSION['flash'] = 'تمت إضافة الريموت.';
            }
        } elseif ($action === 'delete_remote') {
            $stmt = $db->prepare('DELETE FROM remotes WHERE id = :id');
            $stmt->execute([':id' => (int) ($_POST['id'] ?? 0)]);
            $_SESSION['flash'] = 'تم حذف الريموت وروابط توافقه.';
        } elseif ($action === 'save_device') {
            $id = (int) ($_POST['id'] ?? 0);
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $type = $_POST['compatibility_type'] ?? '';
            $remoteIds = isset($_POST['remote_ids']) && is_array($_POST['remote_ids']) ? array_values(array_unique(array_filter(array_map('intval', $_POST['remote_ids'])))) : [];
            if ($brand === '' || $model === '') {
                throw new RuntimeException('اسم الشركة وموديل الجهاز مطلوبان.');
            }
            if (!in_array($type, $allowedTypes, true)) {
                throw new RuntimeException('نوع التوافق المحدد غير صحيح.');
            }
            $count = 0;
            if ($remoteIds) {
                $placeholders = implode(',', array_fill(0, count($remoteIds), '?'));
                $check = $db->prepare('SELECT COUNT(*) FROM remotes WHERE id IN (' . $placeholders . ')');
                $check->execute($remoteIds);
                $count = (int) $check->fetchColumn();
            }
            if (!$remoteIds || $count !== count($remoteIds)) {
                throw new RuntimeException('اختر ريموتًا واحدًا على الأقل من القائمة.');
            }

            $db->beginTransaction();
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE devices SET brand = :brand, model = :model, notes = :notes WHERE id = :id');
                $stmt->execute([':brand' => $brand, ':model' => $model, ':notes' => $notes, ':id' => $id]);
                $deviceId = $id;
                $delete = $db->prepare('DELETE FROM device_remote WHERE device_id = :id');
                $delete->execute([':id' => $deviceId]);
            } else {
                $stmt = $db->prepare('INSERT INTO devices (brand, model, notes) VALUES (:brand, :model, :notes)');
                $stmt->execute([':brand' => $brand, ':model' => $model, ':notes' => $notes]);
                $deviceId = (int) $db->lastInsertId();
            }
            $link = $db->prepare('INSERT INTO device_remote (device_id, remote_id, compatibility_type, notes) VALUES (:device_id, :remote_id, :type, :notes)');
            foreach ($remoteIds as $remoteId) {
                $link->execute([':device_id' => $deviceId, ':remote_id' => $remoteId, ':type' => $type, ':notes' => $notes]);
            }
            $db->commit();
            $_SESSION['flash'] = $id > 0 ? 'تم تحديث الجهاز والتوافقات.' : 'تمت إضافة الجهاز والتوافقات.';
        } elseif ($action === 'delete_device') {
            $stmt = $db->prepare('DELETE FROM devices WHERE id = :id');
            $stmt->execute([':id' => (int) ($_POST['id'] ?? 0)]);
            $_SESSION['flash'] = 'تم حذف الجهاز وروابط توافقه.';
        }
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['flash'] = 'تعذر تنفيذ الطلب: ' . $exception->getMessage();
    }
    header('Location: admin.php');
    exit;
}

$flash = $_SESSION['flash'];
$_SESSION['flash'] = '';
$editingRemote = null;
$editingDevice = null;
$selectedRemoteIds = [];
if (isset($_GET['edit_remote'])) {
    $stmt = $db->prepare('SELECT * FROM remotes WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit_remote']]);
    $editingRemote = $stmt->fetch() ?: null;
}
if (isset($_GET['edit_device'])) {
    $stmt = $db->prepare('SELECT * FROM devices WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit_device']]);
    $editingDevice = $stmt->fetch() ?: null;
    if ($editingDevice) {
        $stmt = $db->prepare('SELECT remote_id FROM device_remote WHERE device_id = :id');
        $stmt->execute([':id' => (int) $editingDevice['id']]);
        $selectedRemoteIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
$remotes = $db->query('SELECT * FROM remotes ORDER BY brand, model')->fetchAll();
$devices = $db->query('SELECT d.*, COUNT(dr.remote_id) AS remote_count FROM devices d LEFT JOIN device_remote dr ON dr.device_id = d.id GROUP BY d.id ORDER BY d.brand, d.model')->fetchAll();
$compatTypes = $db->query('SELECT device_id, compatibility_type FROM device_remote GROUP BY device_id, compatibility_type')->fetchAll();
$typesByDevice = [];
foreach ($compatTypes as $row) {
    $typesByDevice[(int) $row['device_id']][] = $row['compatibility_type'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة قاعدة التوافقات</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container admin-container">
    <header class="topbar admin-topbar">
        <div><p class="eyebrow">لوحة المشرف</p><h1>إدارة الأجهزة والريموتات</h1><p class="muted">مرحبًا <?= e($_SESSION['admin_username'] ?? 'مشرف') ?>، يمكنك إدارة السجلات والتوافقات من هنا.</p></div>
        <nav class="admin-nav"><a class="button-link secondary" href="index.php">عرض الموقع</a><a class="button-link" href="logout.php">تسجيل الخروج</a></nav>
    </header>
    <?php if ($flash !== ''): ?><div class="notice info"><?= e($flash) ?></div><?php endif; ?>

    <main class="admin-grid">
        <section class="panel">
            <h2><?= $editingRemote ? 'تعديل ريموت' : 'إضافة ريموت' ?></h2>
            <form method="post" action="admin.php" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= e(AdminAuth::token()) ?>">
                <input type="hidden" name="action" value="save_remote">
                <input type="hidden" name="id" value="<?= (int) ($editingRemote['id'] ?? 0) ?>">
                <label>الشركة<input name="brand" required maxlength="120" value="<?= e($editingRemote['brand'] ?? '') ?>"></label>
                <label>موديل الريموت<input name="model" required maxlength="180" value="<?= e($editingRemote['model'] ?? '') ?>"></label>
                <label>ملاحظات<textarea name="notes" rows="3"><?= e($editingRemote['notes'] ?? '') ?></textarea></label>
                <button type="submit"><?= $editingRemote ? 'حفظ التعديلات' : 'إضافة الريموت' ?></button>
                <?php if ($editingRemote): ?><a class="back-link" href="admin.php">إلغاء التعديل</a><?php endif; ?>
            </form>
        </section>

        <section class="panel">
            <h2><?= $editingDevice ? 'تعديل جهاز وتوافقاته' : 'إضافة جهاز مدعوم' ?></h2>
            <form method="post" action="admin.php" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= e(AdminAuth::token()) ?>">
                <input type="hidden" name="action" value="save_device">
                <input type="hidden" name="id" value="<?= (int) ($editingDevice['id'] ?? 0) ?>">
                <label>الشركة<input name="brand" required maxlength="120" value="<?= e($editingDevice['brand'] ?? '') ?>"></label>
                <label>موديل الجهاز<input name="model" required maxlength="180" value="<?= e($editingDevice['model'] ?? '') ?>"></label>
                <label>ملاحظات<textarea name="notes" rows="2"><?= e($editingDevice['notes'] ?? '') ?></textarea></label>
                <label>نوع التوافق
                    <select name="compatibility_type" required>
                        <?php foreach ($allowedTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <fieldset class="checkbox-list"><legend>الريموتات المتوافقة (يمكن اختيار أكثر من واحد)</legend>
                    <?php if ($remotes): foreach ($remotes as $remote): ?>
                        <label class="checkbox-item"><input type="checkbox" name="remote_ids[]" value="<?= (int) $remote['id'] ?>" <?= in_array((int) $remote['id'], $selectedRemoteIds, true) ? 'checked' : '' ?>><span><?= e($remote['brand'] . ' — ' . $remote['model']) ?></span></label>
                    <?php endforeach; else: ?><p class="empty-state">أضف ريموتًا أولًا حتى تتمكن من ربطه بجهاز.</p><?php endif; ?>
                </fieldset>
                <button type="submit"><?= $editingDevice ? 'حفظ الجهاز والتوافقات' : 'إضافة الجهاز' ?></button>
                <?php if ($editingDevice): ?><a class="back-link" href="admin.php">إلغاء التعديل</a><?php endif; ?>
            </form>
        </section>

        <section class="panel table-panel">
            <div class="section-heading"><h2>الريموتات</h2><span class="count-pill"><?= count($remotes) ?></span></div>
            <?php if ($remotes): ?><div class="table-wrap"><table><thead><tr><th>الشركة</th><th>الموديل</th><th>ملاحظات</th><th>إجراءات</th></tr></thead><tbody>
                <?php foreach ($remotes as $remote): ?><tr><td><?= e($remote['brand']) ?></td><td><?= e($remote['model']) ?></td><td><?= e($remote['notes']) ?></td><td class="actions"><a href="admin.php?edit_remote=<?= (int) $remote['id'] ?>">تعديل</a><form method="post" action="admin.php" onsubmit="return confirm('حذف هذا الريموت؟ ستُحذف روابط توافقه أيضًا.');"><input type="hidden" name="csrf_token" value="<?= e(AdminAuth::token()) ?>"><input type="hidden" name="action" value="delete_remote"><input type="hidden" name="id" value="<?= (int) $remote['id'] ?>"><button class="text-danger" type="submit">حذف</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div><?php else: ?><p class="empty-state">لا توجد ريموتات مسجلة.</p><?php endif; ?>
        </section>

        <section class="panel table-panel">
            <div class="section-heading"><h2>الأجهزة والتوافقات</h2><span class="count-pill"><?= count($devices) ?></span></div>
            <?php if ($devices): ?><div class="table-wrap"><table><thead><tr><th>الشركة</th><th>الموديل</th><th>عدد الريموتات</th><th>التوافق</th><th>إجراءات</th></tr></thead><tbody>
                <?php foreach ($devices as $device): ?><tr><td><?= e($device['brand']) ?></td><td><?= e($device['model']) ?></td><td><?= (int) $device['remote_count'] ?></td><td><?= e(implode('، ', $typesByDevice[(int) $device['id']] ?? [])) ?></td><td class="actions"><a href="admin.php?edit_device=<?= (int) $device['id'] ?>">تعديل</a><form method="post" action="admin.php" onsubmit="return confirm('حذف هذا الجهاز؟');"><input type="hidden" name="csrf_token" value="<?= e(AdminAuth::token()) ?>"><input type="hidden" name="action" value="delete_device"><input type="hidden" name="id" value="<?= (int) $device['id'] ?>"><button class="text-danger" type="submit">حذف</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div><?php else: ?><p class="empty-state">لا توجد أجهزة مسجلة.</p><?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
