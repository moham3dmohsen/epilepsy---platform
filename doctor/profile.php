<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('doctor');
$user = refreshCurrentUser($conn);
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['update_profile'])) {
        $name  = sanitize($_POST['name']  ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

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

        // تحديث الاسم والهاتف فقط لو موجودين في الـ POST
        if (empty($error) && !empty($name)) {
            $stmt = $conn->prepare("UPDATE users SET name=?,phone=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $phone, $user['id']);
            $stmt->execute();
            $_SESSION['name'] = $name;
        }
        $success = 'تم تحديث الملف الشخصي بنجاح';
    }
    if (isset($_POST['change_password'])) {
        $old=$_POST['old_password']; $new=$_POST['new_password']; $confirm=$_POST['confirm_password'];
        $stmt2=$conn->prepare("SELECT password FROM users WHERE id=?"); $stmt2->bind_param("i",$user['id']); $stmt2->execute();
        $row=$stmt2->get_result()->fetch_assoc();
        if (!password_verify($old,$row['password'])) $error='كلمة المرور الحالية غير صحيحة';
        elseif ($new!==$confirm) $error='كلمتا المرور غير متطابقتين';
        elseif (strlen($new)<6) $error='كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        else { $h=password_hash($new,PASSWORD_DEFAULT); $s=$conn->prepare("UPDATE users SET password=? WHERE id=?"); $s->bind_param("si",$h,$user['id']); $s->execute(); $success='تم تغيير كلمة المرور بنجاح'; }
    }
}
$stmt3=$conn->prepare("SELECT * FROM users WHERE id=?"); $stmt3->bind_param("i",$user['id']); $stmt3->execute();
$userData=$stmt3->get_result()->fetch_assoc();
$profilePic = !empty($userData['profile_picture']) ? '../' . htmlspecialchars($userData['profile_picture']) : null;
$unread=$conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id={$user['id']} AND is_read=0")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الملف الشخصي - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>* { font-family: 'Cairo', sans-serif; } .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); } .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); } .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }</style>
</head>
<body class="bg-gray-50">
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20"><div class="flex items-center gap-3"><img src="../assets/img/logoDark.png" class="h-10 w-auto"><span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span></div></div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="patients.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>مرضاي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="file-text" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="messages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="message-circle" class="w-5 h-5"></i><span>الرسائل</span></a>
      <a href="profile.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
    </nav>
    <div class="p-4"><a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-red-500/30 hover:text-white transition-all"><i data-lucide="log-out" class="w-5 h-5"></i><span>تسجيل الخروج</span></a></div>
  </aside>
  <main class="mr-64 min-h-screen">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div><h1 class="text-2xl font-bold text-gray-800">الملف الشخصي</h1><p class="text-sm text-gray-500">إدارة بياناتك الشخصية</p></div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>
    <div class="p-8 max-w-3xl mx-auto">
      <?php if ($success): ?><div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5"></i><span><?= htmlspecialchars($success) ?></span></div><?php endif; ?>
      <?php if ($error): ?><div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl flex items-center gap-3"><i data-lucide="alert-circle" class="w-5 h-5"></i><span><?= htmlspecialchars($error) ?></span></div><?php endif; ?>

      <!-- صورة البروفايل -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
          <i data-lucide="camera" class="w-5 h-5 text-purple-600"></i> صورة الملف الشخصي
        </h2>
        <form method="POST" enctype="multipart/form-data">
          <div class="flex items-center gap-6">
            <div class="relative group cursor-pointer" onclick="document.getElementById('picInputDoc').click()">
              <?php if ($profilePic): ?>
                <img src="<?= $profilePic ?>" alt="صورة البروفايل" class="w-24 h-24 rounded-2xl object-cover shadow-md border-4 border-purple-100">
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
              <input type="file" name="profile_picture" id="picInputDoc" accept="image/*" class="hidden" onchange="previewProfilePic(this)">
              <p class="text-sm text-gray-600 font-medium mb-1">اختر صورة جديدة</p>
              <p class="text-xs text-gray-400">JPG, PNG, WEBP — حجم أقصى 5MB</p>
              <button type="button" onclick="document.getElementById('picInputDoc').click()"
                      class="mt-3 inline-flex items-center gap-2 bg-purple-50 text-purple-700 border border-purple-200 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-purple-100 transition">
                <i data-lucide="upload" class="w-4 h-4"></i> رفع صورة
              </button>
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" name="update_profile" class="gradient-primary text-white px-6 py-2.5 rounded-xl font-semibold hover:opacity-90 transition flex items-center gap-2 text-sm">
              <i data-lucide="save" class="w-4 h-4"></i> حفظ الصورة
            </button>
          </div>
        </form>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">البيانات الشخصية</h2>
        <form method="POST" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label><input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label><input type="email" value="<?= htmlspecialchars($userData['email']) ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500" disabled></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label><input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400"></div>
          </div>
          <div class="flex justify-end"><button type="submit" name="update_profile" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> حفظ التغييرات</button></div>
        </form>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">تغيير كلمة المرور</h2>
        <form method="POST">
          <div class="space-y-4 mb-6">
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية</label><input type="password" name="old_password" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة</label><input type="password" name="new_password" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور</label><input type="password" name="confirm_password" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400" required></div>
          </div>
          <div class="flex justify-end"><button type="submit" name="change_password" class="gradient-primary text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4"></i> تغيير كلمة المرور</button></div>
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
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
