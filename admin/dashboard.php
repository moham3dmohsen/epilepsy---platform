<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);

$total_users   = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'")->fetch_assoc()['cnt'];
$total_reports = $conn->query("SELECT COUNT(*) as cnt FROM reports")->fetch_assoc()['cnt'];
$total_videos  = $conn->query("SELECT COUNT(*) as cnt FROM videos")->fetch_assoc()['cnt'];
$total_articles= $conn->query("SELECT COUNT(*) as cnt FROM articles")->fetch_assoc()['cnt'];

$recent_users = $conn->query(
    "SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$days_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $cnt   = $conn->query("SELECT COUNT(*) as cnt FROM reports WHERE DATE(created_at)='$date'")->fetch_assoc()['cnt'];
    $days_data[] = ['label' => $label, 'count' => (int)$cnt];
}
$max_count = max(array_column($days_data, 'count') ?: [1]);
$max_count = $max_count < 1 ? 1 : $max_count;

$role_map   = ['parent' => 'ولي أمر', 'doctor' => 'طبيب', 'admin' => 'مدير'];
$role_color = ['parent' => 'bg-blue-100 text-blue-700', 'doctor' => 'bg-purple-100 text-purple-700', 'admin' => 'bg-orange-100 text-orange-700'];
$status_color = ['active' => 'bg-green-100 text-green-700', 'suspended' => 'bg-red-100 text-red-700'];
$status_map   = ['active' => 'نشط', 'suspended' => 'موقوف'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم المدير - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.5s ease forwards; }
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
      <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>المستخدمون</span></a>
      <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="mail" class="w-5 h-5"></i><span>رسائل التواصل</span></a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span></a>
      <a href="content.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>التقارير</span></a>
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
    
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">مرحباً، <?= htmlspecialchars($user['name']) ?></h1>
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
              <i data-lucide="users" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-blue-500 text-sm font-semibold">المستخدمون</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total_users ?></p>
          <p class="text-gray-500 text-sm">إجمالي المستخدمين</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.1s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-red-500 text-sm font-semibold">التشخيصات</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total_reports ?></p>
          <p class="text-gray-500 text-sm">حالات مشخصة</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.2s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="video" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-purple-500 text-sm font-semibold">الفيديوهات</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total_videos ?></p>
          <p class="text-gray-500 text-sm">فيديو تعليمي</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fadeInUp hover:shadow-md transition-shadow" style="animation-delay:0.3s;opacity:0">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
              <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
            </div>
            <span class="text-green-500 text-sm font-semibold">المقالات</span>
          </div>
          <p class="text-3xl font-bold text-gray-800 mb-1"><?= $total_articles ?></p>
          <p class="text-gray-500 text-sm">مقال تعليمي</p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp" style="animation-delay:0.4s;opacity:0">
          <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="w-5 h-5 text-purple-600"></i>
            التقارير - آخر 7 أيام
          </h2>
          <div class="flex items-end gap-3 h-40">
            <?php foreach ($days_data as $day): ?>
            <?php $height = $max_count > 0 ? round(($day['count'] / $max_count) * 100) : 0; ?>
            <div class="flex-1 flex flex-col items-center gap-2">
              <span class="text-xs font-bold text-purple-700"><?= $day['count'] > 0 ? $day['count'] : '' ?></span>
              <div class="w-full rounded-t-lg gradient-primary transition-all" style="height: <?= max($height, 4) ?>%;min-height:4px;"></div>
              <span class="text-xs text-gray-500"><?= $day['label'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp" style="animation-delay:0.5s;opacity:0">
          <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="zap" class="w-5 h-5 text-purple-600"></i>
            إجراءات سريعة
          </h2>
          <div class="grid grid-cols-2 gap-3">
            <a href="users.php" class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors group">
              <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="user-plus" class="w-5 h-5 text-white"></i>
              </div>
              <span class="text-sm font-semibold text-gray-700">إضافة مستخدم</span>
            </a>
            <a href="videos.php" class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors group">
              <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="video" class="w-5 h-5 text-white"></i>
              </div>
              <span class="text-sm font-semibold text-gray-700">رفع فيديو</span>
            </a>
            <a href="content.php" class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors group">
              <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="file-plus" class="w-5 h-5 text-white"></i>
              </div>
              <span class="text-sm font-semibold text-gray-700">إضافة مقال</span>
            </a>
            <a href="reports.php" class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors group">
              <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="bar-chart-2" class="w-5 h-5 text-white"></i>
              </div>
              <span class="text-sm font-semibold text-gray-700">عرض التقارير</span>
            </a>
          </div>
        </div>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 animate-fadeInUp" style="animation-delay:0.6s;opacity:0">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-800">آخر المستخدمين المسجلين</h2>
          <a href="users.php" class="text-purple-600 text-sm hover:underline font-semibold">عرض الكل</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الاسم</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">البريد الإلكتروني</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الدور</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">تاريخ التسجيل</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($recent_users as $u): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-bold text-sm">
                      <?= mb_substr($u['name'], 0, 1, 'UTF-8') ?>
                    </div>
                    <span class="font-semibold text-gray-800"><?= htmlspecialchars($u['name']) ?></span>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($u['email']) ?></td>
                <td class="px-6 py-4">
                  <span class="<?= $role_color[$u['role']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full font-semibold">
                    <?= $role_map[$u['role']] ?? $u['role'] ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="<?= $status_color[$u['status']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full font-semibold">
                    <?= $status_map[$u['status']] ?? $u['status'] ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500 text-sm"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recent_users)): ?>
              <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">لا توجد بيانات بعد</td></tr>
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
