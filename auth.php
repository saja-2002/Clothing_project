<?php
/**
 * ملف نظام الحماية والصلاحيات (Authentication & Authorization Guard)
 * متجر خيوط الأناقة - 2026
 */

// التأكد من بدء الجلسة (Session) بأمان في حال لم تكن قد بدأت بعد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * دالة حماية الصفحات العامة التي تتطلب تسجيل دخول فقط (مثل: الملف الشخصي، سلة المشتريات)
 * إذا لم يكن المستخدم مسجلاً، يتم تحويله فوراً لصفحة تسجيل الدخول.
 */
function check_login() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("Location: login.php");
        exit;
    }
}

/**
 * دالة حماية صفحات لوحة التحكم الإدارية (Admin Dashboard)
 * تمنع أي عميل عادي أو زائر من الدخول، وإذا حاول الدخول يتم تحويله لصفحة هبوط آمنة أو الصفحة الرئيسية.
 */
function check_admin() {
    // 1. التحقق أولاً من أنه مسجل دخول
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("Location: login.php");
        exit;
    }
    
    // 2. التحقق من أن دور المستخدم هو "admin" فعلياً
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
        // إذا كان عميلاً عادياً وحاول الدخول لصفحات الأدمن، يتم توجيهه للرئيسية منعاً للاختراق
        header("Location: index.php?error=unauthorized");
        exit;
    }
}

/**
 * دالة تنظيف البيانات (Sanitization) لحماية الموقع من ثغرات الـ XSS (Cross-Site Scripting)
 * تُستخدم عند عرض أي نص قادم من قاعدة البيانات أو المدخلات.
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
?>