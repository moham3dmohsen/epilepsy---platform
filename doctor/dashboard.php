<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('doctor');
$user = refreshCurrentUser($conn);

$total_patients = $conn->query("SELECT COUNT(DISTINCT patient_id) as cnt FROM reports WHERE doctor_id={$user['id']}")->fetch_assoc()['cnt'];
$monthly_reports = $conn->query("SELECT COUNT(*) as cnt FROM reports WHERE doctor_id={$user['id']} AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['cnt'];
$unread_msgs = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id={$user['id']} AND is_read=0")->fetch_assoc()['cnt'];
$pending = $conn->query("SELECT COUNT(*) as cnt FROM assessments WHERE status='pending'")->fetch_assoc()['cnt'];

// حالة التحقق من الكارنيه
$doctor_status = $user['doctor_status'] ?? null;

$stmt = $conn->prepare("SELECT DISTINCT u.id, u.name, c.name as child_name, c.age, r.diagnosis_result, r.epilepsy_percentage, r.created_at FROM reports r JOIN users u ON r.patient_id=u.id JOIN children c ON c.parent_id=u.id WHERE r.doctor_id=? ORDER BY r.created_at DESC LIMIT 5");
$stmt->bind_param("i", $user['id']); $stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_map = ['positive'=>'مصاب','negative'=>'سليم','suspected'=>'مشتبه به'];
$status_color = ['positive'=>'bg-red-100 text-red-700','negative'=>'bg-green-100 text-green-700','suspected'=>'bg-orange-100 text-orange-700'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم الطبيب - منصة توعية لمرض الصرع</title>
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
                <img src="logo1.png" class="h-10 w-auto">
        <span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="patients.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>مرضاي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="file-text" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="messages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="message-circle" class="w-5 h-5"></i><span>الرسائل</span></a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
    </nav>
    <div class="p-4"><a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all"><i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span></a></div>
  </aside>
  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">مرحباً د. <?= htmlspecialchars($user['name']) ?></h1>
        <p class="text-sm text-gray-500"><?= date('l d F Y') ?></p>
      </div>
      <div class="flex items-center gap-4">
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>
    <div class="p-8">
      <!-- بانر حالة التحقق من الكارنيه -->
      <?php if ($doctor_status === 'pending'): ?>
      <div class="mb-6 flex items-start gap-4 bg-yellow-50 border border-yellow-200 rounded-2xl px-6 py-4">
        <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0">
          <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
        </div>
        <div>
          <p class="font-bold text-yellow-800">حسابك قيد المراجعة</p>
          <p class="text-sm text-yellow-700 mt-0.5">تم استلام كارنيه النقابة الطبية الخاص بك وهو قيد المراجعة من قِبل الإدارة. سيتم تفعيل حسابك بالكامل بعد الموافقة.</p>
        </div>
      </div>
      <?php elseif ($doctor_status === 'approved'): ?>
      <div class="mb-6 flex items-start gap-4 bg-green-50 border border-green-200 rounded-2xl px-6 py-4">
        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
          <i data-lucide="badge-check" class="w-5 h-5 text-green-600"></i>
        </div>
        <div>
          <p class="font-bold text-green-800">حساب موثّق ✓</p>
          <p class="text-sm text-green-700 mt-0.5">تم التحقق من كارنيه النقابة الطبية الخاص بك والموافقة على حسابك.</p>
        </div>
      </div>
      <?php elseif ($doctor_status === 'rejected'): ?>
      <div class="mb-6 flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl px-6 py-4">
        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
          <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
        </div>
        <div>
          <p class="font-bold text-red-800">تم رفض طلب التوثيق</p>
          <p class="text-sm text-red-700 mt-0.5">للأسف تم رفض كارنيه النقابة المرفوع. يرجى التواصل مع الإدارة لمزيد من التفاصيل.</p>
        </div>
      </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center"><i data-lucide="users" class="w-6 h-6 text-white"></i></div>
            <span class="text-green-500 text-sm font-semibold">مرضاي</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total_patients ?></p>
          <p class="text-gray-500 text-sm">عدد المرضى</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.1s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center"><i data-lucide="file-text" class="w-6 h-6 text-white"></i></div>
            <span class="text-blue-500 text-sm font-semibold">هذا الشهر</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $monthly_reports ?></p>
          <p class="text-gray-500 text-sm">تقارير هذا الشهر</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.2s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center"><i data-lucide="message-circle" class="w-6 h-6 text-white"></i></div>
            <span class="text-blue-500 text-sm font-semibold">جديد</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $unread_msgs ?></p>
          <p class="text-gray-500 text-sm">رسائل جديدة</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.3s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center"><i data-lucide="alert-circle" class="w-6 h-6 text-white"></i></div>
            <span class="text-red-500 text-sm font-semibold">معلق</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $pending ?></p>
          <p class="text-gray-500 text-sm">فحوصات معلقة</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 animate-fadeInUp" style="animation-delay:0.4s;opacity:0">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-800">آخر المرضى</h2>
          <a href="patients.php" class="text-purple-600 text-sm hover:underline">عرض الكل</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">اسم الطفل</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">العمر</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">ولي الأمر</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">آخر فحص</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">الحالة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($recent as $r): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($r['child_name']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= $r['age'] ?> سنوات</td>
                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($r['name']) ?></td>
                <td class="px-6 py-4 text-gray-500 text-sm"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td class="px-6 py-4"><span class="<?= $status_color[$r['diagnosis_result']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full"><?= $status_map[$r['diagnosis_result']] ?? $r['diagnosis_result'] ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recent)): ?>
              <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">لا توجد بيانات بعد</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
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
