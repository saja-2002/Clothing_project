<?php
require_once 'config.php';

// إذا كان المستخدم مسجل دخوله بالفعل، يتم توجيهه للرئيسية
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. التحقق من اسم المستخدم (Validation)
    if (empty(trim($_POST["username"]))) {
        $username_err = "الرجاء إدخال اسم المستخدم.";
    } else {
        // التحقق من عدم تكرار الاسم في قاعدة البيانات باستخدام Prepared Statement
        $sql = "SELECT id FROM users WHERE username = :username";
        if ($stmt = $pdo->prepare($sql)) {
            $param_username = sanitize($_POST["username"]);
            $stmt->bindParam(":username", $param_username);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $username_err = "اسم المستخدم هذا مأخوذ بالفعل.";
                } else {
                    $username = $param_username;
                }
            }
            unset($stmt);
        }
    }
    
    // 2. التحقق من كلمة المرور
    if (empty(trim($_POST["password"]))) {
        $password_err = "الرجاء إدخال كلمة المرور.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "يجب أن تحتوي كلمة المرور على 6 رموز على الأقل.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // 3. تأكيد كلمة المرور
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "الرجاء تأكيد كلمة المرور.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "كلمات المرور غير متطابقة.";
        }
    }
    
    // 4. إدخال البيانات في قاعدة البيانات إذا خلت الأخطاء
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, password, role) VALUES (:username, :password, 'customer')";
        
        if ($stmt = $pdo->prepare($sql)) {
            // تشفير كلمة المرور (Password Hashing) كما طلب الدكتور تماماً للأمان
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            
            if ($stmt->execute()) {
                $success_msg = "تم إنشاء الحساب بنجاح! جاري تحويلك لصفحة تسجيل الدخول...";
                header("refresh:2;url=login.php");
            } else {
                $username_err = "حدث خطأ ما، يرجى المحاولة لاحقاً.";
            }
            unset($stmt);
        }
    }
    unset($pdo);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء حساب جديد | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container" style="max-width: 500px;">
        <div class="card shadow border-0 p-4 bg-white">
            <h3 class="fw-bold text-center text-dark mb-2">إنشاء حساب زبون</h3>
            <p class="text-muted small text-center mb-4">انضم إلينا لتستمتع بأحدث صيحات الموضة والملابس</p>
            
            <?php if(!empty($success_msg)): ?> <div class="alert alert-success"><?= $success_msg; ?></div> <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?= sanitize($username); ?>">
                    <div class="invalid-feedback"><?= $username_err; ?></div>
                </div>    
                <div class="mb-3">
                    <label class="form-label fw-bold">كلمة المرور</label>
                    <input type="password" name="password" class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <div class="invalid-feedback"><?= $password_err; ?></div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password" class="form-control <?= (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <div class="invalid-feedback"><?= $confirm_password_err; ?></div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2">تسجيل الحساب</button>
                </div>
                <p class="text-center small mb-0">لديك حساب بالفعل؟ <a href="login.php" class="text-decoration-none text-primary">تسجيل الدخول من هنا</a></p>
            </form>
        </div>
    </div>
</body>
</html>