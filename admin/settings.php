<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);
$success = '';
$settings = getPlatformSettings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    savePlatformSettings($conn, $_POST);
    $settings = getPlatformSettings($conn);
    $success = 'تم حفظ الإعدادات بنجاح';
}

function settingChecked(array $settings, string $key): string {
    return (($settings[$key] ?? '0') === '1') ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الإعدادات - منصة توعية لمرض الصرع</title>
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
      <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="mail" class="w-5 h-5"></i><span>رسائل التواصل</span></a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span></a>
      <a href="content.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="settings.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
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
        <h1 class="text-2xl font-bold text-gray-800">الإعدادات</h1>
        <p class="text-sm text-gray-500">إعدادات النظام والإشعارات</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8 max-w-3xl mx-auto">

      <?php if ($success): ?>
      <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST">

        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="bell" class="w-5 h-5 text-purple-600"></i>
            إعدادات الإشعارات
          </h2>
          <div class="space-y-4">
            <?php
            $notifs = [
              ['notif_new_user',   'تسجيل مستخدم جديد', 'تلقي إشعار عند تسجيل مستخدم جديد في المنصة'],
              ['notif_new_report', 'تقرير طبي جديد',    'تلقي إشعار عند إصدار تقرير طبي جديد'],
              ['notif_critical',   'حالات حرجة',        'تنبيه فوري عند اكتشاف حالة تستدعي الاهتمام'],
              ['notif_contact',    'رسائل التواصل',     'تلقي إشعار عند وصول رسالة من نموذج التواصل'],
              ['notif_system',   'تحديثات النظام',    'إشعارات حول تحديثات وصيانة المنصة'],
            ];
            foreach ($notifs as [$key, $label, $desc]):
            ?>
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
              <div>
                <p class="font-semibold text-gray-800"><?= $label ?></p>
                <p class="text-sm text-gray-500"><?= $desc ?></p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= settingChecked($settings, $key) ?> class="sr-only peer">
                <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="sliders" class="w-5 h-5 text-purple-600"></i>
            إعدادات المنصة
          </h2>
          <div class="space-y-4">
            <?php
            $platform = [
              ['allow_registration', 'السماح بالتسجيل الجديد', 'تفعيل أو تعطيل تسجيل المستخدمين الجدد'],
              ['maintenance_mode',   'وضع الصيانة',           'تفعيل وضع الصيانة يوقف الوصول للمستخدمين'],
              ['content_review',     'مراجعة المحتوى',        'مراجعة المقالات والفيديوهات قبل النشر'],
            ];
            foreach ($platform as [$key, $label, $desc]):
            ?>
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
              <div>
                <p class="font-semibold text-gray-800"><?= $label ?></p>
                <p class="text-sm text-gray-500"><?= $desc ?></p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= settingChecked($settings, $key) ?> class="sr-only peer">
                <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
              </label>
            </div>
            <?php endforeach; ?>
            <div class="p-4 bg-gray-50 rounded-xl mt-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">رسالة وضع الصيانة</label>
              <textarea name="maintenance_message" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"><?= htmlspecialchars($settings['maintenance_message'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="shield" class="w-5 h-5 text-purple-600"></i>
            إعدادات الأمان
          </h2>
          <div class="space-y-4">
            <div class="p-4 bg-gray-50 rounded-xl">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-semibold text-gray-800">المصادقة الثنائية</p>
                  <p class="text-sm text-gray-500">تفعيل التحقق بخطوتين لحسابات المدراء</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" name="admin_2fa" value="1" <?= settingChecked($settings, 'admin_2fa') ?> class="sr-only peer">
                  <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
              </div>
              <p class="text-xs text-amber-600 mt-3">يُحفظ الإعداد في قاعدة البيانات؛ تفعيل المصادقة الثنائية الفعلية يتطلب ربطاً لاحقاً بمزود التحقق.</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
              <label class="block text-sm font-semibold text-gray-700 mb-2">مدة انتهاء الجلسة (دقائق)</label>
              <input type="number" name="session_timeout" value="<?= (int)($settings['session_timeout'] ?? 60) ?>" min="15" max="480" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i> حفظ الإعدادات
          </button>
        </div>

      </form>
    </div>
  </main>

  <script>lucide.createIcons();</script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
