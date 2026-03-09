<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'الرجاء إدخال اسم المستخدم وكلمة المرور.';
    } else {
        try {
            $pdo = new PDO("mysql:host=db;dbname=injaz;charset=utf8mb4", "root", "root", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $stmt = $pdo->prepare("SELECT id, password_hash FROM employees WHERE username = ?");
            $stmt->execute([$username]);
            $employee = $stmt->fetch();

            if ($employee && password_verify($password, $employee['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit;
            } else {
                $error = 'بيانات الدخول غير صحيحة.';
            }
        } catch (PDOException $e) {
            $error = 'خطأ في الاتصال بقاعدة البيانات.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الموظفين - إنجاز</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #003366 0%, #1e4d3a 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .login-header {
            color: #d4af37;
            margin-bottom: 25px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .login-header h2 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: right;
        }

        .input-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #555;
            border-radius: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .form-input:focus {
            border-color: #d4af37;
            outline: none;
            background: rgba(0,0,0,0.7);
        }

        .login-btn {
            background: linear-gradient(90deg, #d4af37, #f1c40f);
            color: #000;
            border: none;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6);
        }

        .error-msg {
            color: #ff6b6b;
            background: rgba(255, 0, 0, 0.1);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ff6b6b;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h2>بوابة الموظفين</h2>
        <p>تسجيل الدخول لإدارة الطلبات</p>
    </div>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="input-group">
            <label for="username">اسم المستخدم</label>
            <input type="text" id="username" name="username" class="form-input" required dir="ltr">
        </div>
        <div class="input-group">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" class="form-input" required dir="ltr">
        </div>
        <button type="submit" class="login-btn">دخول</button>
    </form>
    
    <a href="index.php" class="back-link">العودة للصفحة الرئيسية ←</a>
</div>

</body>
</html>
