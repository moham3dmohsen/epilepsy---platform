<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $name  = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);

        // رفع صورة البروفايل
        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $pic = uploadImage($_FILES['profile_picture'], 'profiles');
            if ($pic) {
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
            $success = 'تم تحديث الملف الشخصي بنجاح';
        }
    }

    if (isset($_POST['change_password'])) {
        $old     = $_POST['old_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt2 = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $row = $stmt2->get_result()->fetch_assoc();

        if (!password_verify($old, $row['password'])) {
            $error = 'كلمة المرور الحالية غير صحيحة';
        } elseif ($new !== $confirm) {
            $error = 'كلمتا المرور الجديدة غير متطابقتين';
        } elseif (strlen($new) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $s    = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $s->bind_param("si", $hash, $user['id']);
            $s->execute();
            $success = 'تم تغيير كلمة المرور بنجاح';
        }
    }
}

$stmt3 = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt3->bind_param("i", $user['id']);
$stmt3->execute();
$userData = $stmt3->get_result()->fetch_assoc();
$profilePic = !empty($userData['profile_picture']) ? '../' . htmlspecialchars($userData['profile_picture']) : null;

$total_users    = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'")->fetch_assoc()['cnt'];
$total_reports  = $conn->query("SELECT COUNT(*) as cnt FROM reports")->fetch_assoc()['cnt'];
$total_videos   = $conn->query("SELECT COUNT(*) as cnt FROM videos")->fetch_assoc()['cnt'];
$total_articles = $conn->query("SELECT COUNT(*) as cnt FROM articles")->fetch_assoc()['cnt'];
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
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
      <a href="profile.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
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
        <p class="text-sm text-gray-500">إدارة بياناتك الشخصية</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8 max-w-4xl mx-auto">

      
      <?php if ($success): ?>
      <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <div class="flex items-center gap-6 mb-8">
          <div class="relative group cursor-pointer" onclick="document.getElementById('picInputAdmin').click()">
            <?php if ($profilePic): ?>
              <img src="<?= $profilePic ?>" alt="صورة البروفايل" class="w-20 h-20 rounded-2xl object-cover shadow-lg border-4 border-purple-100">
            <?php else: ?>
              <div class="w-20 h-20 rounded-2xl gradient-primary flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                <?= mb_substr($userData['name'], 0, 1, 'UTF-8') ?>
              </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <i data-lucide="camera" class="w-6 h-6 text-white"></i>
            </div>
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($userData['name']) ?></h2>
            <p class="text-gray-500"><?= htmlspecialchars($userData['email']) ?></p>
            <span class="inline-flex items-center gap-1 mt-1 bg-purple-100 text-purple-700 text-xs px-3 py-1 rounded-full font-semibold">
              <i data-lucide="shield-check" class="w-3 h-3"></i> مدير النظام
            </span>
          </div>
        </div>

        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-xl">
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-600"><?= $total_users ?></p>
            <p class="text-xs text-gray-500 mt-1">مستخدم</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-600"><?= $total_reports ?></p>
            <p class="text-xs text-gray-500 mt-1">تقرير</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-600"><?= $total_videos ?></p>
            <p class="text-xs text-gray-500 mt-1">فيديو</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-600"><?= $total_articles ?></p>
            <p class="text-xs text-gray-500 mt-1">مقال</p>
          </div>
        </div>

        
        <h3 class="text-lg font-bold text-gray-800 mb-5">تعديل البيانات الشخصية</h3>
        <form method="POST" enctype="multipart/form-data">
          <input type="file" name="profile_picture" id="picInputAdmin" accept="image/*" class="hidden" onchange="previewProfilePic(this)">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
              <input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
              <input type="email" value="<?= htmlspecialchars($userData['email']) ?>"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500" disabled>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الانضمام</label>
              <input type="text" value="<?= date('d F Y', strtotime($userData['created_at'])) ?>"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500" disabled>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="update_profile" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2">
              <i data-lucide="save" class="w-4 h-4"></i> حفظ التغييرات
            </button>
          </div>
        </form>
      </div>

      
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
          <i data-lucide="lock" class="w-5 h-5 text-purple-600"></i>
          تغيير كلمة المرور
        </h3>
        <form method="POST">
          <div class="space-y-4 mb-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية</label>
              <input type="password" name="old_password" required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" required minlength="6"
                  class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" required minlength="6"
                  class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="change_password" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2">
              <i data-lucide="shield-check" class="w-4 h-4"></i> تغيير كلمة المرور
            </button>
          </div>
        </form>
      </div>

    </div>
  </main>

  <script>
    lucide.createIcons();

    function previewProfilePic(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const container = document.querySelector('.relative.group');
          container.innerHTML = `
            <img src="${e.target.result}" alt="معاينة" class="w-20 h-20 rounded-2xl object-cover shadow-lg border-4 border-purple-100">
            <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <i data-lucide="camera" class="w-6 h-6 text-white"></i>
            </div>`;
          lucide.createIcons();
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
