<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
// ضمان ظهور الاسم
if (empty($user['name'])) {
    $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']);
    if ($r && $row = $r->fetch_assoc()) {
        $user['name'] = $row['name'];
        $user['profile_picture'] = $row['profile_picture'];
    }
}

$child = null;
$stmt = $conn->prepare("SELECT * FROM children WHERE parent_id = ? LIMIT 1");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$child = $stmt->get_result()->fetch_assoc();

$report = null;
$doctor_name = '';
if ($child) {
    $stmt2 = $conn->prepare("SELECT r.*, CASE WHEN r.doctor_id = 0 THEN 'النظام الآلي' ELSE u.name END as doctor_name, CASE WHEN r.doctor_id = 0 THEN 1 ELSE 0 END as is_auto FROM reports r LEFT JOIN users u ON r.doctor_id = u.id WHERE r.patient_id = ? ORDER BY r.created_at DESC LIMIT 1");
    $stmt2->bind_param("i", $user['id']);
    $stmt2->execute();
    $report = $stmt2->get_result()->fetch_assoc();
    $doctor_name = $report['doctor_name'] ?? '';
}

$stmt3 = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt3->bind_param("i", $user['id']);
$stmt3->execute();
$unread = $stmt3->get_result()->fetch_assoc()['cnt'];

$reports = [];
if ($child) {
    $stmt4 = $conn->prepare("SELECT r.*, DATE_FORMAT(r.created_at, '%d %M %Y') as date_ar FROM reports r WHERE r.patient_id = ? ORDER BY r.created_at DESC LIMIT 3");
    $stmt4->bind_param("i", $user['id']);
    $stmt4->execute();
    $reports = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
}

$status_map = ['positive' => 'إيجابي', 'negative' => 'سلبي', 'suspected' => 'مشتبه به'];
$status_color = ['positive' => 'bg-red-100 text-red-700', 'negative' => 'bg-green-100 text-green-700', 'suspected' => 'bg-orange-100 text-orange-700'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - لوحة تحكم المستخدم</title>
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
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
  </style>
</head>
<body class="bg-gray-50">

  
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20">
      <div class="flex items-center gap-3">
        <img src="../assets/img/logoDark.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto"><span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>الرئيسية</span>
      </a>
      <a href="assessment.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
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
        <h1 class="text-2xl font-bold text-gray-800">مرحباً <?= htmlspecialchars($user['name']) ?></h1>
        <p class="text-sm text-gray-500"><?= date('l d F Y') ?></p>
      </div>
      <div class="flex items-center gap-4">
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">
      
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="activity" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-orange-500 text-sm font-semibold">تحت المتابعة</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $child ? htmlspecialchars($child['name']) : 'لا يوجد' ?></p>
          <p class="text-gray-500 text-sm">حالة طفلي</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.1s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-blue-500 text-sm font-semibold">آخر فحص</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $report ? date('d M', strtotime($report['created_at'])) : '--' ?></p>
          <p class="text-gray-500 text-sm"><?= $report ? date('Y', strtotime($report['created_at'])) : '' ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.2s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="message-circle" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-green-500 text-sm font-semibold">جديد</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $unread ?></p>
          <p class="text-gray-500 text-sm">رسائل جديدة</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.3s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="percent" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-purple-500 text-sm font-semibold">النسبة</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $report ? $report['epilepsy_percentage'] : '0' ?>%</p>
          <p class="text-gray-500 text-sm">نسبة الصرع</p>
        </div>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8 animate-fadeInUp" style="animation-delay:0.4s;opacity:0">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-gray-800">حالة طفلي</h2>
          <a href="messages.php" class="text-purple-600 text-sm font-semibold hover:text-purple-800">تواصل مع الطبيب</a>
        </div>
        <?php if ($child): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <p class="text-sm text-gray-500 mb-2">اسم الطفل</p>
            <p class="text-2xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($child['name']) ?></p>
            <p class="text-sm text-gray-500 mb-2">العمر</p>
            <p class="text-xl font-bold text-gray-800 mb-4"><?= $child['age'] ?> سنوات</p>
            <p class="text-sm text-gray-500 mb-2">الطبيب المعالج</p>
            <p class="text-lg font-semibold text-purple-600"><?= $doctor_name ?: 'لم يتم التعيين بعد' ?></p>
          </div>
          <div>
            <?php if ($report): ?>
            <p class="text-sm text-gray-500 mb-3">نسبة الصرع</p>
            <div class="mb-6">
              <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold text-gray-800"><?= $report['epilepsy_percentage'] ?>%</span>
                <span class="text-sm text-gray-500"><?= $status_map[$report['diagnosis_result']] ?></span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full" style="width:<?= $report['epilepsy_percentage'] ?>%;background:linear-gradient(135deg,#8F00FF,#5500CC)"></div>
              </div>
            </div>
            <?php endif; ?>
            <button onclick="location.href='messages.php'" class="w-full gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
              <i data-lucide="message-circle" class="w-4 h-4"></i>
              تواصل مع الطبيب
            </button>
          </div>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
          <p class="text-gray-500 mb-4">لم تقم بإضافة بيانات طفلك بعد</p>
          <a href="assessment.php" class="inline-flex items-center gap-2 gradient-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90">
            <i data-lucide="plus" class="w-4 h-4"></i>
            ابدأ الفحص الآن
          </a>
        </div>
        <?php endif; ?>
      </div>

      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp" style="animation-delay:0.5s;opacity:0">
          <h2 class="text-lg font-bold text-gray-800 mb-4">آخر التقارير</h2>
          <?php if (count($reports) > 0): ?>
          <div class="space-y-3">
            <?php foreach ($reports as $r): ?>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-semibold text-gray-800 text-sm">تقرير <?= $r['date_ar'] ?></p>
                  <p class="text-xs text-gray-500">نسبة الصرع: <?= $r['epilepsy_percentage'] ?>%</p>
                </div>
                <span class="<?= $status_color[$r['diagnosis_result']] ?> text-xs px-2 py-1 rounded"><?= $status_map[$r['diagnosis_result']] ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-gray-400 text-sm text-center py-4">لا توجد تقارير بعد</p>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp" style="animation-delay:0.6s;opacity:0">
          <h2 class="text-lg font-bold text-gray-800 mb-4">محتوى موصى به</h2>
          <div class="space-y-3">
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-purple-50 cursor-pointer transition-colors" onclick="location.href='education.php'">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 gradient-primary rounded flex items-center justify-center flex-shrink-0">
                  <i data-lucide="book-open" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                  <p class="font-semibold text-gray-800 text-sm">ما هو مرض الصرع؟</p>
                  <p class="text-xs text-gray-500">مقال توعوي</p>
                </div>
              </div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-purple-50 cursor-pointer transition-colors" onclick="location.href='videos.php'">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 gradient-primary rounded flex items-center justify-center flex-shrink-0">
                  <i data-lucide="video" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                  <p class="font-semibold text-gray-800 text-sm">الإسعافات الأولية</p>
                  <p class="text-xs text-gray-500">فيديو توعوي</p>
                </div>
              </div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-purple-50 cursor-pointer transition-colors" onclick="location.href='education.php'">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 gradient-primary rounded flex items-center justify-center flex-shrink-0">
                  <i data-lucide="book-open" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                  <p class="font-semibold text-gray-800 text-sm">الصرع والمدرسة</p>
                  <p class="text-xs text-gray-500">مقال إرشادي</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    lucide.createIcons();

  </script>
  <?php notificationScripts('../api'); ?>
<?php require_once '../config/view_as_banner.php'; ?>
</body>
</html>
