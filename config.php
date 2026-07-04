<?php
/**
 * مشروع تطوير نظم المعلومات الإدارية المتكاملة - متجر خيوط الأناقة 2026
 * ملف الاتصال المركزي بقاعدة البيانات وإدارة الجلسات الأمنية
 */

// 1. بدء الجلسة (Session) مركزياً وبشكل آمن في أول الملف لمنع تكرارها أو حدوث أخطاء بالمتصفح
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. تعريف ثوابت الاتصال بالسيرفر المحلي (Localhost)
define('DB_SERVER', 'localhost');
define('DB_PORT', '3307'); // تم إضافة ثابت خاص بالبورت الجديد
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // اتركيه فارغاً إذا كنتِ تستخدمين ستاك الـ XAMPP الافتراضي
define('DB_NAME', 'clothing_store');

try {
    // 3. إنشاء الاتصال بقاعدة البيانات باستخدام تقنية PDO المتقدمة وتحديد ترميز اللغة العربية utf8mb4 والبورت 3307
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USERNAME, 
        DB_PASSWORD
    );
    
    // 4. تفعيل وضع معالجة الأخطاء البرمجية الصارمة (PDO Error Mode Exception) للأمان والتحقق
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 5. ضبط الوضع الافتراضي لجلب البيانات على شكل مصفوفات ترابطية (Associative Arrays) لسهولة التعامل مع الكود
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // في حال حدوث خطأ في الاتصال، يتم إيقاف تشغيل الصفحة وعرض رسالة آمنة ومفهومة
    die("خطأ إداري: فشل النظام في الاتصال بقاعدة البيانات. " . $e->getMessage());
}

/**
 * 6. دالة تنظيف وتأمين مدخلات المستخدم (Sanitization / Validation Function)
 * تحمي الموقع تماماً من ثغرات حقن النصوص البرمجية الخبيثة (Cross-Site Scripting - XSS)
 * * @param string $data النص الخام القادم من النماذج (POST/GET)
 * @return string النص المنظف والآمن للاستخدام
 */
function sanitize($data) {
    if (is_null($data)) {
        return '';
    }
    // trim: تزيل الفراغات الزائدة من الأطراف
    // strip_tags: تحذف أي أوسمة برمجية مثل <script> أو <html> قد يرسلها المخترق
    // htmlspecialchars: تحول الرموز الخاصة إلى نصوص نصية آمنة للعرض بدون تنفيذ برمي
    return htmlspecialchars(strip_tags(trim($data)), ENT_NOQUOTES, 'UTF-8');
}
?>