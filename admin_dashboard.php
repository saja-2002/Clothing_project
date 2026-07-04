<?php
// استدعاء ملف الإعدادات والاتصال
require_once 'config.php';


require_once 'auth.php';
check_admin(); // استدعاء دالة التحقق من صلاحيات الأدمن


// التحقق الصارم من الأمان والصلاحيات لـ Admin فقط
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

$success = $error = "";
$tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'dashboard';

// جلب التصنيفات المتاحة لعرضها في القائمة المنسدلة ديناميكياً مع ميزة الإنشاء التلقائي إن كانت فارغة
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    
    // فحص ذكي: إذا كان جدول التصنيفات فارغاً تماماً في قاعدة البيانات، نقوم بملئه بالتصنيفات الأساسية تلقائياً
    if (count($categories) === 0) {
        $default_cats = ['فساتين', 'بلايز', 'أطقم'];
        $insert_cat_stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        foreach ($default_cats as $cat_title) {
            $insert_cat_stmt->execute([$cat_title]);
        }
        // جلب البيانات مجدداً بعد إدخالها
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    }
} catch (PDOException $e) {
    $categories = []; 
}


// كود التقاط ومعالجة إجراءات طلبات الشراء للأدمن
if (isset($_GET['action']) && isset($_GET['order_id']) && isset($_GET['tab']) && $_GET['tab'] == 'orders') {
    $order_id = (int)$_GET['order_id'];
    $action = $_GET['action'];
    
    $new_status = '';
    if ($action === 'confirm') {
        $new_status = 'تم التأكيد والشحن'; // أو يمكنكِ كتابة 'مكتمل' حسب رغبتك في قاعدة البيانات
    } elseif ($action === 'cancel') {
        $new_status = 'ملغي';
    }

    if (!empty($new_status)) {
        try {
            // تحديث حالة الطلب في قاعدة البيانات فعلياً
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            
            // إعادة توجيه لنفس الصفحة لتحديث البيانات واختفاء الأزرار فوراً
            header("Location: admin_dashboard.php?tab=orders");
            exit;
        } catch (PDOException $e) {
            // معالجة الأخطاء إن وجدت
        }
    }
}

// متغيرات مخصصة لعملية التعديل
$edit_product = null;
if ($tab == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_product = $stmt->fetch();
    if (!$edit_product) {
        header("location: admin_dashboard.php?tab=products");
        exit;
    }
}

// 1. معالجة الإضافة (Create) مع رفع الصورة من اللوحة الجانبية
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $title = sanitize($_POST['title']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    $image_name = ""; 
    
    if (empty($title) || $price <= 0 || $quantity < 0) {
        $error = "الرجاء ملء جميع الحقول المطلوبة ببيانات صحيحة!";
    } else {
        // فحص رفع ملف الصورة
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            // إضافة امتداد jfif لقائمة الامتدادات المسموحة
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'jfif'];
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
                $error = "امتداد الصورة غير مسموح! يرجى رفع (JPG, JPEG, PNG, WEBP, JFIF) فقط.";
            }
        } else {
            $error = "يرجى اختيار صورة للمنتج لإتمام عملية الإضافة.";
        }

        // إذا لم يكن هناك خطأ يتم الحفظ في قاعدة البيانات
        if (empty($error)) {
            $sql = "INSERT INTO products (title, price, quantity, category_id, image) VALUES (:title, :price, :quantity, :category_id, :image)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':title' => $title, ':price' => $price, ':quantity' => $quantity, ':category_id' => $category_id, ':image' => $image_name])) {
                $success = "تم إضافة قطعة الملابس وصورتها بنجاح!";
                $tab = 'products';
            } else {
                $error = "حدث خطأ ما أثناء الإضافة.";
            }
        }
    }
}

// 2. معالجة التعديل (Update) مع إمكانية تحديث الصورة
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $id = intval($_POST['id']);
    $title = sanitize($_POST['title']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $existing_image = sanitize($_POST['existing_image']);
    
    if (empty($title) || $price <= 0 || $quantity < 0) {
        $error = "الرجاء ملء جميع الحقول ببيانات صحيحة!";
        $tab = 'edit';
    } else {
        $image_name = $existing_image;

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            // إضافة امتداد jfif لقائمة الامتدادات المسموحة هنا أيضاً لمنع المشكلة عند التعديل
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'jfif'];
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_ext)) {
                $image_name = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, 'uploads/' . $image_name);
                
                if (!empty($existing_image) && file_exists('uploads/' . $existing_image)) {
                    unlink('uploads/' . $existing_image);
                }
            } else {
                $error = "امتداد الصورة غير مسموح! يرجى رفع (JPG, JPEG, PNG, WEBP, JFIF) فقط.";
                $tab = 'edit';
            }
        }

        if (empty($error)) {
            $sql = "UPDATE products SET title = :title, price = :price, quantity = :quantity, category_id = :category_id, image = :image WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':title' => $title, ':price' => $price, ':quantity' => $quantity, ':category_id' => $category_id, ':image' => $image_name, ':id' => $id])) {
                $success = "تم تحديث بيانات القطعة والصورة بنجاح!";
                $tab = 'products';
            } else {
                $error = "فشل تحديث البيانات.";
            }
        }
    }
}
try {
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'قيد الانتظار'");
    $pending_orders_count = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    $pending_orders_count = 0;
}

// 3. معالجة الحذف (Delete)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $img = $stmt->fetchColumn();
    if (!empty($img) && file_exists('uploads/' . $img)) {
        unlink('uploads/' . $img);
    }

    $sql = "DELETE FROM products WHERE id = :id";
    $pdo->prepare($sql)->execute([':id' => $id]);
    $success = "تم حذف المنتج وصورته نهائياً!";
    $tab = 'products';
}

// 4. الاستعلامات العامة للقراءة والإحصاء
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$total_products = count($products);
$out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn();
$total_stock_pieces = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الذكية | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; overflow-x: hidden; }
        .sidebar { position: fixed; top: 0; bottom: 0; right: 0; width: 260px; background-color: #1e293b; color: #fff; z-index: 1000; padding-top: 20px; }
        .sidebar .nav-link { color: #94a3b8; font-weight: 600; padding: 12px 20px; margin: 5px 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: #0f172a; }
        .sidebar .nav-link.active { background-color: #3b82f6; }
        .main-content { margin-right: 260px; padding: 40px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .upload-box { border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; text-align: center; background-color: #f8f9fa; cursor: pointer; transition: all 0.3s ease; }
        .upload-box:hover { border-color: #3b82f6; background-color: #fff; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="px-4 mb-4 text-center">
            <h4 class="fw-bold text-white mb-1"><i class="bi bi-scissors me-2"></i>خيوط الأناقة</h4>
            <span class="badge bg-success small">لوحة الإدارة الكاملة</span>
        </div>
        <hr class="border-secondary">
        <nav class="nav flex-column">
            <a class="nav-link <?= $tab == 'dashboard' ? 'active' : ''; ?>" href="admin_dashboard.php?tab=dashboard">
                <i class="bi bi-speedometer2 fs-5"></i> الرئيسية والإحصاء
            </a>
            <a class="nav-link <?= $tab == 'add' ? 'active' : ''; ?>" href="admin_dashboard.php?tab=add">
                <i class="bi bi-plus-circle fs-5"></i> إضافة منتج جديد
            </a>
            <a class="nav-link <?= $tab == 'products' ? 'active' : ''; ?>" href="admin_dashboard.php?tab=products">
                <i class="bi bi-box-seam fs-5"></i> إدارة وعرض المخزون
            </a>
            <hr class="border-secondary my-3">
            <a class="nav-link text-warning" href="index.php">
                <i class="bi bi-house fs-5"></i> عرض المتجر الأساسي
            </a>
            <a class="nav-link text-danger mt-5" href="logout.php">
                <i class="bi bi-box-arrow-right fs-5"></i> خروج من النظام
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <?php if(!empty($success)): ?> <div class="alert alert-success card-custom py-3 mb-4 text-center fw-bold border-0 shadow-sm"><?= $success; ?></div> <?php endif; ?>
        <?php if(!empty($error)): ?> <div class="alert alert-danger card-custom py-3 mb-4 text-center fw-bold border-0 shadow-sm"><?= $error; ?></div> <?php endif; ?>

        <?php if($tab == 'dashboard'): ?>
            <div class="mb-4">
                <h2 class="fw-bold text-dark">مرحباً بكِ مجدداً، الإدارة 💻</h2>
                <p class="text-muted">مراقبة فورية للمخزون والمنتجات المدعومة بالصور.</p>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-md-4"><div class="card card-custom p-4 bg-white border-start border-primary border-4"><h6>إجمالي المنتجات</h6><h2><?= $total_products; ?> أصنف</h2></div></div>
                <div class="col-md-4"><div class="card card-custom p-4 bg-white border-start border-success border-4"><h6>إجمالي القطع المتاحة</h6><h2><?= $total_stock_pieces; ?> قطعة</h2></div></div>
                <div class="col-md-4"><div class="card card-custom p-4 bg-white border-start border-danger border-4"><h6>منتجات نفدت</h6><h2><?= $out_of_stock; ?> صنف</h2></div></div>
            </div>
        <?php endif; ?>

        <?php if($tab == 'add'): ?>
            <div class="card card-custom p-4 p-md-5 bg-white">
                <h4 class="fw-bold text-dark border-bottom pb-3 mb-4"><i class="bi bi-bag-plus me-2 text-primary"></i>إضافة قطعة ملابس جديدة للمتجر</h4>
                
                <form action="admin_dashboard.php?tab=add" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">اسم الفستان / القطعة <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: فستان سهرة مخمل" required autocomplete="off">
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">السعر النقدي ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">الكمية المتاحة في الرفوف <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">تصنيف الملابس <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- اختر التصنيف الحقيقي للمنتج --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= sanitize($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">صورة المنتج الحقيقية <span class="text-danger">*</span></label>
                        <div class="upload-box">
                            <i class="bi bi-image text-muted fs-2 d-block mb-2"></i>
                            <input type="file" name="product_image" class="form-control" accept="image/*" required>
                            <div class="form-text small text-muted mt-1">الصيغ المدعومة للنظام: JPG, PNG, WEBP, JFIF فقط.</div>
                        </div>
                    </div>

                    <button type="submit" name="add_product" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">حفظ القطعة وإدراجها فوراً</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if($tab == 'edit' && $edit_product): ?>
            <div class="card card-custom p-4 p-md-5 bg-white">
                <h4 class="fw-bold text-warning border-bottom pb-3 mb-4"><i class="bi bi-pencil-square me-2"></i>تعديل بيانات وصورة المنتج</h4>
                <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $edit_product['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?= $edit_product['image']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">اسم المنتج</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_product['title']); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">السعر ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $edit_product['price']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">الكمية</label>
                            <input type="number" name="quantity" class="form-control" value="<?= $edit_product['quantity']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">تعديل التصنيف</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- اختر التصنيف --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= ($edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?= sanitize($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">استبدال الصورة الحالية (اختياري)</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        <div class="form-text small text-muted mt-1">الصيغ المدعومة للنظام: JPG, PNG, WEBP, JFIF فقط.</div>
                    </div>
                    <button type="submit" name="update_product" class="btn btn-warning fw-bold px-4 py-2 shadow-sm text-dark">حفظ التغييرات</button>
                    <a href="admin_dashboard.php?tab=products" class="btn btn-light border ms-2">إلغاء</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
            <a href="admin_dashboard.php?tab=dashboard" class="btn <?= ($tab == 'dashboard' || $tab == 'products') ? 'btn-dark' : 'btn-outline-dark'; ?> fw-bold">
                <i class="bi bi-grid me-1"></i> إدارة المنتجات والمخزون
            </a>
            <a href="admin_dashboard.php?tab=orders" class="btn <?= ($tab == 'orders') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark'; ?> fw-bold">
                <i class="bi bi-bag-check-fill me-1"></i> إدارة مشتريات العملاء
                <?php 
                try {
                    $stmt_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'قيد الانتظار'");
                    $pending_count = $stmt_count->fetchColumn();
                    if ($pending_count > 0) {
                        echo '<span class="badge bg-danger ms-1 rounded-pill">' . $pending_count . '</span>';
                    }
                } catch (PDOException $e) { }
                ?>
            </a>
        </div>

        <?php if($tab == 'products' || $tab == 'dashboard'): ?>
            <div class="card card-custom p-4 bg-white mt-3 shadow-sm border-0 rounded-3">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-box-seam text-warning me-2"></i> إدارة وجرد منتجات المخزون</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>الصورة</th>
                                <th>اسم القطعة</th>
                                <th>السعر</th>
                                <th>المخزون</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($products) > 0): ?>
                                <?php foreach($products as $p): ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($p['image']) && file_exists('uploads/' . $p['image'])): ?>
                                            <img src="uploads/<?= $p['image']; ?>" alt="صورة" class="rounded border shadow-xs" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light text-muted rounded text-center border d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-image small"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-secondary"><?= sanitize($p['title']); ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($p['price'], 2); ?> $</td>
                                    <td>
                                        <?php if($p['quantity'] > 0): ?>
                                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">متوفر (<?= $p['quantity']; ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">نفدت</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <a href="admin_dashboard.php?tab=edit&id=<?= $p['id']; ?>" class="btn btn-sm btn-warning text-dark me-1"><i class="bi bi-pencil-square"></i></a>
                                        <a href="admin_dashboard.php?delete=<?= $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنتِ متأكدة من الحذف الفوري لهذه القطعة وصورتها؟')"><i class="bi bi-trash3"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">المستودع فارغ تماماً.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($tab == 'orders'): ?>
            <div class="card card-custom p-4 bg-white mt-3 shadow-sm border-0 rounded-3">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-receipt text-warning me-2"></i> مراجعة فواتير وحالة مشتريات العملاء</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>رقم الطلب</th>
                                <th>اسم العميل</th>
                                <th>القطع المطلوبة تفصيلياً</th>
                                <th>إجمالي المبلغ</th>
                                <th class="text-center">حالة الطلب الحالية</th>
                                <th class="text-center">إجراء الإدارة (تأكيد الطلب)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $orders_query = "
                                    SELECT o.*, u.username, 
                                    GROUP_CONCAT(CONCAT(p.title, ' (x', od.quantity, ')') SEPARATOR '، ') AS items_details
                                    FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    JOIN order_details od ON o.id = od.order_id
                                    JOIN products p ON od.product_id = p.id
                                    GROUP BY o.id 
                                    ORDER BY o.id DESC
                                ";
                                $all_orders = $pdo->query($orders_query)->fetchAll();
                            } catch (PDOException $e) {
                                $all_orders = [];
                            }

                            if (count($all_orders) > 0):
                                foreach ($all_orders as $order):
                                    $check_status = trim(strtolower($order['status'])); 
                                    ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $order['id']; ?></td>
                                        <td><i class="bi bi-person text-muted me-1"></i> <?= sanitize($order['username']); ?></td>
                                        <td class="small text-secondary fw-semibold"><?= sanitize($order['items_details']); ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($order['total_price'], 2); ?> $</td>
                                        
                                        <td class="text-center">
                                            <?php if (empty($order['status']) || $check_status == 'pending' || $order['status'] == 'قيد الانتظار'): ?>
                                                <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-bold">
                                                    <i class="bi bi-hourglass-split me-1"></i> قيد الانتظار
                                                </span>
                                            <?php elseif ($check_status == 'confirmed' || $check_status == 'completed' || $order['status'] == 'تم التأكيد والشحن' || $order['status'] == 'مكتمل'): ?>
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
                                            <?php if (empty($order['status']) || $check_status == 'pending' || $order['status'] == 'قيد الانتظار'): ?>
                                                <a href="admin_dashboard.php?tab=orders&action=confirm&order_id=<?= $order['id']; ?>" class="btn btn-sm btn-success fw-bold me-1 px-3 shadow-sm">
                                                    <i class="bi bi-check-lg me-1"></i> تأكيد وقبول
                                                </a>
                                                <a href="admin_dashboard.php?tab=orders&action=cancel&order_id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-danger fw-bold px-2 shadow-sm" onclick="return confirm('هل أنتِ متأكدة من إلغاء هذا الطلب؟')">
                                                    <i class="bi bi-x-lg"></i> إلغاء
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small bg-light px-3 py-1.5 rounded-3 border d-inline-block">
                                                    <i class="bi bi-shield-check text-success me-1"></i> تمت معالجته وحفظه
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                tracking_orders_loop_end:
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد أي مشتريات مسجلة حالياً.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
</body>
</html>