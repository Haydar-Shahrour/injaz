<?php
// Database connection
try {
    $pdo = new PDO("mysql:host=db;dbname=injaz;charset=utf8mb4", "root", "root", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

$requests = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search_term'])) {
    $term = trim($_POST['search_term']);
    
    // Remove the # if the user entered it
    $term = ltrim($term, '#');
    
    if (is_numeric($term)) {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ? OR phone_number LIKE ? ORDER BY created_at DESC");
        $stmt->execute([$term, '%' . $term . '%']);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE phone_number LIKE ? ORDER BY created_at DESC");
        $stmt->execute(['%' . $term . '%']);
    }
    
    $requests = $stmt->fetchAll();
}

$categories = [
    'personal' => 'دائرة الأحوال الشخصية',
    'justice' => 'وزارة العدل',
    'transport' => 'هيئة إدارة السير والآليات',
    'work' => 'وزارة التربية والاقتصاد'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع حالة الطلب - إنجاز</title>
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
            padding: 20px;
            color: white;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: rgba(0, 0, 0, 0.5);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            text-align: center;
        }

        h1 {
            color: #d4af37;
            margin-bottom: 20px;
        }

        .search-box {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 15px;
            font-size: 1.2rem;
            border-radius: 10px;
            border: 2px solid #555;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #d4af37;
        }

        .search-btn {
            background: linear-gradient(90deg, #d4af37, #f1c40f);
            color: black;
            font-weight: bold;
            font-size: 1.2rem;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            cursor: pointer;
            width: 100%;
        }

        .search-btn:hover {
            background: linear-gradient(90deg, #f1c40f, #d4af37);
        }
        
        .back-btn {
            background: transparent;
            color: #d4af37;
            border: 2px solid #d4af37;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background: rgba(212, 175, 55, 0.2);
        }

        .result-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: right;
            border-right: 5px solid #d4af37;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-pending { background: #ffc107; color: #000; }
        .status-accomplished { background: #28a745; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>تتبع حالة طلبك</h1>
    <p style="margin-bottom: 25px; color: #ddd;">أدخل رقم الطلب أو رقم هاتفك الذي استخدمته في التقديم لمعرفة حالة الطلب</p>
    
    <div class="search-box">
        <form method="POST">
            <input type="text" name="search_term" class="search-input" value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>" placeholder="رقم الطلب أو رقم الهاتف" required dir="ltr">
            <button type="submit" class="search-btn">بحث 🔍</button>
        </form>
    </div>

    <?php if($requests !== null): ?>
        <?php if(count($requests) > 0): ?>
            <h3 style="margin-top: 30px; text-align: right; color: #ffd966;">نتائج البحث (<?php echo count($requests); ?> طلب):</h3>
            <?php foreach($requests as $req): ?>
                <div class="result-card">
                    <p><strong>رقم الطلب:</strong> #<?php echo $req['id']; ?></p>
                    <p><strong>الخدمة:</strong> <?php echo isset($categories[$req['section_name']]) ? $categories[$req['section_name']] : $req['section_name']; ?></p>
                    <p><strong>تاريخ التقديم:</strong> <span dir="ltr"><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></span></p>
                    <p><strong>الحالة:</strong> 
                        <?php if($req['status'] === 'pending' || empty($req['status'])): ?>
                            <span class="status-badge status-pending">قيد المعالجة ⏳</span>
                        <?php elseif($req['status'] === 'accomplished'): ?>
                            <span class="status-badge status-accomplished">تم الإنجاز بنجاح ✔️</span>
                        <?php elseif($req['status'] === 'rejected'): ?>
                            <span class="status-badge" style="background: #dc3545; color: white;">مرفوض ❌</span>
                        <?php else: ?>
                            <span class="status-badge"><?php echo htmlspecialchars($req['status']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: rgba(255,0,0,0.2); padding: 15px; border-radius: 10px; margin-top: 20px;">
                لم يتم العثور على أي طلبات مرتبطة بهذا الرقم.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a href="index.php" class="back-btn">→ العودة للرئيسية</a>
</div>

</body>
</html>
