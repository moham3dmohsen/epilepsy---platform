<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
if (empty($user['name'])) { $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']); if ($r && $row = $r->fetch_assoc()) { $user['name'] = $row['name']; $user['profile_picture'] = $row['profile_picture']; } }
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_parent'])) {
        $name  = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        // رفع صورة البروفايل
        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $pic = uploadImage($_FILES['profile_picture'], 'profiles');
            if ($pic) {
                // حذف الصورة القديمة
                $old_pic = $conn->query("SELECT profile_picture FROM users WHERE id={$user['id']}")->fetch_assoc()['profile_picture'] ?? '';
                if ($old_pic && file_exists(__DIR__ . '/../' . $old_pic)) {
                    unlink(__DIR__ . '/../' . $old_pic);
                }
                $stmt_pic = $conn->prepare("UPDATE users SET profile_picture=? WHERE id=?");
                $stmt_pic->bind_param("si", $pic, $user['id']);
                $stmt_pic->execute();
                $_SESSION['profile_picture'] = $pic;
            } else {
                $error = 'فشل رفع الصورة. تأكد أن الملف صورة صحيحة (JPG, PNG, WEBP)';
            }
        }

        if (empty($error)) {
            $stmt  = $conn->prepare("UPDATE users SET name=?,phone=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $phone, $user['id']);
            $stmt->execute();
            $_SESSION['name'] = $name;
            $success = 'تم تحديث بيانات ولي الأمر بنجاح';
        }
    }

    if (isset($_POST['update_child'])) {
        $cname      = sanitize($_POST['child_name'] ?? '');
        $age        = (int)($_POST['age'] ?? 0);
        $gender     = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : 'male';
        $weight     = (float)($_POST['weight'] ?? 0);
        $height     = (float)($_POST['height'] ?? 0);
        $birth_date = $_POST['birth_date'] ?? '';

        $stmt2 = $conn->prepare("SELECT id FROM children WHERE parent_id=? LIMIT 1");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $ch = $stmt2->get_result()->fetch_assoc();

        if ($ch) {
            $u = $conn->prepare("UPDATE children SET name=?,age=?,gender=?,weight=?,height=?,birth_date=? WHERE id=?");
            $u->bind_param("sisddsi", $cname, $age, $gender, $weight, $height, $birth_date, $ch['id']);
            $u->execute();
        } else {
            $i = $conn->prepare("INSERT INTO children (parent_id,name,age,gender,weight,height,birth_date) VALUES (?,?,?,?,?,?,?)");
            $i->bind_param("iissdds", $user['id'], $cname, $age, $gender, $weight, $height, $birth_date);
            $i->execute();
        }
        $success = 'تم تحديث بيانات الطفل بنجاح';
    }

    if (isset($_POST['change_password'])) {
        $old     = $_POST['old_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt3 = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt3->bind_param("i", $user['id']);
        $stmt3->execute();
        $row = $stmt3->get_result()->fetch_assoc();

        if (!password_verify($old, $row['password'])) {
            $error = 'كلمة المرور الحالية غير صحيحة';
        } elseif ($new !== $confirm) {
            $error = 'كلمتا المرور الجديدتان غير متطابقتين';
        } elseif (strlen($new) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt4  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt4->bind_param("si", $hashed, $user['id']);
            $stmt4->execute();
            $success = 'تم تغيير كلمة المرور بنجاح';
        }
    }
}

$stmt5 = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt5->bind_param("i", $user['id']);
$stmt5->execute();
$userData = $stmt5->get_result()->fetch_assoc();
$profilePic = !empty($userData['profile_picture']) ? '../' . htmlspecialchars($userData['profile_picture']) : null;

$child = null;
$stmt6 = $conn->prepare("SELECT * FROM children WHERE parent_id=? LIMIT 1");
$stmt6->bind_param("i", $user['id']);
$stmt6->execute();
$child = $stmt6->get_result()->fetch_assoc();

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الملف الشخصي - منصة توعية لمرض الصرع</title>
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
      <a href="profile.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
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
        <h1 class="text-2xl font-bold text-gray-800">الملف الشخصي</h1>
        <p class="text-sm text-gray-500">إدارة بياناتك الشخصية وبيانات طفلك</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($userData['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8 max-w-4xl mx-auto">

      
      <?php if ($success): ?>
      <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center gap-3 animate-fadeInUp">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="font-medium"><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl flex items-center gap-3 animate-fadeInUp">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="font-medium"><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <!-- صورة البروفايل -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 animate-fadeInUp">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
            <i data-lucide="camera" class="w-6 h-6 text-white"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">صورة الملف الشخصي</h2>
            <p class="text-sm text-gray-500">اضغط على الصورة لتغييرها</p>
          </div>
        </div>
        <form method="POST" action="profile.php" enctype="multipart/form-data">
          <div class="flex items-center gap-6">
            <div class="relative group cursor-pointer" onclick="document.getElementById('picInputUser').click()">
              <?php if ($profilePic): ?>
                <img src="<?= $profilePic ?>" alt="صورة البروفايل"
                     class="w-24 h-24 rounded-2xl object-cover shadow-md border-4 border-purple-100">
              <?php else: ?>
                <div class="w-24 h-24 rounded-2xl gradient-primary flex items-center justify-center text-white text-3xl font-bold shadow-md">
                  <?= mb_substr($userData['name'], 0, 1, 'UTF-8') ?>
                </div>
              <?php endif; ?>
              <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <i data-lucide="camera" class="w-7 h-7 text-white"></i>
              </div>
            </div>
            <div class="flex-1">
              <input type="file" name="profile_picture" id="picInputUser" accept="image/*" class="hidden" onchange="previewProfilePic(this,'picPreviewUser','picInitialUser')">
              <p class="text-sm text-gray-600 font-medium mb-1">اختر صورة جديدة</p>
              <p class="text-xs text-gray-400">JPG, PNG, WEBP — حجم أقصى 5MB</p>
              <button type="button" onclick="document.getElementById('picInputUser').click()"
                      class="mt-3 inline-flex items-center gap-2 bg-purple-50 text-purple-700 border border-purple-200 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-purple-100 transition">
                <i data-lucide="upload" class="w-4 h-4"></i> رفع صورة
              </button>
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" name="update_parent" class="gradient-primary text-white px-6 py-2.5 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2 text-sm">
              <i data-lucide="save" class="w-4 h-4"></i> حفظ الصورة
            </button>
          </div>
        </form>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 animate-fadeInUp">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
            <i data-lucide="user" class="w-6 h-6 text-white"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">بيانات ولي الأمر</h2>
            <p class="text-sm text-gray-500">تحديث معلوماتك الشخصية</p>
          </div>
        </div>
        <form method="POST" action="profile.php">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
              <input type="text" name="name" value="<?= htmlspecialchars($userData['name'] ?? '') ?>"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800" required>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
              <input type="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
              <p class="text-xs text-gray-400 mt-1">لا يمكن تغيير البريد الإلكتروني</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                     placeholder="05xxxxxxxx"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ التسجيل</label>
              <input type="text" value="<?= !empty($userData['created_at']) ? date('d/m/Y', strtotime($userData['created_at'])) : '—' ?>"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="update_parent" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              <i data-lucide="save" class="w-4 h-4"></i>
              حفظ بيانات ولي الأمر
            </button>
          </div>
        </form>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 animate-fadeInUp" style="animation-delay:0.1s;opacity:0">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
            <i data-lucide="baby" class="w-6 h-6 text-white"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">بيانات الطفل</h2>
            <p class="text-sm text-gray-500">تحديث معلومات طفلك</p>
          </div>
        </div>
        <form method="POST" action="profile.php">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">اسم الطفل</label>
              <input type="text" name="child_name" value="<?= htmlspecialchars($child['name'] ?? '') ?>"
                     placeholder="أدخل اسم الطفل"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800" required>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">العمر (بالسنوات)</label>
              <input type="number" name="age" min="0" max="18" value="<?= htmlspecialchars($child['age'] ?? '') ?>"
                     placeholder="العمر"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الجنس</label>
              <select name="gender" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
                <option value="male"   <?= (($child['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>ذكر</option>
                <option value="female" <?= (($child['gender'] ?? '') === 'female') ? 'selected' : '' ?>>أنثى</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الوزن (كجم)</label>
              <input type="number" name="weight" step="0.1" min="0" value="<?= htmlspecialchars($child['weight'] ?? '') ?>"
                     placeholder="الوزن"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الطول (سم)</label>
              <input type="number" name="height" step="0.1" min="0" value="<?= htmlspecialchars($child['height'] ?? '') ?>"
                     placeholder="الطول"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد</label>
              <input type="date" name="birth_date" value="<?= htmlspecialchars($child['birth_date'] ?? '') ?>"
                     class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800">
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="update_child" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              <i data-lucide="save" class="w-4 h-4"></i>
              حفظ بيانات الطفل
            </button>
          </div>
        </form>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 animate-fadeInUp" style="animation-delay:0.2s;opacity:0">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center">
            <i data-lucide="lock" class="w-6 h-6 text-white"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">تغيير كلمة المرور</h2>
            <p class="text-sm text-gray-500">تأكد من استخدام كلمة مرور قوية</p>
          </div>
        </div>
        <form method="POST" action="profile.php">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية</label>
              <div class="relative">
                <input type="password" name="old_password" id="oldPass"
                       placeholder="أدخل كلمة المرور الحالية"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 pl-12" required>
                <button type="button" onclick="togglePass('oldPass','eyeOld')" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i id="eyeOld" data-lucide="eye" class="w-4 h-4"></i>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة</label>
              <div class="relative">
                <input type="password" name="new_password" id="newPass"
                       placeholder="6 أحرف على الأقل"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 pl-12" required>
                <button type="button" onclick="togglePass('newPass','eyeNew')" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i id="eyeNew" data-lucide="eye" class="w-4 h-4"></i>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور الجديدة</label>
              <div class="relative">
                <input type="password" name="confirm_password" id="confirmPass"
                       placeholder="أعد إدخال كلمة المرور"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 pl-12" required>
                <button type="button" onclick="togglePass('confirmPass','eyeConfirm')" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i id="eyeConfirm" data-lucide="eye" class="w-4 h-4"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="change_password" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2">
              <i data-lucide="shield-check" class="w-4 h-4"></i>
              تغيير كلمة المرور
            </button>
          </div>
        </form>
      </div>

    </div>
  </main>

  <script>
    lucide.createIcons();

    function previewProfilePic(input, previewId, initialId) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          // ابحث عن الصورة أو الـ div الأولي وبدّله
          const container = input.closest('form').querySelector('.relative.group');
          container.innerHTML = `
            <img src="${e.target.result}" alt="معاينة" class="w-24 h-24 rounded-2xl object-cover shadow-md border-4 border-purple-100">
            <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <i data-lucide="camera" class="w-7 h-7 text-white"></i>
            </div>`;
          lucide.createIcons();
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function togglePass(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon  = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      lucide.createIcons();
    }
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
