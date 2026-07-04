<?php
// 1. بدء الجلسة للتمكن من الوصول إليها وتدميرها
session_start();

// 2. إلغاء تعيين كافة متغيرات الجلسة المخزنة في الذاكرة
$_SESSION = array();

// 3. مسح كوكيز الجلسة (Session Cookies) من متصفح المستخدم لزيادة الأمان
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. تدمير الجلسة نهائياً من السيرفر
session_destroy();

// 5. توجيه المستخدم فوراً إلى صفحة تسجيل الدخول
header("location: login.php");
exit;
?>