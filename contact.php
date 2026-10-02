<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        if ($stmt->execute()) {
            $success = true;
            notifyAdmins($conn, 'system', 'رسالة تواصل جديدة', "رسالة من $name: " . mb_substr($message, 0, 80, 'UTF-8'), 'contact.php', 'contact');
        } else {
            $error = 'حدث خطأ. حاول مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - تواصل معنا</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .input-focus:focus { outline: none; border-color: #8F00FF; box-shadow: 0 0 0 3px rgba(143,0,255,0.15); }
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-white text-gray-800">
  <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md shadow-sm border-b border-purple-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <a href="index.php" class="flex items-center gap-2">
          <img src="assets/img/logo1.png" class="h-10 w-auto">
          <span class="text-base font-bold gradient-text">منصة توعية لمرض الصرع</span>
        </a>
        <div class="hidden md:flex items-center gap-6">
          <a href="index.php" class="text-gray-600 font-medium text-sm hover:text-purple-700 transition">الرئيسية</a>
          <a href="about.php" class="text-gray-600 font-medium text-sm hover:text-purple-700 transition">عن المنصة</a>
          <a href="contact.php" class="text-purple-700 font-semibold text-sm">تواصل معنا</a>
        </div>
        <div class="hidden md:flex items-center gap-3">
          <a href="login.php" class="text-purple-700 font-semibold text-sm border border-purple-300 px-4 py-2 rounded-full hover:bg-purple-50 transition">تسجيل الدخول</a>
          <a href="register.php" class="gradient-primary text-white font-semibold text-sm px-4 py-2 rounded-full hover:opacity-90 transition shadow-md">إنشاء حساب</a>
        </div>
        <button id="menuBtn" class="md:hidden p-2 rounded-lg text-purple-700"><i data-lucide="menu" class="w-6 h-6"></i></button>
      </div>
    </div>
    <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-purple-100 px-4 py-4 space-y-3">
      <a href="index.php" class="block text-gray-600">الرئيسية</a>
      <a href="about.php" class="block text-gray-600">عن المنصة</a>
      <a href="contact.php" class="block text-purple-700 font-semibold">تواصل معنا</a>
      <a href="login.php" class="block text-purple-700 font-semibold">تسجيل الدخول</a>
      <a href="register.php" class="block gradient-primary text-white font-semibold px-4 py-2 rounded-full text-center">إنشاء حساب</a>
    </div>
  </nav>

  <section class="pt-16 gradient-primary min-h-[45vh] flex items-center relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-10 right-10 w-64 h-64 bg-white rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10 text-center text-white">
      <span class="inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-6">📞 نحن هنا لمساعدتك</span>
      <h1 class="text-4xl sm:text-5xl font-black mb-4">تواصل <span class="text-yellow-300">معنا</span></h1>
      <p class="text-purple-200 text-lg max-w-xl mx-auto">فريقنا جاهز للإجابة على جميع استفساراتك</p>
    </div>
  </section>

  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div>
          <h2 class="text-3xl font-black mb-8">أرسل لنا <span class="gradient-text">رسالة</span></h2>
          <?php if ($error): ?>
          <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i><span><?= htmlspecialchars($error) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($success): ?>
          <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3"><i data-lucide="check-circle" class="w-7 h-7 text-green-500"></i></div>
            <h3 class="font-bold text-gray-800 mb-1">تم إرسال رسالتك بنجاح!</h3>
            <p class="text-gray-500 text-sm">سيتواصل معك فريقنا خلال 24 ساعة</p>
          </div>
          <?php else: ?>
          <form method="POST" action="contact.php" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="محمد أحمد" class="input-focus w-full border border-gray-200 rounded-xl py-3 px-4 text-sm bg-gray-50 transition" required>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@email.com" class="input-focus w-full border border-gray-200 rounded-xl py-3 px-4 text-sm bg-gray-50 transition" required>
              </div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="01xxxxxxxxx" class="input-focus w-full border border-gray-200 rounded-xl py-3 px-4 text-sm bg-gray-50 transition" dir="ltr">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الموضوع</label>
              <select name="subject" class="input-focus w-full border border-gray-200 rounded-xl py-3 px-4 text-sm bg-gray-50 transition">
                <option value="">اختر الموضوع</option>
                <option value="استفسار عام">استفسار عام</option>
                <option value="دعم فني">دعم فني</option>
                <option value="طلب انضمام طبيب">طلب انضمام طبيب</option>
                <option value="شكوى">شكوى</option>
                <option value="اقتراح">اقتراح</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الرسالة</label>
              <textarea name="message" rows="5" placeholder="اكتب رسالتك هنا..." class="input-focus w-full border border-gray-200 rounded-xl py-3 px-4 text-sm bg-gray-50 transition resize-none" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="w-full gradient-primary text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-200 flex items-center justify-center gap-2">
              <i data-lucide="send" class="w-5 h-5"></i> إرسال الرسالة
            </button>
          </form>
          <?php endif; ?>
        </div>

        <div class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-purple-50 rounded-2xl p-5 text-center border border-purple-100">
              <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center mx-auto mb-3"><i data-lucide="map-pin" class="w-6 h-6 text-white"></i></div>
              <h3 class="font-bold text-gray-800 mb-1 text-sm">العنوان</h3>
              <p class="text-gray-500 text-xs">١٢ شارع التحرير، القاهرة</p>
            </div>
            <div class="bg-purple-50 rounded-2xl p-5 text-center border border-purple-100">
              <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center mx-auto mb-3"><i data-lucide="phone" class="w-6 h-6 text-white"></i></div>
              <h3 class="font-bold text-gray-800 mb-1 text-sm">الهاتف</h3>
              <p class="text-gray-500 text-xs" dir="ltr">+20 100 123 4567</p>
            </div>
            <div class="bg-purple-50 rounded-2xl p-5 text-center border border-purple-100">
              <div class="w-12 h-12 gradient-primary rounded-xl flex items-center justify-center mx-auto mb-3"><i data-lucide="mail" class="w-6 h-6 text-white"></i></div>
              <h3 class="font-bold text-gray-800 mb-1 text-sm">البريد</h3>
              <p class="text-gray-500 text-xs">info@epilepsy.com</p>
            </div>
          </div>

          <div class="bg-white rounded-3xl p-6 border border-purple-100 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-4">تابعنا على</h3>
            <div class="flex gap-3">
              <a href="https://facebook.com" target="_blank" class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-700 transition shadow-md">
                <svg class="w-5 h-5 fill-white" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="https://instagram.com" target="_blank" class="w-12 h-12 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 rounded-xl flex items-center justify-center hover:opacity-90 transition shadow-md">
                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.5" fill="white" stroke="none"/></svg>
              </a>
              <a href="https://twitter.com" target="_blank" class="w-12 h-12 bg-sky-500 rounded-xl flex items-center justify-center hover:bg-sky-600 transition shadow-md">
                <svg class="w-5 h-5 fill-white" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
              </a>
              <a href="https://youtube.com" target="_blank" class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center hover:bg-red-700 transition shadow-md">
                <svg class="w-5 h-5 fill-white" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white"/></svg>
              </a>
            </div>
          </div>

          <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-3xl p-6 border border-purple-100">
            <div class="flex items-center gap-3 mb-4"><div class="w-10 h-10 gradient-primary rounded-xl flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5 text-white"></i></div><h3 class="font-bold text-gray-800">ساعات العمل</h3></div>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between py-2 border-b border-purple-100"><span class="text-gray-600">السبت - الخميس</span><span class="text-purple-700 font-bold">٩ ص - ٦ م</span></div>
              <div class="flex justify-between py-2 border-b border-purple-100"><span class="text-gray-600">الجمعة</span><span class="text-red-500 font-bold">مغلق</span></div>
              <div class="flex justify-between py-2"><span class="text-gray-600">الدعم الطبي الطارئ</span><span class="text-green-600 font-bold">٢٤/٧</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div class="md:col-span-2">
          <a href="index.php" class="flex items-center gap-2 mb-4">
            <img src="assets/img/logoDark.png" class="h-10 w-auto">
            <span class="text-base font-bold text-white">منصة توعية لمرض الصرع</span>
          </a>
          <p class="text-gray-400 leading-relaxed max-w-sm">منصة متخصصة في توعية وتشخيص مرض الصرع عند الأطفال في مصر.</p>
        </div>
        <div>
          <h4 class="font-bold text-white mb-4">روابط سريعة</h4>
          <ul class="space-y-2">
            <li><a href="index.php" class="text-gray-400 hover:text-purple-400 transition text-sm">الرئيسية</a></li>
            <li><a href="about.php" class="text-gray-400 hover:text-purple-400 transition text-sm">عن المنصة</a></li>
            <li><a href="contact.php" class="text-gray-400 hover:text-purple-400 transition text-sm">تواصل معنا</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-white mb-4">الحساب</h4>
          <ul class="space-y-2">
            <li><a href="login.php" class="text-gray-400 hover:text-purple-400 transition text-sm">تسجيل الدخول</a></li>
            <li><a href="register.php" class="text-gray-400 hover:text-purple-400 transition text-sm">إنشاء حساب</a></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-800 pt-6 text-center">
        <p class="text-gray-500 text-sm">© 2026 منصة توعية لمرض الصرع. جميع الحقوق محفوظة.</p>
      </div>
    </div>
  </footer>

  <script>
    lucide.createIcons();
    document.getElementById('menuBtn').addEventListener('click', () => document.getElementById('mobileMenu').classList.toggle('hidden'));
  </script>
</body>
</html>
