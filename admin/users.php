<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = sanitize($_POST['name']);
        $email    = sanitize($_POST['email']);
        $phone    = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role     = $_POST['role'];
        $stmt = $conn->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $password, $role);
        $stmt->execute();
        $newId = (int)$conn->insert_id;
        if ($newId) {
            $dash = $role === 'doctor' ? '../doctor/dashboard.php' : ($role === 'admin' ? 'dashboard.php' : '../user/dashboard.php');
            notifyUser($conn, $newId, 'system', 'تم إنشاء حسابك', 'مرحباً بك في المنصة. يمكنك تسجيل الدخول الآن.', $dash);
            if ($role === 'doctor') {
                notifyAdmins($conn, 'system', 'مستخدم جديد (طبيب)', "تم إضافة طبيب: $name", 'users.php?role=doctor', 'new_user');
            }
        }

    } elseif ($action === 'edit') {
        $id     = (int)$_POST['id'];
        $name   = sanitize($_POST['name']);
        $phone  = sanitize($_POST['phone']);
        $role   = $_POST['role'];
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE users SET name=?,phone=?,role=?,status=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $phone, $role, $status, $id);
        $stmt->execute();
        notifyUser($conn, $id, 'system', 'تحديث بيانات الحساب', 'تم تحديث بيانات حسابك من قبل الإدارة.', $role === 'doctor' ? '../doctor/profile.php' : ($role === 'admin' ? 'profile.php' : '../user/profile.php'));
        if ($status === 'suspended') {
            notifyUser($conn, $id, 'system', 'تم إيقاف الحساب', 'تم إيقاف حسابك مؤقتاً. تواصل مع الإدارة للمزيد.', '../login.php');
        }

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role!='admin'");

    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE users SET status=IF(status='active','suspended','active') WHERE id=$id");
        $u = getUserById($conn, $id);
        if ($u) {
            $active = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc()['status'] ?? '';
            $msg = $active === 'active' ? 'تم تفعيل حسابك' : 'تم إيقاف حسابك مؤقتاً';
            notifyUser($conn, $id, 'system', $msg, $msg, $u['role'] === 'doctor' ? '../doctor/dashboard.php' : '../user/dashboard.php');
        }

    } elseif ($action === 'approve_doctor') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE users SET doctor_status='approved', status='active' WHERE id=$id AND role='doctor'");
        notifyUser($conn, $id, 'system', 'تمت الموافقة على حسابك', 'تمت الموافقة على طلب التسجيل كطبيب. يمكنك استخدام المنصة الآن.', '../doctor/dashboard.php');

    } elseif ($action === 'reject_doctor') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE users SET doctor_status='rejected', status='suspended' WHERE id=$id AND role='doctor'");
        notifyUser($conn, $id, 'system', 'تم رفض طلب التسجيل', 'لم تتم الموافقة على طلب التسجيل كطبيب.', '../login.php');
    }

    header('Location: users.php');
    exit;
}

$search        = sanitize($_GET['search'] ?? '');
$filter_role   = sanitize($_GET['role'] ?? '');
$filter_status = sanitize($_GET['status'] ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $like = "%$search%";
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($filter_role) {
    $where .= " AND role=?";
    $params[] = $filter_role;
    $types .= "s";
}
if ($filter_status) {
    $where .= " AND status=?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql  = "SELECT * FROM users $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$role_map    = ['parent' => 'ولي أمر', 'doctor' => 'طبيب', 'admin' => 'مدير'];
$role_color  = ['parent' => 'bg-blue-100 text-blue-700', 'doctor' => 'bg-purple-100 text-purple-700', 'admin' => 'bg-orange-100 text-orange-700'];
$status_map  = ['active' => 'نشط', 'suspended' => 'موقوف'];
$status_color= ['active' => 'bg-green-100 text-green-700', 'suspended' => 'bg-red-100 text-red-700'];
$doctor_status_map   = ['pending' => 'قيد المراجعة', 'approved' => 'موثّق', 'rejected' => 'مرفوض'];
$doctor_status_color = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>المستخدمون - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    .modal-overlay { background: rgba(0,0,0,0.5); }
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
      <a href="users.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>المستخدمون</span></a>
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
        <h1 class="text-2xl font-bold text-gray-800">إدارة المستخدمين</h1>
        <p class="text-sm text-gray-500">إجمالي <?= count($users) ?> مستخدم</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <button onclick="openAddModal()" class="gradient-primary text-white px-5 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:opacity-90 transition-opacity">
          <i data-lucide="user-plus" class="w-4 h-4"></i> إضافة مستخدم
        </button>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">
      
      <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
          <label class="block text-sm font-semibold text-gray-700 mb-2">بحث</label>
          <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute right-3 top-3.5 text-gray-400"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="اسم، بريد، هاتف..." class="w-full border border-gray-200 rounded-xl pr-9 pl-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
          </div>
        </div>
        <div class="min-w-36">
          <label class="block text-sm font-semibold text-gray-700 mb-2">الدور</label>
          <select name="role" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
            <option value="">الكل</option>
            <option value="parent" <?= $filter_role==='parent'?'selected':'' ?>>ولي أمر</option>
            <option value="doctor" <?= $filter_role==='doctor'?'selected':'' ?>>طبيب</option>
            <option value="admin"  <?= $filter_role==='admin' ?'selected':'' ?>>مدير</option>
          </select>
        </div>
        <div class="min-w-36">
          <label class="block text-sm font-semibold text-gray-700 mb-2">الحالة</label>
          <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
            <option value="">الكل</option>
            <option value="active"    <?= $filter_status==='active'   ?'selected':'' ?>>نشط</option>
            <option value="suspended" <?= $filter_status==='suspended'?'selected':'' ?>>موقوف</option>
          </select>
        </div>
        <button type="submit" class="gradient-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2">
          <i data-lucide="filter" class="w-4 h-4"></i> تصفية
        </button>
        <a href="users.php" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 flex items-center gap-2">
          <i data-lucide="x" class="w-4 h-4"></i> إعادة تعيين
        </a>
      </form>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">المستخدم</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الهاتف</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الدور</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">التحقق</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">تاريخ التسجيل</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($users as $i => $u): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-gray-500 text-sm"><?= $i + 1 ?></td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                      <?= mb_substr($u['name'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div>
                      <p class="font-semibold text-gray-800"><?= htmlspecialchars($u['name']) ?></p>
                      <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
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
                <!-- عمود التحقق -->
                <td class="px-6 py-4">
                  <?php if ($u['role'] === 'doctor' && !empty($u['doctor_status'])): ?>
                    <span class="<?= $doctor_status_color[$u['doctor_status']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full font-semibold">
                      <?= $doctor_status_map[$u['doctor_status']] ?? $u['doctor_status'] ?>
                    </span>
                  <?php else: ?>
                    <span class="text-gray-300 text-xs">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-gray-500 text-sm"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2 flex-wrap">
                    <button onclick='openEditModal(<?= json_encode($u) ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="تعديل">
                      <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </button>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <?php if ($u['role'] === 'doctor' && !empty($u['syndicate_card'])): ?>
                    <button onclick="viewSyndicateCard('<?= htmlspecialchars($u['syndicate_card'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')"
                            class="p-2 text-teal-600 hover:bg-teal-50 rounded-lg transition-colors" title="مشاهدة كارنيه النقابة">
                      <i data-lucide="id-card" class="w-4 h-4"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($u['role'] === 'doctor' && in_array($u['doctor_status'] ?? '', ['pending', 'rejected'])): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="approve_doctor">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="قبول الطبيب">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($u['role'] === 'doctor' && in_array($u['doctor_status'] ?? '', ['pending', 'approved'])): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('هل تريد رفض هذا الطبيب؟')">
                      <input type="hidden" name="action" value="reject_doctor">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="رفض الطبيب">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                    <a href="view_as.php?id=<?= $u['id'] ?>" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="عرض كـ <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>">
                      <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="p-2 <?= $u['status']==='active' ? 'text-orange-500 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50' ?> rounded-lg transition-colors" title="<?= $u['status']==='active' ? 'تعليق' : 'تفعيل' ?>">
                        <i data-lucide="<?= $u['status']==='active' ? 'pause-circle' : 'play-circle' ?>" class="w-4 h-4"></i>
                      </button>
                    </form>
                    <button onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="حذف">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($users)): ?>
              <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">لا توجد نتائج</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  
  <div id="addModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">إضافة مستخدم جديد</h3>
        <button onclick="closeAddModal()" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
            <input type="text" name="name" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
            <input type="email" name="email" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
            <input type="tel" name="phone" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور</label>
            <input type="password" name="password" required minlength="6" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الدور</label>
            <select name="role" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              <option value="parent">ولي أمر</option>
              <option value="doctor">طبيب</option>
              <option value="admin">مدير</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button type="submit" class="flex-1 gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90">إضافة</button>
          <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="editModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">تعديل المستخدم</h3>
        <button onclick="closeEditModal()" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
            <input type="text" name="name" id="edit_name" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
            <input type="email" id="edit_email" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500" disabled>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
            <input type="tel" name="phone" id="edit_phone" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الدور</label>
            <select name="role" id="edit_role" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              <option value="parent">ولي أمر</option>
              <option value="doctor">طبيب</option>
              <option value="admin">مدير</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الحالة</label>
            <select name="status" id="edit_status" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              <option value="active">نشط</option>
              <option value="suspended">موقوف</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button type="submit" class="flex-1 gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90">حفظ التغييرات</button>
          <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="deleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center">
      <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i data-lucide="trash-2" class="w-8 h-8 text-red-500"></i>
      </div>
      <h3 class="text-xl font-bold text-gray-800 mb-2">تأكيد الحذف</h3>
      <p class="text-gray-500 mb-6">هل أنت متأكد من حذف المستخدم <strong id="delete_name"></strong>؟ لا يمكن التراجع عن هذا الإجراء.</p>
      <form method="POST" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <div class="flex gap-3">
          <button type="submit" class="flex-1 bg-red-500 text-white py-3 rounded-xl font-semibold hover:bg-red-600">حذف</button>
          <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal مشاهدة كارنيه النقابة -->
  <div id="syndicateModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          <i data-lucide="id-card" class="w-6 h-6 text-teal-600"></i>
          <span id="cardDoctorName">كارنيه النقابة</span>
        </h3>
        <button onclick="closeSyndicateModal()" class="p-2 hover:bg-gray-100 rounded-lg">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="flex justify-center bg-gray-50 rounded-xl p-4 min-h-48">
        <img id="cardImage" src="" alt="كارنيه النقابة" class="max-h-80 object-contain rounded-lg shadow">
      </div>
      <p class="text-xs text-gray-400 text-center mt-3">صورة كارنيه النقابة الطبية المرفوعة عند التسجيل</p>
      <div class="mt-4 flex justify-end">
        <button onclick="closeSyndicateModal()" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-xl font-semibold hover:bg-gray-200">إغلاق</button>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    function openAddModal() {
      document.getElementById('addModal').classList.remove('hidden');
      document.getElementById('addModal').classList.add('flex');
    }
    function closeAddModal() {
      document.getElementById('addModal').classList.add('hidden');
      document.getElementById('addModal').classList.remove('flex');
    }
    function openEditModal(u) {
      document.getElementById('edit_id').value    = u.id;
      document.getElementById('edit_name').value  = u.name;
      document.getElementById('edit_email').value = u.email;
      document.getElementById('edit_phone').value = u.phone || '';
      document.getElementById('edit_role').value  = u.role;
      document.getElementById('edit_status').value= u.status;
      document.getElementById('editModal').classList.remove('hidden');
      document.getElementById('editModal').classList.add('flex');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
      document.getElementById('editModal').classList.remove('flex');
    }
    function confirmDelete(id, name) {
      document.getElementById('delete_id').value   = id;
      document.getElementById('delete_name').textContent = name;
      document.getElementById('deleteModal').classList.remove('hidden');
      document.getElementById('deleteModal').classList.add('flex');
    }
    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
      document.getElementById('deleteModal').classList.remove('flex');
    }
    function viewSyndicateCard(filename, doctorName) {
      document.getElementById('cardDoctorName').textContent = 'كارنيه د. ' + doctorName;
      document.getElementById('cardImage').src = '../' + filename;
      document.getElementById('syndicateModal').classList.remove('hidden');
      document.getElementById('syndicateModal').classList.add('flex');
    }
    function closeSyndicateModal() {
      document.getElementById('syndicateModal').classList.add('hidden');
      document.getElementById('syndicateModal').classList.remove('flex');
    }
    ['addModal','editModal','deleteModal','syndicateModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.add('hidden');
          this.classList.remove('flex');
        }
      });
    });
  </script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
