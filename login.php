<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') redirect('/admin/dashboard.php');
    elseif ($role === 'doctor') redirect('/doctor/dashboard.php');
    else redirect('/user/dashboard.php');
}

$error = '';
$success = '';
$registered_email = '';

if (!empty($_GET['timeout'])) {
    $error = 'انتهت مدة الجلسة بسبب عدم النشاط. يرجى تسجيل الدخول مرة أخرى.';
}

// Check if user just registered
if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
    if (isset($_SESSION['doctor_pending']) && $_SESSION['doctor_pending']) {
        $success = 'تم إنشاء حسابك بنجاح! حسابك قيد المراجعة من قِبل الإدارة للتحقق من كارنيه النقابة. سيتم إشعارك عند الموافقة.';
        unset($_SESSION['doctor_pending']);
    } else {
        $success = 'تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.';
    }
    $registered_email = $_SESSION['registered_email'] ?? '';
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, email, password, role, status, doctor_status, syndicate_card, profile_picture FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if (isMaintenanceMode($conn) && $user['role'] !== 'admin') {
            $error = 'المنصة في وضع الصيانة. يمكن للإدارة فقط تسجيل الدخول حالياً.';
        } elseif ($user['status'] === 'suspended') {
            $error = 'حسابك موقوف. تواصل مع الإدارة.';
        } elseif ($user['role'] === 'doctor' && $user['doctor_status'] === 'pending') {
            $error = 'حسابك لا يزال قيد المراجعة. سيتم إشعارك بعد موافقة الإدارة على كارنيه النقابة الخاص بك.';
        } elseif ($user['role'] === 'doctor' && $user['doctor_status'] === 'rejected') {
            $error = 'تم رفض طلب توثيق حسابك. يرجى التواصل مع الإدارة لمزيد من التفاصيل.';
        } else {
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['name']           = $user['name'];
            $_SESSION['email']          = $user['email'];
            $_SESSION['role']           = $user['role'];
            $_SESSION['doctor_status']  = $user['doctor_status'];
            $_SESSION['syndicate_card'] = $user['syndicate_card'];
            $_SESSION['profile_picture']= $user['profile_picture'];
            $_SESSION['last_activity']  = time();

            if ($user['role'] === 'admin') redirect('admin/dashboard.php');
            elseif ($user['role'] === 'doctor') redirect('doctor/dashboard.php');
            else redirect('user/dashboard.php');
        }
    } else {
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="margin:0;padding:0;height:100vh;overflow:hidden;">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - تسجيل الدخول</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; }
    html, body { margin: 0 !important; padding: 0 !important; height: 100vh; width: 100vw; overflow: hidden !important; }
    body { display: flex; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.6s ease forwards; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .animate-float { animation: float 3s ease-in-out infinite; }
    .input-focus:focus { outline: none; border-color: #8F00FF; box-shadow: 0 0 0 3px rgba(143,0,255,0.15); }
  </style>
</head>
<body style="margin:0;padding:0;overflow:hidden;">
  <div style="display:flex;width:100vw;height:100vh;margin:0;padding:0;">

    <!-- Left Side - Desktop Only -->
    <div class="hidden lg:flex" style="width:50%;height:100vh;background:linear-gradient(135deg, #8F00FF, #5500CC);flex-direction:column;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden;">
      <div class="absolute top-0 left-0 w-full h-full opacity-10">
        <div class="absolute top-10 left-10 w-40 h-40 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-60 h-60 bg-white rounded-full blur-3xl"></div>
      </div>
      <div class="relative z-10 text-center text-white">
        <a href="index.php" class="flex items-center justify-center gap-3 mb-10">
          <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-16 w-auto">
          <span class="text-xl font-black">منصة توعية لمرض الصرع</span>
        </a>
        <div class="animate-float mb-8 relative">
          <img src="assets/img/1.jpg" alt="طبيب أطفال" class="rounded-3xl shadow-2xl w-72 h-64 object-cover mx-auto">
          <img src="assets/img/3.jpg" alt="رعاية طبية" class="rounded-2xl shadow-xl w-32 h-32 object-cover absolute -bottom-4 -left-4 border-4 border-white/30">
        </div>
        <h2 class="text-2xl font-bold mb-3">مرحباً بعودتك!</h2>
        <p class="text-purple-200 text-base leading-relaxed max-w-xs mx-auto">سجّل دخولك للوصول إلى لوحة التحكم ومتابعة حالة طفلك</p>
        <div class="flex items-center justify-center gap-6 mt-8">
          <div class="text-center">
            <p class="text-2xl font-black">+500</p>
            <p class="text-purple-200 text-xs">حالة مشخّصة</p>
          </div>
          <div class="w-px h-10 bg-white/30"></div>
          <div class="text-center">
            <p class="text-2xl font-black">+50</p>
            <p class="text-purple-200 text-xs">طبيب متخصص</p>
          </div>
          <div class="w-px h-10 bg-white/30"></div>
          <div class="text-center">
            <p class="text-2xl font-black">98%</p>
            <p class="text-purple-200 text-xs">رضا المستخدمين</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side - Form -->
    <div style="width:100%;height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:#f9fafb;" class="lg:w-1/2">
      <div class="w-full max-w-md animate-fadeInUp">

        <!-- Mobile Logo -->
        <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
          <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
          <span class="text-base font-bold gradient-text">منصة توعية لمرض الصرع</span>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8 border border-purple-100">
          <div class="mb-8">
            <h1 class="text-2xl font-black text-gray-800 mb-2">تسجيل الدخول</h1>
            <p class="text-gray-500 text-sm">أدخل بياناتك للوصول إلى حسابك</p>
          </div>

          <?php if ($success): ?>
          <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($success) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($error): ?>
          <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
          <?php endif; ?>

          <form method="POST" action="login.php" class="space-y-5">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="email" name="email" placeholder="example@email.com"
                       value="<?= htmlspecialchars($registered_email ?: ($_POST['email'] ?? '')) ?>"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-4 text-sm bg-gray-50 transition" required>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="password" name="password" id="passwordInput" placeholder="••••••••"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-10 text-sm bg-gray-50 transition" required>
                <button type="button" id="togglePassword" class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <i data-lucide="eye" class="w-5 h-5 text-gray-400 hover:text-purple-600 transition"></i>
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 accent-purple-600">
                <span class="text-sm text-gray-600">تذكرني</span>
              </label>
              <a href="forgot-password.php" class="text-sm text-purple-600 font-semibold hover:text-purple-800 transition">نسيت كلمة المرور؟</a>
            </div>

            <button type="submit" class="w-full gradient-primary text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-200 flex items-center justify-center gap-2">
              <i data-lucide="log-in" class="w-5 h-5"></i>
              تسجيل الدخول
            </button>
          </form>

          <div class="mt-6 text-center">
            <p class="text-gray-500 text-sm">ليس لديك حساب؟ <a href="register.php" class="text-purple-600 font-bold hover:text-purple-800 transition">إنشاء حساب جديد</a></p>
          </div>

          <div class="mt-4 text-center">
            <a href="index.php" class="text-gray-400 text-sm hover:text-gray-600 transition flex items-center justify-center gap-1">
              <i data-lucide="arrow-right" class="w-4 h-4"></i>
              العودة للرئيسية
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();
    document.getElementById('togglePassword').addEventListener('click', function () {
      const input = document.getElementById('passwordInput');
      const icon  = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      lucide.createIcons();
    });
  </script>
</body>
</html>
