<?php
session_start();

require_once 'config.php';

// إذا كان المستخدم مسجل دخوله بالفعل، يتم توجيهه للرئيسية
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $username_err = "الرجاء إدخال اسم المستخدم.";
    } else {
        $username = sanitize($_POST["username"]);
    }
    
    if (empty(trim($_POST["password"]))) {
        $password_err = "الرجاء إدخال كلمة المرور.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // التحقق من البيانات والمطابقة الأمنية
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $username);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetch();
                    $id = $row["id"];
                    $hashed_password = $row["password"];
                    $role = $row["role"];
                    
                    // التحقق من كلمة المرور المشفّرة (Password Verification)
                    if (password_verify($password, $hashed_password)) {
                        
                        // تخزين البيانات في الجلسة بنجاح بعد أن قمنا بتفعيلها بـ session_start()
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["role"] = $role;
                        
                        // التوجيه بناءً على الصلاحيات وأدوار المستخدمين
                        if ($role === 'admin') {
                            header("location: admin_dashboard.php");
                        } else {
                            header("location: index.php");
                        }
                        exit;
                    } else {
                        $login_err = "اسم المستخدم أو كلمة المرور غير صحيحة.";
                    }
                } else {
                    $login_err = "اسم المستخدم أو كلمة المرور غير صحيحة.";
                }
            } else {
                $login_err = "حدث خطأ ما، يرجى المحاولة لاحقاً.";
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
    <title>تسجيل الدخول | خيوط الأناقة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container" style="max-width: 450px;">
        <div class="card shadow border-0 p-4 bg-white">
            <h3 class="fw-bold text-center text-dark mb-2">تسجيل الدخول</h3>
            <p class="text-muted small text-center mb-4">أهلاً بك مجدداً في متجر خيوط الأناقة</p>

            <?php if(!empty($login_err)): ?> <div class="alert alert-danger text-center small"><?= $login_err; ?></div> <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?= sanitize($username); ?>">
                    <div class="invalid-feedback"><?= $username_err; ?></div>
                </div>    
                <div class="mb-4">
                    <label class="form-label fw-bold">كلمة المرور</label>
                    <input type="password" name="password" class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <div class="invalid-feedback"><?= $password_err; ?></div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2">دخول النظام</button>
                </div>
                <p class="text-center small mb-0">ليس لديك حساب؟ <a href="register.php" class="text-decoration-none text-warning fw-bold">إنشاء حساب جديد</a></p>
            </form>
        </div>
    </div>
</body>
</html>