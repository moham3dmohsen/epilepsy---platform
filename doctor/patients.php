<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('doctor');
$user = refreshCurrentUser($conn);

$patients = $conn->query("SELECT DISTINCT u.id, u.name, u.phone, c.name as child_name, c.age, 
    (SELECT diagnosis_result FROM reports WHERE patient_id=u.id ORDER BY created_at DESC LIMIT 1) as last_result,
    (SELECT epilepsy_percentage FROM reports WHERE patient_id=u.id ORDER BY created_at DESC LIMIT 1) as last_pct,
    (SELECT created_at FROM reports WHERE patient_id=u.id ORDER BY created_at DESC LIMIT 1) as last_check
    FROM users u 
    JOIN children c ON c.parent_id=u.id 
    JOIN assessments a ON a.parent_id=u.id 
    WHERE u.role='parent' 
    ORDER BY last_check DESC")->fetch_all(MYSQLI_ASSOC);

$status_map = ['positive'=>'مصاب','negative'=>'سليم','suspected'=>'مشتبه به'];
$status_color = ['positive'=>'bg-red-100 text-red-700','negative'=>'bg-green-100 text-green-700','suspected'=>'bg-orange-100 text-orange-700'];
$unread = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id={$user['id']} AND is_read=0")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>مرضاي - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
  </style>
</head>
<body class="bg-gray-50">
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20">
      <div class="flex items-center gap-3">
        <img src="../assets/img/logoDark.png" class="h-10 w-auto"><span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="patients.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>مرضاي</span></a>
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
        <h1 class="text-2xl font-bold text-gray-800">مرضاي</h1>
        <p class="text-sm text-gray-500">إدارة قائمة المرضى</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>
    <div class="p-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
          <h2 class="text-lg font-bold text-gray-800">قائمة المرضى (<?= count($patients) ?>)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">اسم الطفل</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">العمر</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">ولي الأمر</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">رقم الهاتف</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">الحالة</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">نسبة الصرع</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">آخر فحص</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($patients as $p): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($p['child_name']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= $p['age'] ?> سنوات</td>
                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($p['name']) ?></td>
                <td class="px-6 py-4 text-gray-500 text-sm" dir="ltr"><?= htmlspecialchars($p['phone']) ?></td>
                <td class="px-6 py-4"><span class="<?= $status_color[$p['last_result']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full"><?= $status_map[$p['last_result']] ?? 'جديد' ?></span></td>
                <td class="px-6 py-4"><?= $p['last_pct'] ?? '—' ?>%</td>
                <td class="px-6 py-4 text-gray-500 text-sm"><?= $p['last_check'] ? date('d M Y', strtotime($p['last_check'])) : '—' ?></td>
                <td class="px-6 py-4">
                  <a href="messages.php?with=<?= $p['id'] ?>" class="gradient-primary text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 inline-flex items-center gap-1">
                    <i data-lucide="message-circle" class="w-3 h-3"></i> رسالة
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($patients)): ?>
              <tr><td colspan="8" class="px-6 py-8 text-center text-gray-400">لا يوجد مرضى بعد</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
  <script>lucide.createIcons();</script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
