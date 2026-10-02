<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
if (empty($user['name'])) { $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']); if ($r && $row = $r->fetch_assoc()) { $user['name'] = $row['name']; $user['profile_picture'] = $row['profile_picture']; } }

$stmt = $conn->prepare("SELECT r.*, CASE WHEN r.doctor_id = 0 THEN 'النظام الآلي' ELSE u.name END as doctor_name, DATE_FORMAT(r.created_at,'%d %M %Y') as date_ar, CASE WHEN r.doctor_id = 0 THEN 1 ELSE 0 END as is_auto FROM reports r LEFT JOIN users u ON r.doctor_id=u.id WHERE r.patient_id=? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$latest  = $reports[0] ?? null;

$conn->query("UPDATE reports SET status='read' WHERE patient_id=" . (int)$user['id']);

$status_map   = ['positive'=>'إيجابي','negative'=>'سلبي','suspected'=>'مشتبه به'];
$status_color = ['positive'=>'bg-red-100 text-red-600','negative'=>'bg-green-100 text-green-600','suspected'=>'bg-orange-100 text-orange-600'];
$status_badge = ['positive'=>'bg-red-100 text-red-700','negative'=>'bg-green-100 text-green-700','suspected'=>'bg-orange-100 text-orange-700'];

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc()['cnt'];

$pct    = $latest ? (int)$latest['epilepsy_percentage'] : 0;
$offset = 314 - round(314 * $pct / 100);

$symptoms_observed = $latest ? array_filter(array_map('trim', explode(',', $latest['symptoms_observed'] ?? ''))) : [];
$recommendations   = $latest ? array_filter(array_map('trim', explode("\n", $latest['recommendations'] ?? ''))) : [];
$medications_raw   = $latest ? array_filter(array_map('trim', explode("\n", $latest['medications'] ?? ''))) : [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>نتائج التشخيص - منصة توعية لمرض الصرع</title>
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
    .circle-progress { transform: rotate(-90deg); }
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
      <a href="assessment.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
        <i data-lucide="clipboard-list" class="w-5 h-5"></i><span>فحص الأعراض</span>
      </a>
      <a href="diagnosis.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
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
        <h1 class="text-2xl font-bold text-gray-800">نتائج التشخيص</h1>
        <p class="text-sm text-gray-500">
          <?= $latest ? 'آخر تحديث: ' . htmlspecialchars($latest['date_ar']) : 'لا توجد تقارير بعد' ?>
        </p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">

      <?php if ($latest): ?>
      
      <?php if ($latest['is_auto']): ?>
      <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-orange-200 rounded-2xl p-6 mb-6 animate-fadeInUp">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center flex-shrink-0">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-800 mb-2">تقييم أولي تلقائي</h3>
            <p class="text-gray-700 text-sm mb-4">هذا تقييم أولي بناءً على الأعراض المُدخلة. للحصول على تشخيص دقيق من طبيب متخصص، يمكنك طلب مراجعة طبية.</p>
            <a href="assessment.php" class="inline-flex items-center gap-2 bg-orange-500 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-orange-600 transition text-sm">
              <i data-lucide="user-check" class="w-4 h-4"></i>
              طلب مراجعة من طبيب متخصص
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8 animate-fadeInUp">
        <div class="flex flex-col md:flex-row items-center gap-8">
          
          <div class="relative w-48 h-48 flex-shrink-0">
            <svg class="w-48 h-48 circle-progress" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="50" fill="none" stroke="#e5e7eb" stroke-width="10"/>
              <circle cx="60" cy="60" r="50" fill="none" stroke="url(#grad)" stroke-width="10"
                stroke-dasharray="314" stroke-dashoffset="<?= $offset ?>" stroke-linecap="round"/>
              <defs>
                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" style="stop-color:#8F00FF"/>
                  <stop offset="100%" style="stop-color:#5500CC"/>
                </linearGradient>
              </defs>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-4xl font-black text-gray-800"><?= $pct ?>%</span>
              <span class="text-sm text-gray-500">احتمالية</span>
            </div>
          </div>

          
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-4 flex-wrap">
              <h2 class="text-2xl font-bold text-gray-800">نتيجة التشخيص</h2>
              <span class="<?= $status_badge[$latest['diagnosis_result']] ?? 'bg-gray-100 text-gray-700' ?> px-4 py-1.5 rounded-full text-sm font-bold">
                <?= $status_map[$latest['diagnosis_result']] ?? htmlspecialchars($latest['diagnosis_result']) ?>
              </span>
            </div>
            <p class="text-gray-600 mb-6 leading-relaxed text-sm">
              <?= nl2br(htmlspecialchars($latest['notes'] ?? 'بناءً على الأعراض المُدخلة وتحليل البيانات، يُنصح بمراجعة الطبيب المختص.')) ?>
            </p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">
                  <?= $latest['is_auto'] ? 'نوع التقييم' : 'الطبيب المعالج' ?>
                </p>
                <p class="font-bold text-gray-800 text-sm">
                  <?php if ($latest['is_auto']): ?>
                    <span class="inline-flex items-center gap-1 text-orange-600">
                      <i data-lucide="cpu" class="w-4 h-4"></i>
                      <?= htmlspecialchars($latest['doctor_name']) ?>
                    </span>
                  <?php else: ?>
                    د. <?= htmlspecialchars($latest['doctor_name']) ?>
                  <?php endif; ?>
                </p>
              </div>
              <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">تاريخ التشخيص</p>
                <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($latest['date_ar']) ?></p>
              </div>
              <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">عدد الأعراض</p>
                <p class="font-bold text-gray-800 text-sm"><?= count($symptoms_observed) ?> أعراض</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp">
          <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-orange-500"></i>
            الأعراض المُسجَّلة
          </h3>
          <?php if (count($symptoms_observed) > 0): ?>
          <div class="space-y-3">
            <?php foreach ($symptoms_observed as $sym): ?>
            <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
              <div class="w-2 h-2 bg-red-500 rounded-full flex-shrink-0"></div>
              <span class="text-gray-800 font-medium text-sm"><?= htmlspecialchars($sym) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-gray-400 text-sm text-center py-4">لا توجد أعراض مسجلة</p>
          <?php endif; ?>
        </div>

        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeInUp">
          <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
            التوصيات
          </h3>
          <?php if (count($recommendations) > 0): ?>
          <div class="space-y-3">
            <?php foreach ($recommendations as $rec): ?>
            <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
              <i data-lucide="arrow-left" class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0"></i>
              <span class="text-gray-800 text-sm"><?= htmlspecialchars($rec) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-gray-400 text-sm text-center py-4">لا توجد توصيات بعد</p>
          <?php endif; ?>
        </div>
      </div>

      
      <?php if (count($medications_raw) > 0): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 animate-fadeInUp">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
          <i data-lucide="pill" class="w-5 h-5 text-purple-600"></i>
          الأدوية الموصى بها
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <?php foreach ($medications_raw as $med): ?>
          <div class="border border-purple-100 rounded-xl p-4 bg-purple-50">
            <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($med) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 mb-8 text-center animate-fadeInUp">
        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="file-x" class="w-10 h-10 text-purple-400"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">لا توجد تقارير بعد</h3>
        <p class="text-gray-500 text-sm mb-6">قم بإجراء فحص الأعراض أولاً ليتمكن الطبيب من إصدار تقرير التشخيص.</p>
        <a href="assessment.php" class="inline-flex items-center gap-2 gradient-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i>
          ابدأ فحص الأعراض
        </a>
      </div>
      <?php endif; ?>

      
      <?php if (count($reports) > 0): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 animate-fadeInUp">
        <h3 class="text-lg font-bold text-gray-800 mb-4">سجل التشخيصات السابقة</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-right py-3 px-4 text-gray-500 font-semibold">التاريخ</th>
                <th class="text-right py-3 px-4 text-gray-500 font-semibold">النسبة</th>
                <th class="text-right py-3 px-4 text-gray-500 font-semibold">الحالة</th>
                <th class="text-right py-3 px-4 text-gray-500 font-semibold">الطبيب</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($reports as $r):
                $pctR = (int)($r['epilepsy_percentage'] ?? 0);
                $pctColor = $pctR >= 66 ? 'text-red-600' : ($pctR >= 41 ? 'text-orange-600' : 'text-green-600');
              ?>
              <tr class="hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800"><?= htmlspecialchars($r['date_ar']) ?></td>
                <td class="py-3 px-4"><span class="<?= $pctColor ?> font-bold"><?= $pctR ?>%</span></td>
                <td class="py-3 px-4">
                  <span class="<?= $status_badge[$r['diagnosis_result']] ?? 'bg-gray-100 text-gray-700' ?> px-2 py-1 rounded text-xs">
                    <?= $status_map[$r['diagnosis_result']] ?? htmlspecialchars($r['diagnosis_result']) ?>
                  </span>
                </td>
                <td class="py-3 px-4 text-gray-600">
                  <?php if ($r['is_auto']): ?>
                    <span class="inline-flex items-center gap-1 text-orange-600">
                      <i data-lucide="cpu" class="w-3 h-3"></i>
                      <?= htmlspecialchars($r['doctor_name']) ?>
                    </span>
                  <?php else: ?>
                    د. <?= htmlspecialchars($r['doctor_name']) ?>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      
      <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl border border-purple-100 p-6 animate-fadeInUp">
        <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
          <i data-lucide="info" class="w-5 h-5 text-purple-600"></i>
          ماذا تعني النتيجة؟
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-green-50 border border-green-200 rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-4 h-4 bg-green-500 rounded-full"></div>
              <span class="font-bold text-green-700">0% - 40%</span>
            </div>
            <p class="text-sm font-semibold text-gray-800 mb-1">سلبي</p>
            <p class="text-xs text-gray-600 leading-relaxed">الأعراض لا تشير بشكل واضح إلى الصرع. يُنصح بالمتابعة الدورية.</p>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-4 h-4 bg-yellow-500 rounded-full"></div>
              <span class="font-bold text-yellow-700">41% - 65%</span>
            </div>
            <p class="text-sm font-semibold text-gray-800 mb-1">مشتبه به</p>
            <p class="text-xs text-gray-600 leading-relaxed">توجد أعراض مشابهة للصرع. يجب إجراء فحوصات إضافية للتأكيد.</p>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-4 h-4 bg-red-500 rounded-full"></div>
              <span class="font-bold text-red-700">66% - 100%</span>
            </div>
            <p class="text-sm font-semibold text-gray-800 mb-1">إيجابي</p>
            <p class="text-xs text-gray-600 leading-relaxed">احتمالية عالية للإصابة بالصرع. يُنصح بمراجعة طبيب أعصاب فوراً.</p>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    lucide.createIcons();

  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
