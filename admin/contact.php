<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);

$contacts = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$total  = count($contacts);
$today  = $conn->query("SELECT COUNT(*) as c FROM contact_messages WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>رسائل التواصل - منصة توعية لمرض الصرع</title>
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
        <img src="../assets/img/logo1.png" class="h-10 w-auto">
        <span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>المستخدمون</span></a>
      <a href="contact.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="mail" class="w-5 h-5"></i><span>رسائل التواصل</span></a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span></a>
      <a href="content.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
    </nav>
    <div class="p-4">
      <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all"><i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span></a>
    </div>
  </aside>

  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">رسائل التواصل</h1>
        <p class="text-sm text-gray-500">رسائل نموذج «تواصل معنا» من الصفحة الرئيسية</p>
      </div>
      <div class="flex items-center gap-4">
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <p class="text-3xl font-bold text-gray-800"><?= $total ?></p>
          <p class="text-gray-500 text-sm">إجمالي الرسائل</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <p class="text-3xl font-bold text-purple-600"><?= $today ?></p>
          <p class="text-gray-500 text-sm">رسائل اليوم</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($contacts)): ?>
        <div class="p-16 text-center text-gray-400">لا توجد رسائل تواصل بعد</div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
          <?php foreach ($contacts as $c): ?>
          <div class="p-6 hover:bg-purple-50/50 transition">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
              <div>
                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($c['name']) ?></p>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($c['email']) ?><?= $c['phone'] ? ' · ' . htmlspecialchars($c['phone']) : '' ?></p>
              </div>
              <div class="text-left">
                <p class="text-xs text-gray-400"><?= date('d/m/Y h:i A', strtotime($c['created_at'])) ?></p>
                <?php if (!empty($c['subject'])): ?>
                <span class="inline-block mt-1 bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded-full font-semibold"><?= htmlspecialchars($c['subject']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-gray-700 text-sm leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($c['message']) ?></div>
            <div class="flex flex-wrap gap-2 mt-3">
              <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="text-sm text-purple-600 font-semibold hover:underline flex items-center gap-1">
                <i data-lucide="reply" class="w-4 h-4"></i> رد بالبريد
              </a>
              <?php if ($c['phone']): ?>
              <a href="tel:<?= htmlspecialchars($c['phone']) ?>" class="text-sm text-green-600 font-semibold hover:underline flex items-center gap-1">
                <i data-lucide="phone" class="w-4 h-4"></i> اتصال
              </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script>lucide.createIcons();</script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
