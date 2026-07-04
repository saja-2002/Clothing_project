<?php
// تفعيل عرض الأخطاء لضمان تتبع أي مشكلة أثناء التطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'auth.php';

// تأمين الصفحة: التحقق من أن المستخدم هو الأدمن فقط
// check_admin(); 

// ⚡ 1. كود المعالجة والتحديث المدمج في نفس الصفحة
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $action = $_GET['action'];
    
    $new_status = '';
    if ($action === 'confirm') {
        $new_status = 'تم التأكيد والشحن';
    } elseif ($action === 'cancel') {
        $new_status = 'ملغي';
    }

    if (!empty($new_status) && $order_id > 0) {
        try {
            // تحديث حقل الحالة مباشرة بجدول orders
            $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $order_id]);
            
            // إنعاش الصفحة باستخدام JavaScript للتخلص من بارامترات الرابط ورؤية النتيجة فوراً
            echo "<script>window.location.href='admin_orders.php';</script>";
            exit;
        } catch (PDOException $e) {
            die("<div class='alert alert-danger text-center m-3'>خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "</div>");
        }
    }
}

// 📋 2. جلب الطلبات مع تفاصيل القطع والكميات من جدول التفاصيل
try {
    $query = "
        SELECT o.*, u.username, 
        GROUP_CONCAT(CONCAT(p.title, ' (x', od.quantity, ')') SEPARATOR '، ') AS purchased_items
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        JOIN order_details od ON o.id = od.order_id
        JOIN products p ON od.product_id = p.id
        GROUP BY o.id 
        ORDER BY o.id DESC
    ";
    $orders = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات المشترين | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; }
        .orders-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .table { vertical-align: middle; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="admin_dashboard.php">لوحة تحكم خيوط الأناقة</a>
            <a href="admin_dashboard.php" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> العودة للوحة الرئيسية
            </a>
        </div>
    </nav>

    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="bi bi-receipt text-warning me-2"></i> مراجعة فواتير وحالة مشتريات العملاء</h2>
            <span class="badge bg-dark px-3 py-2 rounded-pill">إجمالي الفواتير: <?= count($orders); ?></span>
        </div>

        <div class="card orders-card p-4 bg-white shadow-sm">
            <?php if (!empty($orders)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>رقم الطلب</th>
                                <th>اسم العميل</th>
                                <th>القطع المطلوبة تفصيلياً</th>
                                <th>إجمالي الفاتورة</th>
                                <th>تاريخ الطلب</th>
                                <th class="text-center">حالة الطلب الحالية</th>
                                <th class="text-center">إجراء الإدارة (تأكيد الطلب)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $db_status = isset($order['status']) ? trim($order['status']) : '';
                                $check_status = strtolower($db_status); 
                                ?>
                                <tr>
                                    <td class="fw-bold">#<?= $order['id']; ?></td>
                                    <td><i class="bi bi-person text-muted me-1"></i> <?= sanitize($order['username']); ?></td>
                                    <td><span class="text-secondary small fw-semibold"><?= sanitize($order['purchased_items']); ?></span></td>
                                    <td class="fw-bold text-danger"><?= number_format($order['total_price'], 2); ?> $</td>
                                    <td class="text-muted small"><?= $order['created_at']; ?></td>
                                    
                                    <td class="text-center">
                                        <?php if (empty($db_status) || $check_status == 'pending' || $db_status == 'قيد الانتظار'): ?>
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-bold">
                                                <i class="bi bi-hourglass-split me-1"></i> قيد الانتظار
                                            </span>
                                        <?php elseif ($check_status == 'confirmed' || $check_status == 'completed' || $db_status == 'تم التأكيد والشحن' || $db_status == 'مكتمل'): ?>
                                            <span class="badge bg-success px-2.5 py-1.5 rounded-pill fw-bold">
                                                <i class="bi bi-check-circle-fill me-1"></i> تم التأكيد والشحن
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-2.5 py-1.5 rounded-pill fw-bold">
                                                <i class="bi bi-x-circle-fill me-1"></i> ملغي
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center text-nowrap">
                                        <?php if (empty($db_status) || $check_status == 'pending' || $db_status == 'قيد الانتظار'): ?>
                                            <a href="admin_orders.php?action=confirm&order_id=<?= $order['id']; ?>" class="btn btn-sm btn-success fw-bold me-1 px-3 shadow-sm">
                                                <i class="bi bi-check-lg me-1"></i> تأكيد وقبول
                                            </a>
                                            <a href="admin_orders.php?action=cancel&order_id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-danger fw-bold px-2 shadow-sm" onclick="return confirm('هل أنتِ متأكدة من إلغاء هذا الطلب؟')">
                                                <i class="bi bi-x-lg"></i> إلغاء
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small bg-light px-3 py-1.5 rounded-3 border d-inline-block">
                                                <i class="bi bi-shield-check text-success me-1"></i> تمت معالجته وحفظه
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-3 d-block mb-3 text-secondary"></i>
                    <h5>لا توجد طلبات شراء مسجلة من قِبل العملاء حتى الآن!</h5>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-4 mt-5 border-top border-warning border-3">
        <p class="mb-0">لوحة تحكم خيوط الأناقة الإلكترونية © 2026</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>