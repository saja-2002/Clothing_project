<?php
require_once 'config.php';

$search_query = "";
$products = [];

// معالجة طلب الاستعلام (Search Query)
if (isset($_GET['search'])) {
    $search_query = sanitize($_GET['search']);
    // استخدام LIKE مع Prepared Statements لمنع ثغرات SQL Injection
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.title LIKE :search ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bindParam(":search", $search_param);
    $stmt->execute();
    $products = $stmt->fetchAll();
} else {
    // عرض كافة المنتجات في حال عدم وجود بحث
    $products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام الاستعلام عن الملابس</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <div class="container my-5">
        <div class="card shadow-sm border-0 p-4 mb-4">
            <h2 class="fw-bold mb-3 text-dark">محرك البحث  </h2>
            <form action="search_products.php" method="get" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control form-control-lg" placeholder="أدخل اسم قطعة الملابس للبحث عنها..." value="<?= sanitize($search_query); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold">استعلام فوري</button>
                </div>
            </form>
        </div>

        <div class="card shadow-sm border-0 p-4">
            <h4 class="fw-bold text-muted mb-3">نتائج البحث المسترجعة (<?= count($products); ?> قطعة)</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>المعرف</th>
                            <th>القطعة</th>
                            <th>التصنيف</th>
                            <th>السعر الحالي</th>
                            <th>المخزون المتوفر</th>
                            <?php if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                                <th>إجراءات المسؤول</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($products)): ?>
                            <?php foreach($products as $p): ?>
                            <tr>
                                <td><?= $p['id']; ?></td>
                                <td class="fw-bold"><?= sanitize($p['title']); ?></td>
                                <td><span class="badge bg-info text-dark"><?= sanitize($p['category_name'] ?? 'عام'); ?></span></td>
                                <td class="text-danger fw-bold"><?= number_format($p['price'], 2); ?> $</td>
                                <td><?= $p['quantity']; ?> قطع متوفرة</td>
                                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                                    <td>
                                        <a href="edit_product.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-warning">تعديل</a>
                                        <a href="admin_dashboard.php?delete=<?= $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد الحذف؟')">حذف</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">عذراً، لم يتم العثور على أي نتائج تطابق عملية البحث الحالية.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-secondary">العودة للواجهة الرئيسية</a>
        </div>
    </div>
</body>
</html>