<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
if (empty($user['name'])) { $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']); if ($r && $row = $r->fetch_assoc()) { $user['name'] = $row['name']; $user['profile_picture'] = $row['profile_picture']; } }
$showDiagnosis = false;
$diagnosisData = null;
$request_doctor_review = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_name = sanitize($_POST['child_name'] ?? '');
    $age        = (int)($_POST['age'] ?? 0);
    $gender     = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : 'male';
    $weight     = (float)($_POST['weight'] ?? 0);
    $birth_date = $_POST['birth_date'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM children WHERE parent_id=? LIMIT 1");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $child_id = $existing['id'];
        $u = $conn->prepare("UPDATE children SET name=?,age=?,gender=?,weight=?,birth_date=? WHERE id=?");
        $u->bind_param("sissdi", $child_name, $age, $gender, $weight, $birth_date, $child_id);
        $u->execute();
    } else {
        $i = $conn->prepare("INSERT INTO children (parent_id,name,age,gender,weight,birth_date) VALUES (?,?,?,?,?,?)");
        $i->bind_param("iissds", $user['id'], $child_name, $age, $gender, $weight, $birth_date);
        $i->execute();
        $child_id = $conn->insert_id;
    }

    $symptoms    = implode(',', array_map('sanitize', $_POST['symptoms'] ?? []));
    $description = sanitize($_POST['description'] ?? '');
    $frequency   = sanitize($_POST['frequency'] ?? '');
    $start_date  = $_POST['start_date'] ?? '';
    $request_doctor_review = isset($_POST['request_doctor_review']) && $_POST['request_doctor_review'] === '1';

    $s = $conn->prepare("INSERT INTO assessments (child_id,parent_id,symptoms,description,frequency,start_date) VALUES (?,?,?,?,?,?)");
    $s->bind_param("iissss", $child_id, $user['id'], $symptoms, $description, $frequency, $start_date);
    $s->execute();
    $assessment_id = $s->insert_id;

    $autoRisk = calculateEpilepsyRisk($symptoms);
    
    // إنشاء تقرير مبدئي فوري
    $auto_percentage = $autoRisk['percentage'];
    $auto_result = $autoRisk['result'];
    
    $auto_recommendations = [];
    if ($auto_result === 'positive') {
        $auto_recommendations = [
            'يُنصح بمراجعة طبيب أعصاب متخصص في أقرب وقت',
            'تجنب المحفزات مثل الأضواء الساطعة والإجهاد',
            'الحفاظ على نمط نوم منتظم',
            'تسجيل أي نوبات تحدث مع تفاصيلها'
        ];
    } elseif ($auto_result === 'suspected') {
        $auto_recommendations = [
            'يُنصح بإجراء فحوصات إضافية للتأكد',
            'مراقبة الأعراض وتسجيلها بدقة',
            'استشارة طبيب أعصاب للتقييم الشامل',
            'تجنب القيادة أو الأنشطة الخطرة حتى التأكد'
        ];
    } else {
        $auto_recommendations = [
            'الأعراض لا تشير بوضوح إلى الصرع',
            'المتابعة الدورية مع الطبيب',
            'الحفاظ على نمط حياة صحي',
            'مراجعة الطبيب عند ظهور أعراض جديدة'
        ];
    }
    
    $auto_symptoms_observed = $symptoms;
    $auto_notes = "هذا تقييم أولي تلقائي بناءً على الأعراض المُدخلة. للحصول على تشخيص دقيق، يُرجى طلب مراجعة من طبيب متخصص.";
    
    // حفظ التقرير المبدئي
    $system_doctor_id = 0; // 0 يعني تقييم تلقائي
    $recommendations_text = implode("\n", $auto_recommendations);
    $report_status = 'read'; // مقروء مباشرة
    $is_auto = 1; // تقرير تلقائي
    
    $medications_empty = '';
    $r = $conn->prepare("INSERT INTO reports (assessment_id, doctor_id, patient_id, diagnosis_result, epilepsy_percentage, symptoms_observed, recommendations, notes, medications, status, is_auto) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $r->bind_param("iiisisssssi", $assessment_id, $system_doctor_id, $user['id'], $auto_result, $auto_percentage, $auto_symptoms_observed, $recommendations_text, $auto_notes, $medications_empty, $report_status, $is_auto);
    $r->execute();

    notifyUser($conn, (int)$user['id'], 'report', 'نتيجة الفحص جاهزة', 'تم إنشاء تقييم أولي لحالة ' . $child_name . '. اضغط لعرض التفاصيل.', 'diagnosis.php');

    if ($request_doctor_review) {
        notifyDoctors($conn, 'assessment', 'فحص جديد يحتاج مراجعة', "تم إرسال فحص جديد من {$user['name']}. اضغط للمراجعة.", '../doctor/reports.php');
    }

    if ($auto_result === 'positive' && $auto_percentage >= 66) {
        notifyAdmins($conn, 'assessment', 'حالة قد تحتاج متابعة', "فحص إيجابي ($auto_percentage%) من {$user['name']}", '../admin/reports.php', 'critical');
        notifyDoctors($conn, 'assessment', 'حالة تحتاج مراجعة', "نتيجة إيجابية من {$user['name']} - $auto_percentage%", '../doctor/reports.php');
    }

    // إعداد بيانات التشخيص للعرض
    $diagnosisData = [
        'result' => $auto_result,
        'percentage' => $auto_percentage,
        'recommendations' => $auto_recommendations,
        'symptoms' => explode(',', $symptoms),
        'child_name' => $child_name,
        'notes' => $auto_notes
    ];
    
    $showDiagnosis = true;
    $success = true;
}

$child = null;
$stmt2 = $conn->prepare("SELECT * FROM children WHERE parent_id=? LIMIT 1");
$stmt2->bind_param("i", $user['id']);
$stmt2->execute();
$child = $stmt2->get_result()->fetch_assoc();

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>فحص الأعراض - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.5s ease forwards; }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    .step-active { background: linear-gradient(135deg, #8F00FF, #5500CC); color: white; }
    .step-done { background: #10b981; color: white; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
  </style>
</head>
<body class="bg-gray-50">

  
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20">
      <div class="flex items-center gap-3">
        <img src="../assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
        <span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>الرئيسية</span>
      </a>
      <a href="assessment.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
        <i data-lucide="clipboard-list" class="w-5 h-5"></i><span>فحص الأعراض</span>
      </a>
      <a href="diagnosis.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="activity" class="w-5 h-5"></i><span>نتائج التشخيص</span>
      </a>
      <a href="messages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="message-circle" class="w-5 h-5"></i><span>رسائل الطبيب</span>
      </a>
      <a href="education.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span>
      </a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span>
      </a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span>
      </a>
    </nav>
    <div class="p-4">
      <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all">
        <i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span>
      </a>
    </div>
  </aside>

  
  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">فحص الأعراض</h1>
        <p class="text-sm text-gray-500">أدخل أعراض طفلك للحصول على تقييم أولي</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8 max-w-3xl mx-auto">

      
      <div class="flex items-center justify-center gap-4 mb-10">
        <div class="flex items-center gap-2">
          <div id="step1-indicator" class="w-10 h-10 rounded-full step-active flex items-center justify-center font-bold text-sm">1</div>
          <span class="text-sm font-semibold text-gray-700">بيانات الطفل</span>
        </div>
        <div class="flex-1 h-1 bg-gray-200 rounded max-w-16"></div>
        <div class="flex items-center gap-2">
          <div id="step2-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm">2</div>
          <span class="text-sm font-semibold text-gray-500">الأعراض الملاحظة</span>
        </div>
        <div class="flex-1 h-1 bg-gray-200 rounded max-w-16"></div>
        <div class="flex items-center gap-2">
          <div id="step3-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm">3</div>
          <span class="text-sm font-semibold text-gray-500">مراجعة وإرسال</span>
        </div>
      </div>

      <form id="assessmentForm" method="POST" action="assessment.php">

        
        <div id="step1" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 animate-fadeInUp">
          <h2 class="text-xl font-bold text-gray-800 mb-6">بيانات الطفل</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">اسم الطفل</label>
              <input type="text" name="child_name" id="f_child_name"
                     value="<?= htmlspecialchars($child['name'] ?? '') ?>"
                     placeholder="أدخل اسم الطفل"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800" required>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">العمر (بالسنوات)</label>
              <input type="number" name="age" id="f_age" min="0" max="18"
                     value="<?= htmlspecialchars($child['age'] ?? '') ?>"
                     placeholder="العمر"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800" required>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الجنس</label>
              <select name="gender" id="f_gender" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
                <option value="male" <?= (($child['gender'] ?? '') === 'male') ? 'selected' : '' ?>>ذكر</option>
                <option value="female" <?= (($child['gender'] ?? '') === 'female') ? 'selected' : '' ?>>أنثى</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الوزن (كجم)</label>
              <input type="number" name="weight" id="f_weight" step="0.1" min="0"
                     value="<?= htmlspecialchars($child['weight'] ?? '') ?>"
                     placeholder="الوزن"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد</label>
              <input type="date" name="birth_date" id="f_birth_date"
                     value="<?= htmlspecialchars($child['birth_date'] ?? '') ?>"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
          </div>
          <div class="flex justify-start mt-8">
            <button type="button" onclick="goToStep(2)" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              التالي
              <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        
        <div id="step2" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-8 animate-fadeInUp">
          <h2 class="text-xl font-bold text-gray-800 mb-2">الأعراض الملاحظة</h2>
          <p class="text-gray-500 text-sm mb-6">اختر الأعراض التي لاحظتها على طفلك</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <?php
            $symptomsList = [
              'تشنجات', 'فقدان الوعي', 'شرود الذهن', 'حركات لا إرادية',
              'مشاكل في النوم', 'صعوبة في الكلام', 'صعوبة في التركيز', 'نوبات مفاجئة'
            ];
            foreach ($symptomsList as $sym): ?>
            <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50 transition has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50">
              <input type="checkbox" name="symptoms[]" value="<?= htmlspecialchars($sym) ?>" class="w-5 h-5 accent-purple-600">
              <span class="font-medium text-gray-800"><?= htmlspecialchars($sym) ?></span>
            </label>
            <?php endforeach; ?>
          </div>

          <div class="mb-5">
            <label class="block text-sm font-semibold text-gray-700 mb-2">وصف تفصيلي للأعراض</label>
            <textarea name="description" id="f_description" rows="3"
                      placeholder="اكتب وصفاً تفصيلياً للأعراض التي لاحظتها..."
                      class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 resize-none"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تكرار الأعراض</label>
              <select name="frequency" id="f_frequency" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
                <option value="">اختر التكرار</option>
                <option value="daily">يومياً</option>
                <option value="weekly">أسبوعياً</option>
                <option value="monthly">شهرياً</option>
                <option value="rarely">نادراً</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ بدء الأعراض</label>
              <input type="date" name="start_date" id="f_start_date"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
          </div>

          <div class="flex justify-between mt-8">
            <button type="button" onclick="goToStep(1)" class="border border-gray-300 text-gray-700 px-8 py-3 rounded-xl font-semibold hover:bg-gray-50 transition flex items-center gap-2">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              السابق
            </button>
            <button type="button" onclick="goToStep(3)" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              التالي
              <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        
        <div id="step3" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-8 animate-fadeInUp">
          <h2 class="text-xl font-bold text-gray-800 mb-6">مراجعة وإرسال</h2>

          <div class="bg-purple-50 rounded-xl p-6 mb-5">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i data-lucide="user" class="w-4 h-4 text-purple-600"></i>
              بيانات الطفل
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">الاسم:</span> <span id="r_name" class="font-semibold text-gray-800"></span></div>
              <div><span class="text-gray-500">العمر:</span> <span id="r_age" class="font-semibold text-gray-800"></span></div>
              <div><span class="text-gray-500">الجنس:</span> <span id="r_gender" class="font-semibold text-gray-800"></span></div>
              <div><span class="text-gray-500">الوزن:</span> <span id="r_weight" class="font-semibold text-gray-800"></span></div>
              <div><span class="text-gray-500">تاريخ الميلاد:</span> <span id="r_birth" class="font-semibold text-gray-800"></span></div>
            </div>
          </div>

          <div class="bg-orange-50 rounded-xl p-6 mb-5">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i data-lucide="alert-circle" class="w-4 h-4 text-orange-500"></i>
              الأعراض المختارة
            </h3>
            <div id="r_symptoms" class="flex flex-wrap gap-2 mb-3"></div>
            <div id="r_desc_wrap" class="hidden">
              <p class="text-xs text-gray-500 mb-1">الوصف:</p>
              <p id="r_description" class="text-sm text-gray-700"></p>
            </div>
          </div>

          <div class="bg-blue-50 rounded-xl p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
              <i data-lucide="calendar" class="w-4 h-4 text-blue-500"></i>
              تفاصيل إضافية
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">التكرار:</span> <span id="r_frequency" class="font-semibold text-gray-800"></span></div>
              <div><span class="text-gray-500">تاريخ البدء:</span> <span id="r_start_date" class="font-semibold text-gray-800"></span></div>
            </div>
          </div>

          <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-6">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="request_doctor_review" value="1" id="request_doctor_checkbox" class="w-5 h-5 accent-green-600 mt-0.5">
              <div>
                <span class="font-semibold text-gray-800 block mb-1">طلب مراجعة من طبيب متخصص</span>
                <span class="text-sm text-gray-600">سيتم إرسال إشعار للأطباء لمراجعة الحالة وإعطاء تقييم طبي دقيق</span>
              </div>
            </label>
          </div>

          <div class="flex justify-between">
            <button type="button" onclick="goToStep(2)" class="border border-gray-300 text-gray-700 px-8 py-3 rounded-xl font-semibold hover:bg-gray-50 transition flex items-center gap-2">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              السابق
            </button>
            <button type="submit" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              <i data-lucide="send" class="w-4 h-4"></i>
              الحصول على التشخيص المبدئي
            </button>
          </div>
        </div>

      </form>
    </div>
  </main>

  
  <?php if ($showDiagnosis && $diagnosisData): 
    $resultColors = [
      'positive' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700', 'icon' => 'alert-triangle', 'title' => 'احتمالية عالية للإصابة بالصرع'],
      'suspected' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700', 'icon' => 'alert-circle', 'title' => 'احتمالية متوسطة - يحتاج فحص إضافي'],
      'negative' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-700', 'icon' => 'check-circle', 'title' => 'احتمالية منخفضة']
    ];
    $resultStyle = $resultColors[$diagnosisData['result']] ?? $resultColors['negative'];
  ?>
  <div id="diagnosisModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full my-8 shadow-2xl animate-fadeInUp">
      
      <!-- Header -->
      <div class="gradient-primary p-6 rounded-t-2xl text-white">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-2xl font-bold">التشخيص المبدئي</h3>
          <i data-lucide="activity" class="w-8 h-8"></i>
        </div>
        <p class="text-white/90 text-sm">بناءً على الأعراض المُدخلة لـ <?= htmlspecialchars($diagnosisData['child_name']) ?></p>
      </div>

      <div class="p-6 space-y-5">
        
        <!-- نتيجة التشخيص -->
        <div class="<?= $resultStyle['bg'] ?> border <?= $resultStyle['border'] ?> rounded-xl p-5">
          <div class="flex items-start gap-4">
            <div class="<?= $resultStyle['text'] ?> mt-1">
              <i data-lucide="<?= $resultStyle['icon'] ?>" class="w-8 h-8"></i>
            </div>
            <div class="flex-1">
              <h4 class="font-bold text-gray-800 text-lg mb-2"><?= $resultStyle['title'] ?></h4>
              <div class="flex items-center gap-3 mb-3">
                <span class="text-sm text-gray-600">نسبة الاحتمالية:</span>
                <div class="flex-1 bg-white rounded-full h-3 overflow-hidden max-w-xs">
                  <div class="h-full <?= $diagnosisData['result'] === 'positive' ? 'bg-red-500' : ($diagnosisData['result'] === 'suspected' ? 'bg-yellow-500' : 'bg-green-500') ?>" 
                       style="width: <?= $diagnosisData['percentage'] ?>%"></div>
                </div>
                <span class="font-bold <?= $resultStyle['text'] ?> text-lg"><?= $diagnosisData['percentage'] ?>%</span>
              </div>
              <p class="text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($diagnosisData['notes']) ?></p>
            </div>
          </div>
        </div>

        <!-- الأعراض المكتشفة -->
        <div class="bg-gray-50 rounded-xl p-5">
          <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i data-lucide="clipboard-list" class="w-5 h-5 text-purple-600"></i>
            الأعراض المكتشفة
          </h4>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($diagnosisData['symptoms'] as $symptom): ?>
              <span class="bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-sm text-gray-700">
                <?= htmlspecialchars($symptom) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- التوصيات -->
        <div class="bg-blue-50 rounded-xl p-5">
          <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i data-lucide="lightbulb" class="w-5 h-5 text-blue-600"></i>
            التوصيات
          </h4>
          <ul class="space-y-2">
            <?php foreach ($diagnosisData['recommendations'] as $recommendation): ?>
              <li class="flex items-start gap-2 text-sm text-gray-700">
                <i data-lucide="check" class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($recommendation) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- تنبيه هام -->
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <i data-lucide="info" class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm text-gray-700 leading-relaxed">
              <strong class="text-purple-700">ملاحظة هامة:</strong> هذا تقييم أولي تلقائي وليس تشخيصاً طبياً نهائياً. 
              للحصول على تشخيص دقيق، يُنصح بمراجعة طبيب أعصاب متخصص.
            </p>
          </div>
        </div>

        <!-- الأزرار -->
        <div class="flex gap-3 pt-2">
          <button onclick="window.location.href='diagnosis.php'" 
                  class="flex-1 gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center justify-center gap-2">
            <i data-lucide="file-text" class="w-5 h-5"></i>
            عرض جميع التقارير
          </button>
          <button onclick="window.location.href='assessment.php'" 
                  class="flex-1 border-2 border-purple-600 text-purple-600 py-3 rounded-xl font-semibold hover:bg-purple-50 transition flex items-center justify-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            فحص جديد
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script>
    lucide.createIcons();

    function goToStep(step) {
      [1,2,3].forEach(i => {
        document.getElementById('step' + i).classList.add('hidden');
        const ind = document.getElementById('step' + i + '-indicator');
        ind.className = 'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm';
        if (i < step) ind.classList.add('step-done');
        else if (i === step) ind.classList.add('step-active');
        else { ind.classList.add('bg-gray-200'); ind.classList.add('text-gray-500'); }
      });
      document.getElementById('step' + step).classList.remove('hidden');
      if (step === 3) buildReview();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function buildReview() {
      document.getElementById('r_name').textContent   = document.getElementById('f_child_name').value || '—';
      document.getElementById('r_age').textContent    = (document.getElementById('f_age').value || '—') + ' سنوات';
      const genderVal = document.getElementById('f_gender').value;
      document.getElementById('r_gender').textContent = genderVal === 'female' ? 'أنثى' : 'ذكر';
      document.getElementById('r_weight').textContent = (document.getElementById('f_weight').value || '—') + ' كجم';
      document.getElementById('r_birth').textContent  = document.getElementById('f_birth_date').value || '—';

      const checked = [...document.querySelectorAll('input[name="symptoms[]"]:checked')];
      const sympBox = document.getElementById('r_symptoms');
      sympBox.innerHTML = '';
      if (checked.length === 0) {
        sympBox.innerHTML = '<span class="text-gray-400 text-sm">لم يتم اختيار أعراض</span>';
      } else {
        checked.forEach(cb => {
          const tag = document.createElement('span');
          tag.className = 'bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium';
          tag.textContent = cb.value;
          sympBox.appendChild(tag);
        });
      }

      const desc = document.getElementById('f_description').value.trim();
      if (desc) {
        document.getElementById('r_desc_wrap').classList.remove('hidden');
        document.getElementById('r_description').textContent = desc;
      } else {
        document.getElementById('r_desc_wrap').classList.add('hidden');
      }

      const freqMap = { daily:'يومياً', weekly:'أسبوعياً', monthly:'شهرياً', rarely:'نادراً' };
      const freqVal = document.getElementById('f_frequency').value;
      document.getElementById('r_frequency').textContent   = freqMap[freqVal] || '—';
      document.getElementById('r_start_date').textContent  = document.getElementById('f_start_date').value || '—';
    }
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
