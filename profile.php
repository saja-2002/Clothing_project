<?php
require_once 'config.php';
require_once 'auth.php';

// استدعاء دالة التحقق من تسجيل الدخول
check_login(); 

// حماية الصفحة: منع غير المسجلين من الدخول
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$orders = [];

try {
    // 🔥 جلب كافة طلبات المشتريات الحالية الخاصة بهذا العميل مع تفاصيلها الفعلية المجمعة
    // استخدمنا GROUP_CONCAT لجمع المنتجات التابعة لنفس الفاتورة لكي لا يتكرر رقم الفاتورة في الجدول
    $sql = "SELECT o.id as order_id, o.total_price, o.status, o.created_at, 
                   GROUP_CONCAT(CONCAT(p.title, ' (x', od.quantity, ')') SEPARATOR '، ') as products_details
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            JOIN products p ON od.product_id = p.id
            WHERE o.user_id = :user_id 
            GROUP BY o.id
            ORDER BY o.id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // معالجة الأخطاء في حال حدوثها
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملفي الشخصي | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .profile-card { border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="index.php">خيوط الأناقة</a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">الرئيسية للمتجر</a>
                <a href="logout.php" class="btn btn-danger btn-sm">تسجيل خروج</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card profile-card p-4 text-center bg-white rounded-3">
                    <div class="bg-warning text-dark rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                        <?= strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                    </div>
                    <h4 class="fw-bold text-dark"><?= sanitize($_SESSION["username"]); ?></h4>
                    <span class="badge bg-secondary px-3 py-2 mt-1">حساب زبون (Customer)</span>
                    <hr class="my-4">
                </div>
            </div>

            <div class="col-md-8">
                <div class="card profile-card p-4 bg-white rounded-3">
                    <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-clock-history text-warning me-2"></i> سجل المشتريات والطلبات الحالية</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>القطع المشتراة تفصيلياً</th>
                                    <th>إجمالي المبلغ</th>
                                    <th>تاريخ الطلب</th>
                                    <th class="text-center">حالة الطلب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($orders)): ?>
                                    <?php foreach($orders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $order['order_id']; ?></td>
                                        <td class="text-secondary small fw-semibold"><?= sanitize($order['products_details']); ?></td>
                                        <td class="text-danger fw-bold"><?= number_format($order['total_price'], 2); ?> $</td>
                                        <td class="text-muted small"><?= $order['created_at']; ?></td>
                                        <td class="text-center">
                                            <?php 
                                            // ربط الحالة القادمة من قاعدة البيانات لتغيير لون الشارة تلقائياً للعميل
                                            $status = $order['status'];
                                            if ($status == 'قيد الانتظار' || $status == 'pending') {
                                                echo '<span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-bold">قيد الانتظار</span>';
                                            } elseif ($status == 'تم التأكيد والشحن') {
                                                echo '<span class="badge bg-success px-2.5 py-1.5 rounded-pill fw-bold">تم التأكيد والشحن</span>';
                                            } elseif ($status == 'ملغي') {
                                                echo '<span class="badge bg-danger px-2.5 py-1.5 rounded-pill fw-bold">ملغي</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary px-2.5 py-1.5 rounded-pill fw-bold">' . sanitize($status) . '</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-cart-x display-6 d-block mb-2 text-secondary"></i>
                                            لم تقم بإجراء أي عمليات شراء بعد. تسوق الآن عبر الصفحة الرئيسية!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>