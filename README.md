# Remote Compatibility Database

تطبيق ويب احترافي لإدارة توافقات الأجهزة مع الريموتات، مبني على PHP + MySQL.

## المميزات
- بحث ذكي عن الأجهزة حسب اسم الشركة أو الموديل
- اقتراحات فورية أثناء الكتابة
- عرض الريموتات المتوافقة مع كل جهاز
- شاشة إدارة لإضافة الريموتات والأجهزة
- ربط متعدد إلى متعدد بين الأجهزة والريموتات
- دعم MySQL عبر PDO
- تصميم عربي RTL وعصري

## المتطلبات
- PHP 8.1+
- MySQL 8+
- خادم ويب مثل Apache أو PHP Built-in Server

## إعداد قاعدة البيانات

1. أنشئ قاعدة بيانات جديدة في MySQL:

```sql
CREATE DATABASE remotes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. استورد ملف schema:

```bash
mysql -u root -p remotes_db < database/schema.sql
```

3. عدّل ملف `config/database.php` إذا لزم الأمر مع بيانات الاتصال:

```php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'remotes_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

## التشغيل

### عبر خادم PHP الداخلي

```bash
php -S localhost:8000 -t public
```

ثم افتح:

```text
http://localhost:8000/index.php
```

### في حالة Apache/Nginx

استخدم مجلد `public` كمسار الجذر للموقع.

## هيكل المشروع

```text
config/
  database.php
public/
  index.php
  admin.php
  api.php
  assets/
    css/style.css
    js/app.js
src/
  Database.php
  RemoteCompatibilityRepository.php
database/
  schema.sql
README.md
```

## نماذج البيانات

توجد بيانات تجريبية داخل ملف `database/schema.sql` لعرض التطبيق مباشرة بعد الاستيراد.

## ملاحظات

- التوافق بين الجهاز والريموت يتم من خلال جدول وسيط `device_remote`.
- يمكن ربط نفس الريموت بعدة أجهزة، وكذلك نفس الجهاز بعدة ريموتات.
- نوع التوافق يدعم: `مناسب بالكامل`، `يحتاج برمجة`، `توافق جزئي`.

## النمو المستقبلي
- إضافة صلاحيات المستخدمين
- إضافة صور للأجهزة والريموتات
- نظام تعديل وحذف
- لوحة إحصاءات وتقارير
- API REST كامل
