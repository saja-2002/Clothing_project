<?php
require_once 'config.php';

// التحقق من الأمان والصلاحيات لـ Admin فقط
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

$title = $price = $quantity = "";
$error = $success = "";

// التحقق من وجود معرف المنتج في الرابط (GET) لقراءة البيانات الحالية
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id = (int)trim($_GET["id"]);
    
    $sql = "SELECT * FROM products WHERE id = :id";
    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $id);
        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch();
                $title = $row["title"];
                $price = $row["price"];
                $quantity = $row["quantity"];
            } else {
                $error = "المنتج غير موجود.";
            }
        }
    }
}

// معالجة البيانات عند إرسال النموذج للتحديث (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_product"])) {
    $id = (int)$_POST["id"];
    $title = sanitize($_POST['title']);
    $price = sanitize($_POST['price']);
    $quantity = sanitize($_POST['quantity']);
    
    // التحقق من صحة المدخلات (Validation)
    if (empty($title) || empty($price) || empty($quantity)) {
        $error = "الرجاء ملء جميع الحقول المطلوبة!";
    } else {
        $sql = "UPDATE products SET title = :title, price = :price, quantity = :quantity WHERE id = :id";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":quantity", $quantity);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $success = "تم تحديث بيانات المنتج بنجاح!";
                header("refresh:2;url=admin_dashboard.php"); // العودة التلقائية للوحة التحكم بعد ثانيتين
            } else {
                $error = "حدث خطأ أثناء التحديث، حاول مجدداً.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل منتج ملابس</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body>
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow border-0 p-4">
            <h3 class="fw-bold text-center mb-4 text-warning">تعديل بيانات قطعة الملابس</h3>
            
            <?php if(!empty($success)): ?> <div class="alert alert-success"><?= $success; ?></div> <?php endif; ?>
            <?php if(!empty($error)): ?> <div class="alert alert-danger"><?= $error; ?></div> <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?= $id; ?>" method="post">
                <input type="hidden" name="id" value="<?= $id; ?>">
                
                <div class="mb-3">
                    <label class="form-label">اسم القطعة</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($title); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">السعر ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= sanitize($price); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">الكمية في المخزن</label>
                    <input type="number" name="quantity" class="form-control" value="<?= sanitize($quantity); ?>" required>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="update_product" class="btn btn-warning w-100 fw-bold">تحديث التعديلات</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary w-50">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>