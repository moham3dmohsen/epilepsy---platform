<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('doctor');
$user = refreshCurrentUser($conn);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_id = (int)$_POST['assessment_id'];
    $patient_id = (int)$_POST['patient_id'];
    $diagnosis_result = in_array($_POST['diagnosis_result'], ['positive','negative','suspected']) ? $_POST['diagnosis_result'] : 'suspected';
    $epilepsy_percentage = min(100, max(0, (int)$_POST['epilepsy_percentage']));
    $symptoms_observed = sanitize($_POST['symptoms_observed'] ?? '');
    $recommendations = sanitize($_POST['recommendations'] ?? '');
    $medications = sanitize($_POST['medications'] ?? '');

    $ins = $conn->prepare("INSERT INTO reports (assessment_id, doctor_id, patient_id, diagnosis_result, epilepsy_percentage, symptoms_observed, recommendations, medications) VALUES (?,?,?,?,?,?,?,?)");
    $ins->bind_param("iiisisss", $assessment_id, $user['id'], $patient_id, $diagnosis_result, $epilepsy_percentage, $symptoms_observed, $recommendations, $medications);
    if ($ins->execute()) {
        $conn->query("UPDATE assessments SET status='reviewed' WHERE id=$assessment_id");
        addNotification($conn, $patient_id, 'report', 'تقرير طبي جديد', 'تم إرسال تقرير طبي جديد من د. ' . $user['name'] . '. اضغط لعرض التفاصيل.', '../user/diagnosis.php');
        if ($diagnosis_result === 'positive') {
            notifyAdmins($conn, 'assessment', 'تقرير إيجابي', 'تقرير طبي إيجابي من د. ' . $user['name'], 'reports.php', 'new_report');
        }
        $success = 'تم إرسال التقرير بنجاح';
    } else {
        $error = 'حدث خطأ أثناء الحفظ';
    }
}

$pending = $conn->query("SELECT a.*, u.name as parent_name, c.name as child_name, c.age FROM assessments a JOIN users u ON a.parent_id=u.id JOIN children c ON a.child_id=c.id WHERE a.status='pending' ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$reports = $conn->query("SELECT r.*, u.name as patient_name, c.name as child_name, DATE_FORMAT(r.created_at,'%d/%m/%Y') as date_f FROM reports r JOIN users u ON r.patient_id=u.id JOIN children c ON c.parent_id=u.id WHERE r.doctor_id={$user['id']} ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$status_map = ['positive'=>'إيجابي','negative'=>'سلبي','suspected'=>'مشتبه به'];
$status_color = ['positive'=>'bg-red-100 text-red-700','negative'=>'bg-green-100 text-green-700','suspected'=>'bg-orange-100 text-orange-700'];
$unread = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id={$user['id']} AND is_read=0")->fetch_assoc()['cnt'];
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
      <a href="patients.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>مرضاي</span></a>
      <a href="reports.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="file-text" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="messages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="message-circle" class="w-5 h-5"></i><span>الرسائل</span></a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
    </nav>
    <div class="p-4"><a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all"><i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span></a></div>
  </aside>
  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">التقارير</h1>
        <p class="text-sm text-gray-500">إدارة تقارير التشخيص</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php if (count($pending) > 0): ?>
        <button onclick="document.getElementById('pendingModal').classList.remove('hidden')" class="gradient-primary text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 hover:opacity-90">
          <i data-lucide="plus" class="w-4 h-4"></i> تقرير جديد (<?= count($pending) ?>)
        </button>
        <?php endif; ?>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>
    <div class="p-8">
      <?php if ($success): ?>
      <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i><span><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif; ?>

      
      <?php if (count($pending) > 0): ?>
      <div class="bg-orange-50 border border-orange-200 rounded-2xl p-6 mb-6">
        <h3 class="font-bold text-orange-800 mb-4 flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-5 h-5"></i>
          فحوصات تنتظر التقرير (<?= count($pending) ?>)
        </h3>
        <div class="space-y-3">
          <?php foreach ($pending as $a): ?>
          <div class="bg-white rounded-xl p-4 flex items-center justify-between">
            <div>
              <p class="font-semibold text-gray-800"><?= htmlspecialchars($a['child_name']) ?> - <?= $a['age'] ?> سنوات</p>
              <p class="text-sm text-gray-500">ولي الأمر: <?= htmlspecialchars($a['parent_name']) ?> | <?= date('d/m/Y', strtotime($a['created_at'])) ?></p>
              <p class="text-xs text-gray-400 mt-1">الأعراض: <?= htmlspecialchars(mb_substr($a['symptoms'],0,80,'UTF-8')) ?>...</p>
            </div>
            <button onclick="openReportForm(<?= $a['id'] ?>, <?= $a['parent_id'] ?>, '<?= htmlspecialchars($a['child_name']) ?>')" class="gradient-primary text-white px-4 py-2 rounded-xl text-sm font-semibold hover:opacity-90 flex items-center gap-2">
              <i data-lucide="send" class="w-4 h-4"></i> إرسال تقرير
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
          <h2 class="text-lg font-bold text-gray-800">التقارير المرسلة</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">اسم الطفل</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">التشخيص</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">نسبة الصرع</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">التاريخ</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500">الحالة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($reports as $r): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($r['child_name']) ?></td>
                <td class="px-6 py-4"><span class="<?= $status_color[$r['diagnosis_result']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full"><?= $status_map[$r['diagnosis_result']] ?? $r['diagnosis_result'] ?></span></td>
                <td class="px-6 py-4 font-semibold text-gray-800"><?= $r['epilepsy_percentage'] ?>%</td>
                <td class="px-6 py-4 text-gray-500 text-sm"><?= $r['date_f'] ?></td>
                <td class="px-6 py-4"><span class="<?= $r['status']==='read' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?> text-xs px-2 py-1 rounded-full"><?= $r['status']==='read' ? 'مقروء' : 'مرسل' ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($reports)): ?>
              <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">لا توجد تقارير بعد</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  
  <div id="reportModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
        <h3 class="text-lg font-bold text-gray-800">إرسال تقرير طبي</h3>
        <button onclick="closeReportModal()" class="p-2 hover:bg-gray-100 rounded-xl"><i data-lucide="x" class="w-5 h-5 text-gray-500"></i></button>
      </div>
      <form method="POST" action="reports.php" class="p-6 space-y-4">
        <input type="hidden" name="assessment_id" id="modal_assessment_id">
        <input type="hidden" name="patient_id" id="modal_patient_id">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">المريض</label>
          <input type="text" id="modal_patient_name" readonly class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-700">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">نتيجة التشخيص</label>
          <select name="diagnosis_result" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
            <option value="positive">إيجابي - مصاب</option>
            <option value="suspected">مشتبه به</option>
            <option value="negative">سلبي - سليم</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">نسبة احتمالية الصرع: <span id="pct_val">50</span>%</label>
          <input type="range" name="epilepsy_percentage" min="0" max="100" value="50" class="w-full accent-purple-600" oninput="document.getElementById('pct_val').textContent=this.value">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">الأعراض الملاحظة</label>
          <textarea name="symptoms_observed" rows="3" placeholder="اكتب الأعراض..." class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"></textarea>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">التوصيات الطبية</label>
          <textarea name="recommendations" rows="3" placeholder="التوصيات والإرشادات..." class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"></textarea>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">الأدوية الموصوفة</label>
          <textarea name="medications" rows="2" placeholder="مثال: فالبروات الصوديوم 200mg" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"></textarea>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeReportModal()" class="flex-1 border border-gray-200 rounded-xl py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">إلغاء</button>
          <button type="submit" class="flex-1 gradient-primary text-white rounded-xl py-3 text-sm font-semibold hover:opacity-90 flex items-center justify-center gap-2">
            <i data-lucide="send" class="w-4 h-4"></i> إرسال التقرير
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    lucide.createIcons();
    function openReportForm(assessmentId, patientId, childName) {
      document.getElementById('modal_assessment_id').value = assessmentId;
      document.getElementById('modal_patient_id').value = patientId;
      document.getElementById('modal_patient_name').value = childName;
      const modal = document.getElementById('reportModal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      lucide.createIcons();
    }
    function closeReportModal() {
      document.getElementById('reportModal').classList.add('hidden');
      document.getElementById('reportModal').classList.remove('flex');
    }
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
