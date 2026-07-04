<?php 
require_once 'config.php'; 

// جلب المنتجات من قاعدة البيانات للعرض الديناميكي
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 6");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر خيوط الأناقة | للملابس العصرية</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- إضافة مكتبة أيقونات بوتستراب لمظهر بروفايل احترافي -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            color: white; padding: 100px 0; text-align: center;
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none; border-radius: 15px; overflow: hidden;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .product-img {
            height: 340px;
            object-fit: cover;
            width: 100%;
        }
        .user-welcome-link {
            transition: color 0.2s ease;
        }
        .user-welcome-link:hover {
            color: #ffda6a !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="index.php">خيوط الأناقة</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="search_products.php">🔎 استعلام وببحث</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        
                        <!-- التحقق من دور المستخدم لتحديد وجهة الرابط عند الضغط على الاسم -->
                        <?php 
                        $profile_url = ($_SESSION["role"] === 'admin') ? 'admin_dashboard.php' : 'profile.php'; 
                        ?>
                        
                        <a href="<?= $profile_url; ?>" class="text-white text-decoration-none me-3 fw-bold user-welcome-link">
                            <i class="bi bi-person-circle text-warning me-1"></i> مرحباً، <?= sanitize($_SESSION["username"]); ?>
                        </a>

                        <?php if($_SESSION["role"] === 'admin'): ?>
                            <a href="admin_dashboard.php" class="btn btn-outline-warning btn-sm me-2">لوحة التحكم</a>
                        <?php endif; ?>
                        
                        <a href="logout.php" class="btn btn-danger btn-sm">تسجيل الخروج</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm me-2">تسجيل الدخول</a>
                        <a href="register.php" class="btn btn-warning btn-sm">حساب جديد</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero-section mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">اكتشف أناقتك الحقيقية اليوم</h1>
            <p class="lead mb-4">أحدث صيحات الموضة العالمية بين يديك وبأفضل الأسعار الإدارية المتميزة.</p>
            <a href="products_explore.php" class="btn btn-warning btn-lg px-4 gap-3 fw-bold">تسوق الآن</a>
        </div>
    </header>

    <main class="container my-5" id="products">
        <h2 class="text-center fw-bold mb-4 position-relative">أحدث التصاميم المضافة</h2>
        <div class="row g-4">
            <?php if(!empty($products)): ?>
                <?php foreach($products as $product): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 product-card shadow-sm">
                            
                            <?php 
                            if (!empty($product['image']) && file_exists('uploads/' . $product['image'])) {
                                $image_src = 'uploads/' . $product['image'];
                            } else {
                                $image_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=500&q=80'; 
                            }
                            ?>
                            <img src="<?= $image_src; ?>" class="product-img" alt="<?= sanitize($product['title']); ?>">
                            
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start">
                                    <?= sanitize($product['category_name'] ?? 'تنانير وفساتين'); ?>
                                </span>
                                
                                <h5 class="card-title fw-bold"><?= sanitize($product['title']); ?></h5>
                                <p class="card-text text-danger fw-bold fs-5 mb-3"><?= number_format($product['price'], 2); ?> $</p>
                                <p class="card-text text-muted small">المخزون المتوفر: <?= (int)$product['quantity']; ?> قطعة</p>
                                
                                <!-- 🛒 تم تعديل الرابط هنا ليوجه العميل مباشرة إلى صفحة المشتريات (my_orders.php) ممرراً معرف المنتج ومستعداً لإتمام الفاتورة -->
                                <a href="my_orders.php?action=add&id=<?= $product['id']; ?>" class="btn btn-dark mt-auto w-100 fw-bold">
                                    <i class="bi bi-cart-plus me-1"></i> شراء الآن
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info shadow-sm">لا توجد منتجات معروضة حالياً في قاعدة البيانات. توجه للوحة التحكم لإضافتها!</div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-4 mt-5 border-top border-warning border-3">
        <p class="mb-0">حقوق النشر © 2026 </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>