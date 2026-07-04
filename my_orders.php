<?php
require_once 'config.php';

// التأكد من بدء الجلسة لتخزين عناصر السلة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إنشاء مصفوفة السلة في الجلسة إذا لم تكن موجودة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 🛒 استقبال وإضافة المنتج عند الضغط على "شراء الآن" من الصفحة الرئيسية
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    // جلب بيانات المنتج من قاعدة البيانات للتأكد من وجوده وصحة سعره
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // إذا كان المنتج موجوداً مسبقاً في السلة، قم بزيادة الكمية المطلوبة
            if (isset($_SESSION['cart'][$product_id])) {
                if ($_SESSION['cart'][$product_id]['quantity'] < $product['quantity']) {
                    $_SESSION['cart'][$product_id]['quantity']++;
                }
            } else {
                // إضافة المنتج لأول مرة في السلة
                $_SESSION['cart'][$product_id] = [
                    'title' => $product['title'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'category_name' => $product['category_name'] ?? 'تنانير وفساتين',
                    'quantity' => 1,
                    'max_quantity' => $product['quantity']
                ];
            }
        }
    } catch (PDOException $e) {
        // معالجة الأخطاء في حال حدوثها
    }
    
    // إعادة توجيه لنفس الصفحة لتنظيف الـ URL ومنع تكرار الإضافة عند تحديث الصفحة
    header("Location: my_orders.php");
    exit;
}

// ❌ حذف منتج معين من السلة
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $remove_id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header("Location: my_orders.php");
    exit;
}

// 🧹 تفريغ السلة بالكامل
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: my_orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة المشتريات | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .cart-img { width: 80px; height: 100px; object-fit: cover; border-radius: 8px; }
        .table-responsive { background: white; border-radius: 15px; overflow: hidden; }
        .summary-card { border: none; border-radius: 15px; background: white; }
    </style>
</head>
<body>

    <!-- شريط التنقل (Navbar) متناسق -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="index.php">خيوط الأناقة</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-right me-1"></i> العودة للمتجر
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <h2 class="fw-bold text-dark mb-4"><i class="bi bi-bag-heart text-warning me-2"></i> سلة مشترياتك</h2>

        <div class="row g-4">
            <!-- قسم عرض المنتجات المضافة للسلة -->
            <div class="col-lg-8">
                <?php if (!empty($_SESSION['cart'])): ?>
                    <div class="table-responsive shadow-sm p-3">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light border-bottom">
                                <tr>
                                    <th scope="col">المنتج</th>
                                    <th scope="col">التصنيف</th>
                                    <th scope="col">السعر</th>
                                    <th scope="col" class="text-center">الكمية</th>
                                    <th scope="col">الإجمالي</th>
                                    <th scope="col" class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_cart_price = 0;
                                foreach ($_SESSION['cart'] as $id => $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $total_cart_price += $item_total;
                                    
                                    if (!empty($item['image']) && file_exists('uploads/' . $item['image'])) {
                                        $img_src = 'uploads/' . $item['image'];
                                    } else {
                                        $img_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=500&q=80';
                                    }
                                ?>
                                    <tr class="border-bottom">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $img_src; ?>" class="cart-img shadow-sm" alt="<?= sanitize($item['title']); ?>">
                                                <span class="fw-bold text-dark"><?= sanitize($item['title']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5"><?= sanitize($item['category_name']); ?></span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= number_format($item['price'], 2); ?> $</td>
                                        <td class="text-center fw-bold text-muted"><?= $item['quantity']; ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($item_total, 2); ?> $</td>
                                        <td class="text-center">
                                            <a href="my_orders.php?action=remove&id=<?= $id; ?>" class="btn btn-outline-danger btn-sm border-0" title="حذف المنتج">
                                                <i class="bi bi-trash3-fill fs-5"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                            <a href="my_orders.php?action=clear" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> تفريغ السلة بالكامل
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info shadow-sm p-4 text-center border-0 rounded-4">
                        <i class="bi bi-cart-x display-4 text-secondary mb-3 d-block"></i>
                        <h5>سلة المشتريات فارغة تماماً حالياً!</h5>
                        <p class="text-muted small mb-3">تصفحي الصفحة الرئيسية واختاري أجمل التصاميم لإضافتها هنا.</p>
                        <a href="index.php" class="btn btn-warning fw-bold px-4">تصفح الملابس الآن</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- قسم ملخص الفاتورة وإتمام الطلب -->
            <div class="col-lg-4">
                <div class="card summary-card shadow-sm p-4">
                    <h4 class="fw-bold text-dark border-bottom pb-3 mb-3">ملخص الفاتورة</h4>
                    
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">إجمالي المنتجات:</span>
                            <span class="fw-bold text-dark"><?= count($_SESSION['cart']); ?> عناصر</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <span class="text-muted">تكلفة الشحن:</span>
                            <span class="text-success fw-bold">شحن مجاني</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-5 fw-bold text-dark">المجموع الكلي:</span>
                            <span class="fs-4 fw-bold text-danger"><?= number_format($total_cart_price, 2); ?> $</span>
                        </div>

                        <!-- نموذج تأكيد الحجز النهائي للجنة المناقشة -->
                        <form action="checkout_process.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">طريقة الدفع المتوفرة</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cod">الدفع عند الاستلام (COD)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm">
                                <i class="bi bi-credit-card-2-front me-1"></i> تأكيد الحجز وشراء الآن
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">أضيفي ملابس للسلة لتفعيل خيارات الفاتورة وحساب السعر تلقائياً.</p>
                        <button class="btn btn-secondary w-100 disabled" disabled>تأكيد الطلب</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-4 mt-5 border-top border-warning border-3">
        <p class="mb-0">حقوق النشر © 2026 | خيوط الأناقة</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>