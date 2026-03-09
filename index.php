<?php
$successMessage = false;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_service'])) {
    
    // Extract standard fields
    $fullName = $_POST['fullName'] ?? '';
    $phoneNumber = $_POST['phoneNumber'] ?? '';
    $section = $_POST['section'] ?? '';
    $serviceIndex = $_POST['service_index'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    // Extract any extra fields from work category
    $extras = [];
    $extraKeys = ['extra_id', 'extra_cert', 'extra_count', 'extra_uni'];
    foreach ($extraKeys as $key) {
        if (isset($_POST[$key])) {
            $extras[$key] = $_POST[$key];
        }
    }
    $extraDetails = empty($extras) ? null : json_encode($extras, JSON_UNESCAPED_UNICODE);

    // Insert request
    $stmt = $pdo->prepare("INSERT INTO requests (full_name, phone_number, section_name, service_index, payment_method, extra_details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $phoneNumber, $section, $serviceIndex, $paymentMethod, $extraDetails]);
    
    $requestId = $pdo->lastInsertId();
    
    // Handle file uploads
    if (!empty($_FILES['documents']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileCount = count($_FILES['documents']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $tmpName = $_FILES['documents']['tmp_name'][$i];
            $fileName = basename($_FILES['documents']['name'][$i]);
            if ($tmpName != "") {
                $uniqueName = time() . '_' . rand(1000, 9999) . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
                $targetPath = $uploadDir . $uniqueName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $stmtFile = $pdo->prepare("INSERT INTO uploaded_documents (request_id, file_path) VALUES (?, ?)");
                    $stmtFile->execute([$requestId, 'uploads/' . $uniqueName]);
                }
            }
        }
    }

    $successMessage = $requestId;
}

$servicesData = [
    'personal' => [
        [
            'title' => 'إخراج / تجديد هوية ( بطاقة شخصية )',
            'req' => ['صورة عن طلب هوية من المختار','٣ صور شمسيات','صورة إخراج قيد فردي حديث','{ + صورة عن الهوية القديمة في حال التجديد }'],
            'cost' => '١.٠٠٠.٠٠٠ ل.ل ( مليون ليرة لبنانية فقط لا غير )',
            'duration' => 'المده: من ١٥ يومًا إلى شهر من تاريخ الإرسال. نرجو التوجه الى اقرب محل لاستلام طلبك بعد الفتره المحدده.',
            'extra' => ''
        ],
        [
            'title' => 'إخراج / تجديد جواز سفر',
            'req' => ['صورة عن الهوية أو إخراج قيد فردي','صورة شمسية جديدة','صورة عن جواز السفر القديم ( في حال التجديد )'],
            'cost' => 'التكلفة : ٦.٠٠٠.٠٠٠ ل.ل (٦ مليون ليرة) = ٥ سنوات /// ١٠.٠٠٠.٠٠٠ ل.ل (١٠ مليون) = ١٠ سنوات',
            'duration' => 'المدّة : ١٥ يومًا من تاريخ الإرسال',
            'extra' => ''
        ],
        [
            'title' => 'طلب إخراج قيد فردي / عائلي',
            'req' => ['صورة عن الطلب من المختار','صورة شمسية { للفردي }'],
            'cost' => '٤٠٠.٠٠٠ ل.ل ( ٤٠٠ ألف ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : - فوري ( إذا كان السجل ممكنًا ) - من ٣ ل ٥ أيام ( إذا كان السجل يدويًا )',
            'extra' => ''
        ],
        [
            'title' => 'تسجيل مولود',
            'req' => ['صورة عن وثيقة الزواج','صورة عن وثيقة الولادة من المشفى','صورة عن إخراج قيد العائلي'],
            'cost' => '٤٠٠.٠٠٠ ل.ل ( ٤٠٠ ألف ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : من ٥ ل ٧ أيام من تاريخ الإرسال',
            'extra' => ''
        ],
        [
            'title' => 'تثبيت زواج / طلاق',
            'req' => ['صورة عن وثيقة زواج ( من المحكمة الشرعية أو الروحية )','صورة عن إخراج قيد للزوج','صورة عن إخراج قيد للزوجة'],
            'cost' => '٤٠٠.٠٠٠ ل.ل ( ٤٠٠ الف ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : ٤ ل ٧ أيام من تاريخ الإرسال',
            'extra' => ''
        ]
    ],
    'justice' => [
        [
            'title' => 'استخراج سجل عدلي',
            'req' => ['صورة عن الهوية او صورة عن إخراج القيد الفردي'],
            'cost' => '١٠٠.٠٠٠ ل.ل ( ١٠٠ الف ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : في نفس اليوم من تاريخ التسليم',
            'extra' => ''
        ],
        [
            'title' => 'تصديق توثيق عقود',
            'req' => ['صورة عن العقد الأصلي','صورة عن هوية الموكل','صورة عن هوية الموكل إليه','صور عن الهوية للشواهد'],
            'cost' => '٢.٠٠٠.٠٠٠ ل.ل ( ٢ مليون ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : يومين من تاريخ التسليم',
            'extra' => ''
        ]
    ],
    'transport' => [
        [
            'title' => 'رخصة قيادة',
            'req' => ['صورة عن تقرير طبّي ( سليمٌ معاف )','صورة عن السجل العدلي','صورة عن إفادة إجتياز الإختبار بنجاح'],
            'cost' => '٣٠٠ دولار فقط لا غير',
            'duration' => 'المدّة : أسبوعين من تاريخ التسليم',
            'extra' => ''
        ],
        [
            'title' => 'تسجيل ملكية عقار',
            'req' => ['صورة عن الإفادة العقارية','صورة عن براءة ذمّة من البلدية'],
            'cost' => 'غير محددة (يرجى الاتصال)',
            'duration' => 'المدّة : من ٣ أيام إلى أسبوع من تاريخ التسليم',
            'extra' => ''
        ]
    ],
    'work' => [
        [
            'title' => 'تصديق شهادات مدرسية / جامعية ( من وزارة التربية )',
            'req' => ['صورتين شمسيات'],
            'extra' => '<label>~ رقم الطلب الرسمي مع تحديد السنة الدراسية: <input class="form-input" type="text" name="extra_id" placeholder="رقم الطلب" required></label><br><label>~ تحديد الشهادة: <select class="form-input" name="extra_cert"><option>مهني</option><option>ثانوي</option><option>اختصاصات</option></select></label><br><label>~ تحديد عدد الشهادات: <input class="form-input" type="number" min="1" value="1" name="extra_count"></label>',
            'cost' => '٢.٠٠٠.٠٠٠ ل.ل ( ٢ مليون ليرة فقط لا غير ) للشهادة الواحدة',
            'duration' => 'المدّة : ٣ ل ٥ أيام من تاريخ التسليم'
        ],
        [
            'title' => 'معادلة شهادة جامعية',
            'req' => ['صورة عن الشهادة الرسمية من الجامعة الحالية','صورة شمسية','صورة عن اخراج قيد فردي','صورة عن إفادة سكن'],
            'extra' => '<label>~ تحديد الجامعة: <input class="form-input" type="text" name="extra_uni" placeholder="اسم الجامعة" required></label>',
            'cost' => '٢.٠٠٠.٠٠٠ ل.ل ( ٢ مليون ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : من اسبوع إلى اسبوعين من تاريخ التسليم'
        ],
        [
            'title' => 'إخراج سجل تجاري',
            'req' => ['صورة عن إفادة ترخيص الشركة','صورة عن الهوية','صورة عن ملكية الشركة','صورة شمسية','سجل عدلي'],
            'cost' => '٥٠٠.٠٠٠ ل.ل ( ٥٠٠ الف ليرة لبنانية فقط لا غير )',
            'duration' => 'المدّة : من ٣ الى ٥ ايام من تاريخ التسليم',
            'extra' => ''
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وزارة الداخلية اللبنانية - الخدمات الإلكترونية</title>
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
            position: relative;
            overflow-x: hidden;
        }

        /* Semi-transparent Injaz logo as background watermark */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90vw;
            max-width: 550px;
            height: 90vw;
            max-height: 550px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="90" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="10"/><path d="M100 20 L100 180 M20 100 L180 100" stroke="rgba(255,255,255,0.05)" stroke-width="5"/></svg>');
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 650px;
            margin: auto;
        }

        .header {
            text-align: center;
            color: #d4af37;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            background: rgba(0, 0, 0, 0.4);
            padding: 15px;
            border-radius: 15px;
            border: 1px solid #d4af37;
        }

        .header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            padding: 0 10px;
        }

        .header p {
            font-size: 1.2rem;
            color: #e0e0e0;
        }

        .page {
            display: none;
            flex-direction: column;
            gap: 20px;
            animation: fadeIn 0.4s ease;
        }

        .active-page {
            display: flex;
            flex-direction: column;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Information Box (Form setup) */
        .info-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            text-align: center;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: right;
        }

        .input-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-size: 1.2rem;
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
            margin-bottom: 10px;
        }

        .form-input:focus {
            border-color: #d4af37;
            outline: none;
            background: rgba(0,0,0,0.7);
        }

        .next-btn {
            background: linear-gradient(90deg, #d4af37, #f1c40f);
            color: #000;
            border: none;
            padding: 15px 40px;
            font-size: 1.3rem;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            opacity: 0.5;
            pointer-events: none;
            width: 100%;
            margin-top: 10px;
        }

        .next-btn.show {
            opacity: 1;
            pointer-events: auto;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .next-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6);
        }

        /* Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #8f7423;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }

        .category-card:hover {
            background: rgba(212, 175, 55, 0.2);
            border-color: #f1c40f;
            transform: translateY(-5px);
        }

        .icon-large {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }

        .category-card h3 {
            font-size: 1.5rem;
            color: #ffdb9d;
        }

        /* Services */
        .section-page {
            display: none;
            flex-direction: column;
            gap: 20px;
            animation: fadeIn 0.4s ease;
        }

        .service-box {
            background: rgba(0,0,0,0.6);
            border: 2px solid rgba(255,215,0,0.5);
            border-radius: 15px;
            overflow: hidden;
            width: 100%;
            margin-bottom: 15px;
            transition: border-color 0.3s;
            text-align: right;
        }

        .service-box h2 {
            font-size: 1.3rem;
            color: #ffd966;
            cursor: pointer;
            padding: 15px 20px;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .arrow-icon {
            transition: transform 0.3s;
            font-size: 0.9rem;
            margin-right: 15px;
        }

        .box-body {
            display: none;
            padding: 20px;
            border-top: 1px solid rgba(255,215,0,0.2);
        }

        .checklist p {
            color: #add8e6;
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .checklist label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-size: 1.1rem;
            margin-bottom: 12px;
            cursor: pointer;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            transition: background 0.2s;
            text-align: right;
        }

        .checklist label:hover {
            background: rgba(255,255,255,0.1);
        }

        .checklist input[type="checkbox"] {
            width: 20px;
            height: 20px;
            min-width: 20px;
            accent-color: #4CAF50;
        }

        .upload-area {
            background: rgba(76, 175, 80, 0.1);
            border: 2px dashed #4CAF50;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
            margin-top: 20px;
            display: none;
        }

        .upload-area p {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .upload-area input[type="file"] {
            max-width: 100%;
            display: block;
            margin: auto;
        }

        .submit-req {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: background 0.3s;
        }

        .submit-req:disabled {
            background: #5a5a5a;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .submit-req:not(:disabled):hover {
            background: #2ecc71;
        }

        .payment-info {
            background: #103010e0;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 2px solid #ffb347;
            display: none;
        }

        .cost {
            font-size: 1.3rem;
            color: #ffdb9d;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .payment-methods label {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            color: white;
            background: rgba(0,0,0,0.3);
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #f1c40f;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background 0.2s;
        }

        .payment-methods label:hover {
            background: rgba(241, 196, 15, 0.2);
        }

        .payment-methods input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: #f1c40f;
        }

        .duration-message {
            font-size: 1.1rem;
            background: #2d374b;
            padding: 15px;
            border-radius: 10px;
            border-right: 6px solid gold;
            color: #ececec;
            margin-top: 15px;
        }

        .back-main {
            background: transparent;
            color: #d4af37;
            border: 2px solid #d4af37;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: all 0.3s;
        }

        .back-main:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        /* Popups */
        .success-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .success-box {
            background: linear-gradient(135deg, #1a2a6c, #112240, #2b5876);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            border: 2px solid #4CAF50;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            animation: scaleIn 0.3s ease;
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-circle {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 0 20px rgba(76, 175, 80, 0.4);
        }

        .success-msg {
            color: #e0e0e0;
            font-size: 1.3rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .success-close {
            background: #d4af37;
            color: black;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
        }

        /* Responsive specific fixes */
        @media (min-width: 768px) {
            .payment-methods {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            .payment-methods label {
                flex: 1;
                min-width: 180px;
            }
        }
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.6rem;
            }
            .header p {
                font-size: 1rem;
            }
            .info-box, .service-box h2, .box-body {
                padding: 15px;
            }
            .next-btn {
                padding: 12px 20px;
            }
            .cost {
                font-size: 1.1rem;
            }
            .payment-methods label {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<?php if ($successMessage): ?>
<div class="success-overlay" id="successPopup">
    <div class="success-box">
        <div class="success-circle">✔</div>
        <div class="success-msg">نتقدّم إليكم بخالص الشكر والتقدير على تواصلكم معنا،<br>وسيتم التواصل معكم في أقرب وقت ممكن.<br><br>
        <strong>رقم الطلب الخاص بك هو: <span style="color: #f1c40f; font-size: 1.5rem; display: inline-block; direction: ltr;">#<?php echo htmlspecialchars($successMessage); ?></span></strong><br>
        <strong style="color:#ffd966;">الرجاء حفظ رقم الطلب أو رقم هاتفك للتمكن من تتبع حالة الطلب لاحقاً أو مراجعتنا به.</strong>
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="success-close" style="background:#555; color:white;" onclick="document.getElementById('successPopup').remove()">إغلاق</button>
            <a href="status.php" class="success-close" style="text-decoration:none; display:inline-block; line-height: 25px;">تتبع الطلب الآن</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <div class="header">
        <h1>جمهورية لبنان - وزارة الداخلية والبلديات</h1>
        <p>البوابة الإلكترونية للخدمات الرسمية لإنجاز</p>
        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
            <a href="status.php" style="display:inline-block; margin-top:15px; padding: 8px 20px; background: rgba(255,255,255,0.1); border: 1px solid #d4af37; color: white; text-decoration: none; border-radius: 20px; font-weight: bold; transition: background 0.3s; font-size: 0.9rem;">
                🔍 تتبع حالة طلبك
            </a>
            <a href="login.php" style="display:inline-block; margin-top:15px; padding: 8px 20px; background: rgba(255,255,255,0.1); border: 1px solid #d4af37; color: white; text-decoration: none; border-radius: 20px; font-weight: bold; transition: background 0.3s; font-size: 0.9rem;">
                👤 تسجيل دخول الموظفين
            </a>
        </div>
    </div>

    <!-- User Info Form (Page 1) -->
    <div id="page1" class="page active-page">
        <div class="info-box">
            <h2 style="color: white; margin-bottom: 25px; text-align: center;">مرحباً بك، يرجى إدخال بياناتك للمتابعة</h2>
            <div class="input-group">
                <label for="fullName">الاسم الثلاثي:</label>
                <input type="text" id="fullName" class="form-input" placeholder="مثال: أحمد محمد علي" required>
            </div>
            <div class="input-group">
                <label for="phoneNumber">رقم الهاتف النشط (للتواصل):</label>
                <input type="tel" id="phoneNumber" class="form-input" placeholder="81 123 456" required dir="ltr" style="text-align: right;">
            </div>
            <button class="next-btn" id="nextToMainBtn">استمرار ⟵</button>
        </div>
    </div>

    <!-- Main Categories -->
    <div id="mainCategories" class="page">
        <h2 style="color: white; text-align: center; margin-bottom: 10px; font-size: 1.8rem; text-shadow: 1px 1px 3px black;">اختر القسم المطلوب</h2>
        <div class="categories-grid">
            <div class="category-card" data-cat="personal">
                <div class="icon-large">👤</div>
                <h3>دائرة الأحوال الشخصية</h3>
                <p style="margin-top: 10px; color:#ccc;">هويات، جوازات سفر، اخراجات قيد، وتسجيلات</p>
            </div>
            <div class="category-card" data-cat="justice">
                <div class="icon-large">⚖️</div>
                <h3>وزارة العدل</h3>
                <p style="margin-top: 10px; color:#ccc;">سجل عدلي، وتوثيق عقود</p>
            </div>
            <div class="category-card" data-cat="transport">
                <div class="icon-large">🚗</div>
                <h3>هيئة إدارة السير والآليات</h3>
                <p style="margin-top: 10px; color:#ccc;">رخص قيادة، وتسجيل ملكيات</p>
            </div>
            <div class="category-card" data-cat="work">
                <div class="icon-large">💼</div>
                <h3>وزارة التربية والاقتصاد</h3>
                <p style="margin-top: 10px; color:#ccc;">معادلات وتصديق شهادات وسجلات تجارية</p>
            </div>
        </div>
        <button class="back-main" style="border:none; background: #c0392b; color: white;" onclick="window.location.reload();">⟲ العودة وتغيير الاسم</button>
    </div>

    <!-- Generate Section Pages via PHP -->
    <?php foreach ($servicesData as $categoryKey => $services): ?>
    <div id="<?php echo $categoryKey; ?>Page" class="section-page">
        <h2 style="color: #ffd966; text-align: center; margin-bottom: 20px; font-size: 1.8rem; text-shadow: 1px 1px 3px black;">
            <?php 
                $titles = [
                    'personal' => 'دائرة الأحوال الشخصية',
                    'justice' => 'وزارة العدل',
                    'transport' => 'هيئة إدارة السير والآليات',
                    'work' => 'وزارة التربية والاقتصاد'
                ];
                echo $titles[$categoryKey];
            ?>
        </h2>
        
        <?php foreach ($services as $idx => $service): ?>
        <div class="service-box">
            <h2 onclick="toggleServiceBody(this)">
                <?php echo htmlspecialchars($service['title']); ?>
                <span class="arrow-icon">▼</span>
            </h2>
            <div class="box-body">
                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="section" value="<?php echo $categoryKey; ?>">
                    <input type="hidden" name="service_index" value="<?php echo $idx; ?>">
                    
                    <div class="checklist">
                        <p>📋 المتطلبات :</p>
                        <?php foreach ($service['req'] as $reqIdx => $req): ?>
                        <label>
                            <input type="checkbox" class="req-check" onchange="checkRequirements(this)"> 
                            <?php echo htmlspecialchars($req); ?>
                        </label>
                        <?php endforeach; ?>
                        
                        <?php if(!empty($service['extra'])) echo $service['extra']; ?>
                    </div>
                    
                    <div class="upload-area">
                        <p>🖼️ يرجى إرفاق المستندات المطلوبة (صيغة jpg, png)</p>
                        <input type="file" name="documents[]" accept="image/*" multiple required onchange="checkFiles(this)">
                    </div>
                    
                    <div class="payment-info">
                        <div class="cost">💰 التكلفة: <?php echo htmlspecialchars($service['cost']); ?></div>
                        <p style="color: white; margin-bottom: 10px; font-weight: bold; text-align: right;">اختر طريقة الدفع للإستمرار:</p>
                        <div class="payment-methods">
                            <label><input type="radio" name="payment_method" value="OMT" required> 💸 OMT</label>
                            <label><input type="radio" name="payment_method" value="Wish" required> 💳 Wish money</label>
                            <label><input type="radio" name="payment_method" value="LibanPost" required> 📦 LibanPost</label>
                        </div>
                        <div class="duration-message">
                            ⏳ <?php echo htmlspecialchars($service['duration']); ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_service" class="submit-req" disabled>تأكيد وتقديم الطلب</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        
        <button class="back-main" onclick="showCategories()">→ العودة إلى الأقسام الرئيسية</button>
    </div>
    <?php endforeach; ?>

</div>

<script>
    const nameInput = document.getElementById('fullName');
    const phoneInput = document.getElementById('phoneNumber');
    const nextBtn = document.getElementById('nextToMainBtn');

    nameInput.addEventListener('input', checkUserInputs);
    phoneInput.addEventListener('input', checkUserInputs);

    function checkUserInputs() {
        const nameOk = nameInput.value.trim().length > 2;
        const phoneOk = phoneInput.value.trim().length > 4;
        if (nameOk && phoneOk) {
            nextBtn.classList.add('show');
        } else {
            nextBtn.classList.remove('show');
        }
    }

    nextBtn.addEventListener('click', function() {
        document.getElementById('page1').classList.remove('active-page');
        document.getElementById('mainCategories').classList.add('active-page');
    });

    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', function() {
            const cat = this.dataset.cat;
            document.querySelectorAll('.page, .section-page').forEach(el => el.classList.remove('active-page'));
            
            const targetPage = document.getElementById(cat + 'Page');
            if (targetPage) {
                targetPage.style.display = 'flex';
                // Trigger reflow to restart animation/display properly
                void targetPage.offsetWidth; 
                targetPage.classList.add('active-page');
            }
        });
    });

    function showCategories() {
        document.querySelectorAll('.page, .section-page').forEach(el => {
            el.style.display = 'none';
            el.classList.remove('active-page')
        });
        
        const mainCats = document.getElementById('mainCategories');
        mainCats.style.display = 'flex';
        void mainCats.offsetWidth;
        mainCats.classList.add('active-page');
    }

    function toggleServiceBody(headerEl) {
        const box = headerEl.closest('.service-box');
        const body = box.querySelector('.box-body');
        const arrow = box.querySelector('.arrow-icon');
        
        const isOpen = body.style.display === 'block';
        
        // Close all other bodies
        document.querySelectorAll('.box-body').forEach(b => b.style.display = 'none');
        document.querySelectorAll('.arrow-icon').forEach(a => a.style.transform = 'rotate(0deg)');
        document.querySelectorAll('.service-box').forEach(sb => sb.style.borderColor = 'rgba(255,215,0,0.5)');

        if (!isOpen) {
            body.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
            box.style.borderColor = '#f1c40f';
        }
    }

    function checkRequirements(checkbox) {
        const form = checkbox.closest('form');
        const checkboxes = form.querySelectorAll('.req-check');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const uploadArea = form.querySelector('.upload-area');
        const paymentInfo = form.querySelector('.payment-info');
        const submitBtn = form.querySelector('.submit-req');
        const fileInput = form.querySelector('input[type="file"]');

        if (allChecked) {
            uploadArea.style.display = 'block';
        } else {
            uploadArea.style.display = 'none';
            paymentInfo.style.display = 'none';
            submitBtn.disabled = true;
            fileInput.value = ''; // Reset files
            
            // clear payment
            form.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
        }
    }

    function checkFiles(fileInput) {
        const form = fileInput.closest('form');
        const paymentInfo = form.querySelector('.payment-info');
        
        if (fileInput.files.length > 0) {
            paymentInfo.style.display = 'block';
            paymentInfo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            paymentInfo.style.display = 'none';
            form.querySelector('.submit-req').disabled = true;
            form.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
        }
    }

    // Enable submit only when payment is selected
    document.querySelectorAll('input[type="radio"][name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const form = this.closest('form');
            form.querySelector('.submit-req').disabled = false;
        });
    });

    // Append fullName and phoneNumber to any submitted form
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const hName = document.createElement('input');
            hName.type = 'hidden';
            hName.name = 'fullName';
            hName.value = document.getElementById('fullName').value;
            this.appendChild(hName);
            
            const hPhone = document.createElement('input');
            hPhone.type = 'hidden';
            hPhone.name = 'phoneNumber';
            hPhone.value = document.getElementById('phoneNumber').value;
            this.appendChild(hPhone);
        });
    });

</script>
</body>
</html>
