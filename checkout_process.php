<?php
require_once 'config.php';
require_once 'auth.php';

// تأمين الصفحة: يجب أن يكون المستخدم مسجل دخول لإتمام الشراء
check_login();

// التحقق من أن السلة ليست فارغة وأن الطلب قادم عبر POST
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_SESSION['cart'])) {
    header("Location: my_orders.php");
    exit;
}

$success = false;
$error_message = "";
$order_items = $_SESSION['cart']; // الاحتفاظ بنسخة من المنتجات لعرض الفاتورة
$total_price = 0;
$user_id = $_SESSION["id"]; // جلب رقم معرف العميل الحالي من الجلسة

try {
    // بدء معاملة (Transaction) لضمان تنفيذ كافة العمليات معاً أو إلغائها في حال حدوث خطأ
    $pdo->beginTransaction();

    // 1. حساب إجمالي السعر الفعلي وفحص توفر المخزن قبل أي عملية إدخال
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $requested_qty = (int)$item['quantity'];
        $total_price += $item['price'] * $requested_qty;

        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['quantity'] < $requested_qty) {
            throw new Exception("عذراً، الكمية المطلوبة من صنف '" . $item['title'] . "' غير متوفرة حالياً في المخزن.");
        }
    }

    // 2. تسجيل الطلب الرئيسي أولاً في جدول orders بحالة "قيد الانتظار"
    // ملاحظة للجنة: نفترض هنا المسميات الافتراضية لأعمدة جدول الطلبات الرئيسي لديكِ
    $insert_order = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'قيد الانتظار')");
    $insert_order->execute([$user_id, $total_price]);
    
    // 🔥 جلب رقم (ID) الفاتورة/الطلب الذي تم توليده للحظة لربطه بالتفاصيل
    $order_id = $pdo->lastInsertId();

    // 3. حلقة تكرارية لتسجيل كل قطعة ملابس بجدول تفاصيل الطلبات وخصم مخزنها
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $requested_qty = (int)$item['quantity'];
        $product_price = $item['price'];

        // إدخال السجل التفصيلي في جدول تفاصيل الطلبات (order_details)
        $insert_detail = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $insert_detail->execute([$order_id, $product_id, $requested_qty, $product_price]);

        // تحديث وخصم الكمية من جدول المنتجات ديناميكياً
        $update_stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $update_stmt->execute([$requested_qty, $product_id]);
    }

    // إذا تمت كل العمليات بنجاح تام، يتم اعتماد التغييرات رسمياً في قاعدة البيانات
    $pdo->commit();
    $success = true;

    // 🧹 تفريغ سلة المشتريات بعد نجاح الحجز
    $_SESSION['cart'] = [];

} catch (Exception $e) {
    // في حال حدوث نقص بالمخزن أو خطأ بالـ SQL، يتم التراجع فوراً وإلغاء كل البيانات المدخلة
    $pdo->rollBack();
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حالة الطلب | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .status-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .success-icon { font-size: 4.5rem; color: #198754; }
        .error-icon { font-size: 4.5rem; color: #dc3545; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark mb-5 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="index.php">خيوط الأناقة</a>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 650px;">
        <div class="card status-card p-4 p-md-5 text-center bg-white">
            
            <?php if ($success): ?>
                <div class="success-icon mb-3"><i class="bi bi-check-circle-fill"></i></div>
                <h2 class="fw-bold text-success mb-2">تم إرسال طلبكِ بنجاح!</h2>
                <p class="text-muted mb-4">طلبكِ الآن مسجل وحالته <span class="badge bg-warning text-dark px-2 py-1 rounded-pill">قيد الانتظار</span> لحين تأكيده ومراجعته من قِبل الأدمن.</p>

                <div class="bg-light p-4 rounded-3 text-start mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt me-1 text-warning"></i> ملخص الفاتورة الفورية:</h5>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($order_items as $item): ?>
                            <li class="d-flex justify-content-between mb-2 border-bottom pb-2">
                                <span class="text-secondary"><?= sanitize($item['title']); ?> <small class="text-muted">(x<?= $item['quantity']; ?>)</small></span>
                                <span class="fw-bold text-dark"><?= number_format($item['price'] * $item['quantity'], 2); ?> $</span>
                            </li>
                        <?php endforeach; ?>
                        <li class="d-flex justify-content-between pt-2 fw-bold text-danger fs-5">
                            <span>المجموع الكلي المخصوم:</span>
                            <span><?= number_format($total_price, 2); ?> $</span>
                        </li>
                    </ul>
                </div>
                
                <div class="alert alert-warning border-0 small text-dark d-flex align-items-center justify-content-center">
                    <i class="bi bi-truck me-2 fs-5"></i> طريقة الدفع: الدفع عند الاستلام (COD) فور مراجعة الإدارة.
                </div>

            <?php else: ?>
                <div class="error-icon mb-3"><i class="bi bi-x-circle-fill"></i></div>
                <h2 class="fw-bold text-danger mb-2">عذراً، لم يكتمل الطلب!</h2>
                <p class="text-muted p-3 bg-danger-subtle text-danger rounded-3 fw-bold mb-4"><?= sanitize($error_message); ?></p>
                <p class="text-muted small">يمكنكِ العودة للسلة.</p>
            <?php endif; ?>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-3">
                <a href="index.php" class="btn btn-dark btn-lg px-4 fw-bold shadow-sm">العودة للرئيسية</a>
                <?php if (!$success): ?>
                    <a href="my_orders.php" class="btn btn-outline-secondary btn-lg px-4 fw-bold">العودة للسلة</a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5 border-top border-warning border-3">
        <p class="mb-0">حقوق النشر © 2026 | خيوط الأناقة</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>