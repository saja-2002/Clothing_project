<?php
// تفعيل عرض الأخطاء بالكامل في الصفحة لمعرفة سبب المشكلة فوراً
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

echo "<div style='font-family: Arial, sans-serif; direction: rtl; padding: 20px; line-height: 1.6;'>";
echo "<h2 style='color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px;'>🧪 صفحة فحص وتتبع خطأ تحديث الطلبات</h2>";

try {
    // 1. فحص الاتصال بقاعدة البيانات وجلب أول طلب متاح
    echo "<h3>1️⃣ فحص جلب الطلبات من قاعدة البيانات:</h3>";
    $stmt = $pdo->query("SELECT id, status FROM orders LIMIT 1");
    $test_order = $stmt->fetch();

    if ($test_order) {
        echo "<span style='color: green; font-weight: bold;'>✔ تم الاتصال وجلب البيانات بنجاح!</span><br>";
        echo "رقم الطلب المتوفر للفحص: <b>#" . $test_order['id'] . "</b><br>";
        echo "حالة الطلب الحالية المخزنة في القاعدة هي: <span style='background: #fff3cd; padding: 2px 8px; border-radius: 4px; border: 1px solid #ffeeba;'>\"" . $test_order['status'] . "\"</span><br>";
        
        // 2. محاولة عمل تحديث تجريبي لحالة هذا الطلب
        echo "<h3>2️⃣ فحص تنفيذ أمر التحديث (UPDATE):</h3>";
        
        $order_id_to_update = $test_order['id'];
        $new_test_status = 'تم التأكيد والشحن';
        
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_test_status, $order_id_to_update]);
        
        if ($update_stmt->rowCount() > 0) {
            echo "<span style='color: green; font-weight: bold;'>✔ ممتاز! تم تحديث الحالة في قاعدة البيانات بنجاح وبدون أي مشاكل.</span><br>";
            echo "يرجى الانتقال الآن إلى لوحة التحكم والتأكد مما إذا كانت الأزرار قد اختفت وتحولت للحالة الخضراء.";
        } else {
            echo "<span style='color: orange; font-weight: bold;'>⚠ تم تنفيذ الاستعلام، ولكن لم يتغير شيء في قاعدة البيانات!</span><br>";
            echo "السبب المحتمل: الحالة في قاعدة البيانات كانت بالفعل مسجلة باسم 'تم التأكيد والشحن'، أو أن هناك قيوداً (Constraints) تمنع التعديل.";
        }

    } else {
        echo "<span style='color: red; font-weight: bold;'>❌ لا توجد أي طلبات مسجلة في جدول orders لإجراء الفحص عليها!</span><br>";
        echo "يرجى القيام بعملية شراء تجريبية من المتجر كعميل أولاً ثم إعادة تحميل هذه الصفحة.";
    }

} catch (PDOException $e) {
    // عرض الخطأ الصريح القادم من MySQL في حال وجود مشكلة في أسماء الحقول أو الجداول
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin-top: 10px;'>";
    echo "<h3>❌ خطأ برمي صريح في قاعدة البيانات (MySQL Error):</h3>";
    echo "<b>نص الخطأ:</b> " . $e->getMessage() . "<br><br>";
    echo "<b>توجيه للمناقشة:</b> تأكدي إن كان اسم الجدول هو <code style='background:#fff; padding:2px 5px;'>orders</code> واسم حقل الحالة هو <code style='background:#fff; padding:2px 5px;'>status</code> تماماً في الـ Database.";
    echo "</div>";
}

echo "</div>";
?>