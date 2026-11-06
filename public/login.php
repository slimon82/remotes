<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/AdminAuth.php';

use App\AdminAuth;
use App\Database;
use function App\e;

AdminAuth::startSession();
if (AdminAuth::isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuth::verifyToken($_POST['csrf_token'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. حدّث الصفحة وحاول مرة أخرى.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = Database::getConnection()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            AdminAuth::login($admin);
            header('Location: admin.php');
            exit;
        }
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دخول المشرف | مكتبة توافق الريموتات</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand-mark" href="index.php">ريموتك</a>
        <p class="eyebrow">منطقة الإدارة</p>
        <h1>تسجيل دخول المشرف</h1>
        <p class="muted">سجّل الدخول لإدارة الأجهزة والريموتات والتوافقات.</p>
        <?php if ($error !== ''): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="login.php" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e(AdminAuth::token()) ?>">
            <label>اسم المستخدم<input name="username" autocomplete="username" required autofocus maxlength="80"></label>
            <label>كلمة المرور<input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit">دخول آمن</button>
        </form>
        <a class="back-link" href="index.php">العودة إلى البحث</a>
    </main>
</body>
</html>
