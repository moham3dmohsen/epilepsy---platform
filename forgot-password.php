<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

if (isLoggedIn()) redirect('/user/dashboard.php');

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - استعادة كلمة المرور</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.7s ease forwards; }
    .input-focus:focus { outline: none; border-color: #8F00FF; box-shadow: 0 0 0 3px rgba(143,0,255,0.15); }
    @keyframes pulse-ring { 0% { transform: scale(0.9); opacity: 1; } 100% { transform: scale(1.4); opacity: 0; } }
    .pulse-ring::before { content: ''; position: absolute; inset: -8px; border-radius: 50%; border: 2px solid #8F00FF; animation: pulse-ring 1.5s ease-out infinite; }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-violet-50 flex items-center justify-center p-4">

  <div class="w-full max-w-md animate-fadeInUp">
    
    <a href="login.php" class="flex items-center gap-2 text-purple-600 font-semibold text-sm mb-6 hover:text-purple-800 transition w-fit">
      <i data-lucide="arrow-right" class="w-4 h-4"></i>
      العودة لتسجيل الدخول
    </a>

    <div class="bg-white rounded-3xl shadow-xl p-8 border border-purple-100">
      
      <div class="flex justify-center mb-6">
        <div class="relative">
          <div class="pulse-ring relative w-20 h-20 gradient-primary rounded-full flex items-center justify-center shadow-lg shadow-purple-300">
            <i data-lucide="key-round" class="w-9 h-9 text-white"></i>
          </div>
        </div>
      </div>

      
      <div class="flex items-center justify-center gap-2 mb-6">
        <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
        <span class="text-lg font-bold gradient-text">منصة توعية لمرض الصرع</span>
      </div>

      <div class="text-center mb-8">
        <h1 class="text-2xl font-black text-gray-800 mb-2">نسيت كلمة المرور؟</h1>
        <p class="text-gray-500 text-sm leading-relaxed">لا تقلق! أدخل بريدك الإلكتروني وسنرسل لك رابط لإعادة تعيين كلمة المرور</p>
      </div>

      <?php if ($error): ?>
      <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      
      <div class="text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="check-circle" class="w-8 h-8 text-green-500"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">تم الإرسال بنجاح!</h3>
        <p class="text-gray-500 text-sm mb-6">إذا كان البريد الإلكتروني مسجلاً لدينا، ستصلك رسالة تحتوي على رابط لإعادة تعيين كلمة المرور. تحقق من مجلد الرسائل غير المرغوب فيها إن لم تجدها.</p>
        <a href="login.php" class="inline-flex items-center gap-2 gradient-primary text-white font-bold px-6 py-3 rounded-xl hover:opacity-90 transition">
          <i data-lucide="log-in" class="w-4 h-4"></i>
          العودة لتسجيل الدخول
        </a>
      </div>
      <?php else: ?>
      
      <form method="POST" action="forgot-password.php" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
          <div class="relative">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
            </div>
            <input type="email" name="email" placeholder="example@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-4 text-sm bg-gray-50 transition" required>
          </div>
        </div>

        <button type="submit" class="w-full gradient-primary text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-200 flex items-center justify-center gap-2">
          <i data-lucide="send" class="w-5 h-5"></i>
          إرسال رابط الاستعادة
        </button>
      </form>
      <?php endif; ?>

      <div class="mt-6 pt-6 border-t border-gray-100 text-center">
        <p class="text-gray-500 text-sm">ليس لديك حساب؟ <a href="register.php" class="text-purple-600 font-bold hover:text-purple-800 transition">إنشاء حساب جديد</a></p>
      </div>
    </div>

    
    <div class="mt-6 bg-purple-50 rounded-2xl p-4 border border-purple-100">
      <div class="flex items-start gap-3">
        <div class="w-8 h-8 gradient-primary rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="info" class="w-4 h-4 text-white"></i>
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-700 mb-1">هل تحتاج مساعدة؟</p>
          <p class="text-xs text-gray-500 leading-relaxed">إذا لم تصلك الرسالة، تحقق من مجلد الرسائل غير المرغوب فيها أو تواصل معنا على <span class="text-purple-600 font-semibold">support@nabda.eg</span></p>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
