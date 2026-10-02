<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);

$from          = sanitize($_GET['from'] ?? '');
$to            = sanitize($_GET['to'] ?? '');
$result_filter = sanitize($_GET['result'] ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($from) {
    $where   .= " AND r.created_at >= ?";
    $params[] = $from . ' 00:00:00';
    $types   .= "s";
}
if ($to) {
    $where   .= " AND r.created_at <= ?";
    $params[] = $to . ' 23:59:59';
    $types   .= "s";
}
if ($result_filter) {
    $where   .= " AND r.diagnosis_result = ?";
    $params[] = $result_filter;
    $types   .= "s";
}

$sql = "SELECT r.*, u.name as doctor_name, p.name as patient_name,
               c.name as child_name, c.age
        FROM reports r
        JOIN users u ON r.doctor_id  = u.id
        JOIN users p ON r.patient_id = p.id
        LEFT JOIN children c ON c.parent_id = p.id
        $where
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($reports);
$positive_count = count(array_filter($reports, fn($r) => $r['diagnosis_result'] === 'positive'));
$positive_pct   = $total > 0 ? round(($positive_count / $total) * 100) : 0;
$ages           = array_filter(array_column($reports, 'age'));
$avg_age        = count($ages) > 0 ? round(array_sum($ages) / count($ages), 1) : 0;

$result_map   = ['positive' => 'مصاب', 'negative' => 'سليم', 'suspected' => 'مشتبه به'];
$result_color = ['positive' => 'bg-red-100 text-red-700', 'negative' => 'bg-green-100 text-green-700', 'suspected' => 'bg-orange-100 text-orange-700'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>التقارير - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    @media print {
      .sidebar, header, .no-print { display: none !important; }
      main { margin-right: 0 !important; }
    }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
  </style>
</head>
<body class="bg-gray-50">

  
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20">
      <div class="flex items-center gap-3">
        <img src="../assets/img/logo1.png" class="h-10 w-auto">
        <span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>المستخدمون</span></a>
      <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="mail" class="w-5 h-5"></i><span>رسائل التواصل</span></a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span></a>
      <a href="content.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span></a>
      <a href="reports.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
    </nav>
    <div class="p-4">
      <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all">
        <i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span>
      </a>
    </div>
  </aside>

  
  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40 no-print">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">التقارير الطبية</h1>
        <p class="text-sm text-gray-500">جميع تقارير الأطباء</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <button onclick="window.print()" class="flex items-center gap-2 bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
          <i data-lucide="printer" class="w-4 h-4"></i> طباعة / تصدير
        </button>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">

      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-blue-500 text-sm font-semibold">الإجمالي</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total ?></p>
          <p class="text-gray-500 text-sm">إجمالي التقارير</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center">
              <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-red-500 text-sm font-semibold">الإيجابية</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $positive_pct ?>%</p>
          <p class="text-gray-500 text-sm">نسبة الحالات المصابة (<?= $positive_count ?>)</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
              <i data-lucide="user" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-blue-500 text-sm font-semibold">متوسط العمر</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $avg_age ?></p>
          <p class="text-gray-500 text-sm">متوسط عمر الأطفال (سنوات)</p>
        </div>
      </div>

      
      <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 flex flex-wrap gap-4 items-end no-print">
        <div class="min-w-44">
          <label class="block text-sm font-semibold text-gray-700 mb-2">من تاريخ</label>
          <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
        </div>
        <div class="min-w-44">
          <label class="block text-sm font-semibold text-gray-700 mb-2">إلى تاريخ</label>
          <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
        </div>
        <div class="min-w-40">
          <label class="block text-sm font-semibold text-gray-700 mb-2">نتيجة التشخيص</label>
          <select name="result" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
            <option value="">الكل</option>
            <option value="positive"  <?= $result_filter==='positive' ?'selected':'' ?>>مصاب</option>
            <option value="negative"  <?= $result_filter==='negative' ?'selected':'' ?>>سليم</option>
            <option value="suspected" <?= $result_filter==='suspected'?'selected':'' ?>>مشتبه به</option>
          </select>
        </div>
        <button type="submit" class="gradient-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2">
          <i data-lucide="filter" class="w-4 h-4"></i> تصفية
        </button>
        <a href="reports.php" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 flex items-center gap-2">
          <i data-lucide="x" class="w-4 h-4"></i> إعادة تعيين
        </a>
      </form>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
          <h2 class="font-bold text-gray-800">نتائج التقارير (<?= $total ?>)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">الطفل</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">العمر</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">ولي الأمر</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">الطبيب</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">النتيجة</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">النسبة</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">التاريخ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($reports as $i => $r): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4 text-gray-500 text-sm"><?= $i + 1 ?></td>
                <td class="px-5 py-4 font-semibold text-gray-800"><?= htmlspecialchars($r['child_name'] ?? '-') ?></td>
                <td class="px-5 py-4 text-gray-600 text-sm"><?= $r['age'] ? $r['age'] . ' سنوات' : '-' ?></td>
                <td class="px-5 py-4 text-gray-600 text-sm"><?= htmlspecialchars($r['patient_name']) ?></td>
                <td class="px-5 py-4 text-gray-600 text-sm"><?= htmlspecialchars($r['doctor_name']) ?></td>
                <td class="px-5 py-4">
                  <span class="<?= $result_color[$r['diagnosis_result']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full font-semibold">
                    <?= $result_map[$r['diagnosis_result']] ?? $r['diagnosis_result'] ?>
                  </span>
                </td>
                <td class="px-5 py-4">
                  <?php if ($r['epilepsy_percentage'] !== null): ?>
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-2 w-20">
                      <div class="h-2 rounded-full <?= $r['epilepsy_percentage'] >= 70 ? 'bg-red-500' : ($r['epilepsy_percentage'] >= 40 ? 'bg-orange-400' : 'bg-green-500') ?>" style="width:<?= $r['epilepsy_percentage'] ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700"><?= $r['epilepsy_percentage'] ?>%</span>
                  </div>
                  <?php else: ?>
                  <span class="text-gray-400 text-sm">-</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-gray-500 text-sm"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($reports)): ?>
              <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">لا توجد تقارير تطابق معايير البحث</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

  <script>lucide.createIcons();</script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
