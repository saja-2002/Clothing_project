<?php
// 1. بدء الجلسة واستدعاء ملف الاتصال المركزي
require_once 'config.php';

// 2. استعلام القراءة لجلب الملابس والمنتجات من قاعدة البيانات
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $all_products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في النظام: فشل جلب المنتجات. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المتجر | استكشفي أحدث الموديلات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .product-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .price-tag {
            font-size: 1.25rem;
            color: #dc3545;
            font-weight: 700;
        }
        /* تنسيق لتثبيت أبعاد وحجم الصور لتبدو متناسقة */
        .product-img-container {
            width: 100%;
            height: 250px;
            overflow: hidden;
            border-radius: 10px;
            background-color: #f8f9fa;
        }
        .product-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3 mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-scissors me-2"></i>خيوط الأناقة</a>
            <a href="index.php" class="btn btn-outline-light btn-sm fw-bold px-3">الرئيسية</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">كتالوج الملابس والأناقة ✨</h2>
            <p class="text-muted">اكتشفي تشكيلتنا الفريدة والمميزة المتاحة في المخزن حالياً</p>
        </div>

        <div class="row g-4">
            <?php if(count($all_products) > 0): ?>
                <?php foreach($all_products as $product): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 product-card shadow-sm p-3 bg-white">
                            
                            <div class="product-img-container mb-3">
                                <?php if(!empty($product['image'])): ?>
                                    <img src="uploads/<?= $product['image']; ?>" alt="<?= $product['title']; ?>">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-0 d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="card-title fw-bold text-dark mb-2"><?= $product['title']; ?></h5>
                                    
                                    <p class="card-text small mb-3">
                                        <?php if($product['quantity'] > 0): ?>
                                            <span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i> متوفر في المخزن</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> نفدت الكمية حالياً</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price-tag"><?= number_format($product['price'], 2); ?> $</span>
                                    
                                    <?php if($product['quantity'] > 0): ?>
                                        <a href="checkout_process.php?id=<?= $product['id']; ?>" class="btn btn-sm btn-dark fw-bold px-3 py-2 rounded-2">
                                            <i class="bi bi-cart-plus me-1"></i> شراء
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-dark fw-bold px-3 py-2 rounded-2 disabled">
                                            <i class="bi bi-x-circle me-1"></i> غير متوفر
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-4">🛍️ لا توجد منتجات معروضة في المتجر حالياً.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>