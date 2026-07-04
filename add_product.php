<?php
require_once 'config.php';

// 1. نظام الصلاحيات والأمان: التحقق الصارم من أن المستخدم مسجل دخول وأنه "مدير" فقط
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

$title = $price = $quantity = $category_id = "";
$error = $success = "";

// جلب التصنيفات المتاحة لعرضها في القائمة المنسدلة ديناميكياً
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = []; 
}

// 2. معالجة البيانات عند إرسال النموذج (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {
    
    $title = sanitize($_POST['title']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    $image_name = ""; 

    if (empty($title) || $price <= 0 || $quantity < 0) {
        $error = "عذراً، يجب ملء جميع الحقول الإلزامية وبيانات السعر والكمية بشكل صحيح!";
    } else {
        
        // 🔒 معالجة رفع ملف الصورة بأمان
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_ext)) {
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                $image_name = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, 'uploads/' . $image_name);
            } else {
                $error = "امتداد الصورة غير مسموح! يرجى رفع ملفات بصيغة (JPG, JPEG, PNG, WEBP) فقط.";
            }
        } else {
            $error = "يرجى اختيار صورة للمنتج لإتمام عملية الإضافة.";
        }

        // 3. التخزين في قاعدة البيانات
        if (empty($error)) {
            $sql = "INSERT INTO products (title, price, quantity, category_id, image) VALUES (:title, :price, :quantity, :category_id, :image)";
            
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":price", $price);
                $stmt->bindParam(":quantity", $quantity);
                $stmt->bindParam(":category_id", $category_id, $category_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(":image", $image_name);
                
                if ($stmt->execute()) {
                    $success = "تمت إضافة قطعة الملابس وصورتها بنجاح إلى المخزون والموقع اللحظي!";
                    $title = $price = $quantity = $category_id = "";
                } else {
                    $error = "للأسف، حدث خطأ غير متوقع في النظام. يرجى المحاولة مرة أخرى لاحقاً.";
                }
                unset($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستودع | إضافة منتج جديد</title>
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Google Fonts: Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; }
        .form-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        .input-group-text { background-color: #f8f9fa; color: #6c757d; border-inline-end: none; }
        .form-control, .form-select { border-inline-start: none; }
        .form-control:focus, .form-select:focus { box-shadow: none; border-color: #dee2e6; }
        .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.15); border-radius: 0.375rem; }
        .upload-box { border: 2px dashed #dee2e6; border-radius: 12px; padding: 25px; text-align: center; background-color: #f8f9fa; transition: all 0.3s ease; }
        .upload-box:hover { border-color: #ffc107; background-color: #fff; }
    </style>
</head>
<body>

    <!-- شريط التنقل المميز -->
    <nav class="navbar navbar-dark bg-dark mb-5 shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="admin_dashboard.php">
                <i class="bi bi-sliders me-2"></i>لوحة تحكم المتجر
            </a>
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-right-short"></i> العودة للوحة الإدارة
            </a>
        </div>
    </nav>

    <div class="container mb-5" style="max-width: 750px;">
        <div class="card form-card p-4 p-md-5 bg-white">
            
            <div class="text-center mb-5">
                <div class="d-inline-block bg-warning-subtle text-warning p-3 rounded-circle mb-3">
                    <i class="bi bi-bag-plus-fill fs-2"></i>
                </div>
                <h2 class="fw-bold text-dark mb-1">إضافة قطعة ملابس جديدة</h2>
                <p class="text-muted small">قم بتعبئة البيانات ورفع الصورة ليتم تحديث واجهة الزبائن والمخزن فوراً.</p>
            </div>

            <!-- إشعارات النظام النظيفة -->
            <?php if(!empty($success)): ?> 
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-3 mb-4 rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> <strong>نجاح إداري:</strong> <?= $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div> 
            <?php endif; ?>
            
            <?php if(!empty($error)): ?> 
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-3 mb-4 rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i> <strong>تنبيه النظام:</strong> <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div> 
            <?php endif; ?>

            <!-- بداية النموذج المنسق بالكامل -->
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                
                <!-- حقل اسم المنتج -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary mb-2">اسم المنتج / قطعة الملابس <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-tags"></i></span>
                        <input type="text" name="title" class="form-control form-control-lg fs-6" placeholder="مثال: فستان سهرة مخمل، عباية مطرزة..." value="<?= sanitize($title); ?>" required autocomplete="off">
                    </div>
                </div>

                <!-- حقول السعر والكمية في صف واحد متناسق -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary mb-2">السعر بالدولار ($) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" step="0.01" name="price" class="form-control form-control-lg fs-6" placeholder="0.00" value="<?= sanitize($price); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary mb-2">الكمية المتاحة في المخزن <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                            <input type="number" name="quantity" class="form-control form-control-lg fs-6" placeholder="مثال: 25 قطعة" value="<?= sanitize($quantity); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
    <label class="form-label fw-bold text-secondary mb-2">تصنيف الملابس الرئيسي</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-grid"></i></span>
        <select name="category_id" class="form-select form-select-lg fs-6" required>
            <option value="">-- اختر التصنيف المناسب من القائمة --</option>
            
            <?php if (!empty($categories)): ?>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id']; ?>" <?= (isset($category_id) && $category_id == $cat['id']) ? 'selected' : ''; ?>>
                        <?= sanitize($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">لا توجد تصنيفات مضافة في قاعدة البيانات</option>
            <?php endif; ?>
            
        </select>
    </div>
</div>

                <!-- 📸 صندوق رفع الصورة الاحترافي والمنسق -->
                <div class="mb-5">
                    <label class="form-label fw-bold text-secondary mb-2">صورة قطعة الملابس المعروضة <span class="text-danger">*</span></label>
                    <div class="upload-box">
                        <i class="bi bi-cloud-arrow-up text-warning fs-1 mb-2 d-block"></i>
                        <span class="fw-bold text-dark d-block mb-1">اضغطي هنا لاختيار الصورة</span>
                        <span class="text-muted small d-block mb-3">امتدادات الحماية المدعومة: JPG, PNG, WEBP</span>
                        <input type="file" name="product_image" class="form-control" accept="image/*" required style="cursor: pointer;">
                    </div>
                </div>

                <!-- أزرار الإرسال والإلغاء بتصميم مريح -->
                <div class="row g-3">
                    <div class="col-sm-8">
                        <button type="submit" name="submit_product" class="btn btn-warning btn-lg fw-bold text-dark w-100 py-3 shadow-sm">
                            <i class="bi bi-cloud-plus-fill me-1"></i> حفظ ونشر القطعة في المتجر
                        </button>
                    </div>
                    <div class="col-sm-4">
                        <a href="admin_dashboard.php" class="btn btn-light btn-lg text-secondary border w-100 py-3">إلغاء</a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <footer class="text-center py-4 text-muted small">
        نظام إدارة متجر خيوط الأناقة الإلكتروني © 2026
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>