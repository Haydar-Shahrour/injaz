<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $action = $_POST['action'];
    $reqId = $_POST['request_id'];
    
    if ($action === 'mark_accomplished') {
        $stmt = $pdo->prepare("UPDATE requests SET status = 'accomplished' WHERE id = ?");
        $stmt->execute([$reqId]);
        
        $phoneStmt = $pdo->prepare("SELECT phone_number FROM requests WHERE id = ?");
        $phoneStmt->execute([$reqId]);
        $phone = $phoneStmt->fetchColumn();
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $waMsg = urlencode("مرحباً، نعلمكم بأنه تم إنجاز طلبكم رقم $reqId بنجاح عبر منصة إنجاز.");
        $_SESSION['wa_url'] = "https://wa.me/$cleanPhone?text=$waMsg";
        
        $_SESSION['msg'] = "تم إنجاز الطلب رقم $reqId بنجاح. سيتم فتح نافذة الواتساب للتواصل مع المواطن.";
        header('Location: admin.php');
        exit;
    } elseif ($action === 'mark_rejected') {
        $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$reqId]);
        
        $phoneStmt = $pdo->prepare("SELECT phone_number FROM requests WHERE id = ?");
        $phoneStmt->execute([$reqId]);
        $phone = $phoneStmt->fetchColumn();
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $waMsg = urlencode("مرحباً، نعتذر لإبلاغكم بأنه تم رفض طلبكم رقم $reqId عبر منصة إنجاز.");
        $_SESSION['wa_url'] = "https://wa.me/$cleanPhone?text=$waMsg";
        
        $_SESSION['msg'] = "تم رفض الطلب. سيتم فتح نافذة الواتساب للتواصل مع المواطن عبر الرقم: $phone 📱";
        header('Location: admin.php');
        exit;
    }
}

// Fetch all requests
$stmt = $pdo->query("SELECT * FROM requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll();

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
    <title>لوحة الموظفين - إنجاز</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f4f6f9;
            padding: 30px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            background: #003366;
            color: #d4af37;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #1e4d3a;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .status-pending {
            background: #ffeaa7;
            color: #b8860b;
        }

        .status-accomplished {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-action {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }

        .btn-action:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .files-list {
            list-style: none;
        }
        
        .files-list li {
            margin-bottom: 5px;
        }

        .files-list a {
            color: #0056b3;
            text-decoration: none;
        }
        
        .files-list a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="width: 100px;"></div> <!-- Spacer -->
            <h1>لوحة تحكم الموظفين - معالجة الطلبات</h1>
            <div>
                <a href="admin.php?logout=true" style="background: #e74c3c; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 0.9rem;">تسجيل خروج</a>
            </div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['msg'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border: 1px solid #c3e6cb;">
            <?php 
                echo htmlspecialchars($_SESSION['msg']); 
                unset($_SESSION['msg']);
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['wa_url'])): ?>
        <script>
            // Open WhatsApp link in a new tab automatically
            window.open('<?php echo addslashes($_SESSION['wa_url']); ?>', '_blank');
        </script>
        <?php unset($_SESSION['wa_url']); ?>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>الاسم</th>
                <th>رقم الهاتف</th>
                <th>القسم والخدمة</th>
                <th>المستندات</th>
                <th>تاريخ الطلب</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($requests as $req): ?>
            <tr>
                <td><strong>#<?php echo $req['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                <td dir="ltr" style="text-align: right;"><?php echo htmlspecialchars($req['phone_number']); ?></td>
                <td>
                    <?php 
                        echo isset($categories[$req['section_name']]) ? $categories[$req['section_name']] : $req['section_name']; 
                        echo "<br><small style='color:#666;'>Service Index: " . $req['service_index'] . "</small>";
                    ?>
                </td>
                <td>
                    <?php
                        // Fetch docs for this request
                        $docStmt = $pdo->prepare("SELECT file_path FROM uploaded_documents WHERE request_id = ?");
                        $docStmt->execute([$req['id']]);
                        $docs = $docStmt->fetchAll();
                        if(count($docs) > 0) {
                            echo '<ul class="files-list">';
                            foreach($docs as $doc) {
                                echo '<li><a href="'.htmlspecialchars($doc['file_path']).'" target="_blank">📄 عرض المستند</a></li>';
                            }
                            echo '</ul>';
                        } else {
                            echo 'لا يوجد مستندات';
                        }
                    ?>
                </td>
                <td dir="ltr" style="text-align: right;"><?php echo date('Y-m-d H:i', strtotime($req['created_at'])); ?></td>
                <td>
                    <?php if($req['status'] === 'pending' || empty($req['status'])): ?>
                        <span class="status-badge status-pending">قيد المعالجة</span>
                    <?php elseif($req['status'] === 'accomplished'): ?>
                        <span class="status-badge status-accomplished">منجز</span>
                    <?php elseif($req['status'] === 'rejected'): ?>
                        <span class="status-badge status-rejected">مرفوض</span>
                    <?php else: ?>
                        <span class="status-badge"><?php echo htmlspecialchars($req['status']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($req['status'] === 'pending' || empty($req['status'])): ?>
                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="mark_accomplished">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" class="btn-action">✔ إنجاز</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="mark_rejected">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" class="btn-reject">✖ رفض</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($requests)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 30px;">لا يوجد طلبات حالياً.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
