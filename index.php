<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header('Location: admin/dashboard.php');
    elseif ($role === 'doctor') header('Location: doctor/dashboard.php');
    else header('Location: user/dashboard.php');
    exit;
}

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_reports = $conn->query("SELECT COUNT(*) as cnt FROM reports")->fetch_assoc()['cnt'];
$total_articles = $conn->query("SELECT COUNT(*) as cnt FROM articles")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.6s ease forwards; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .animate-float { animation: float 3s ease-in-out infinite; }
    .fade-in-section { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
    .fade-in-section.visible { opacity: 1; transform: translateY(0); }
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-white text-gray-800">

  <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md shadow-sm border-b border-purple-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <a href="index.php" class="flex items-center gap-2">
          <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
          <span class="text-base font-bold gradient-text">منصة توعية لمرض الصرع</span>
        </a>
        <div class="hidden md:flex items-center gap-6">
          <a href="index.php" class="text-purple-700 font-semibold text-sm hover:text-purple-900 transition">الرئيسية</a>
          <a href="about.php" class="text-gray-600 font-medium text-sm hover:text-purple-700 transition">عن المنصة</a>
          <a href="contact.php" class="text-gray-600 font-medium text-sm hover:text-purple-700 transition">تواصل معنا</a>
        </div>
        <div class="hidden md:flex items-center gap-3">
          <a href="login.php" class="text-purple-700 font-semibold text-sm border border-purple-300 px-4 py-2 rounded-full hover:bg-purple-50 transition">تسجيل الدخول</a>
          <a href="register.php" class="gradient-primary text-white font-semibold text-sm px-4 py-2 rounded-full hover:opacity-90 transition shadow-md">إنشاء حساب</a>
        </div>
        <button id="menuBtn" class="md:hidden p-2 rounded-lg text-purple-700"><i data-lucide="menu" class="w-6 h-6"></i></button>
      </div>
    </div>
    <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-purple-100 px-4 py-4 space-y-3">
      <a href="index.php" class="block text-purple-700 font-semibold">الرئيسية</a>
      <a href="about.php" class="block text-gray-600 font-medium">عن المنصة</a>
      <a href="contact.php" class="block text-gray-600 font-medium">تواصل معنا</a>
      <a href="login.php" class="block text-purple-700 font-semibold">تسجيل الدخول</a>
      <a href="register.php" class="block gradient-primary text-white font-semibold px-4 py-2 rounded-full text-center">إنشاء حساب</a>
    </div>
  </nav>

  <section class="min-h-screen pt-16 bg-gradient-to-br from-purple-50 via-white to-violet-50 flex items-center">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div class="animate-fadeInUp">
          <span class="inline-block bg-purple-100 text-purple-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-6">🧠 منصة طبية متخصصة 2026</span>
          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight mb-6">معاً <span class="gradient-text">نحارب</span><br>الصرع</h1>
          <p class="text-gray-600 text-lg leading-relaxed mb-8 max-w-lg">منصة توعية لمرض الصرع تجمع الأهل والأطباء المتخصصين لتشخيص ومتابعة مرض الصرع عند الأطفال بأحدث التقنيات الذكية في مصر.</p>
          <div class="flex flex-wrap gap-4">
            <a href="register.php" class="gradient-primary text-white font-bold px-8 py-3.5 rounded-full hover:opacity-90 transition shadow-lg shadow-purple-300 flex items-center gap-2">
              <i data-lucide="rocket" class="w-5 h-5"></i> ابدأ الآن
            </a>
            <a href="about.php" class="border-2 border-purple-300 text-purple-700 font-bold px-8 py-3.5 rounded-full hover:bg-purple-50 transition flex items-center gap-2">
              <i data-lucide="info" class="w-5 h-5"></i> اعرف أكثر
            </a>
          </div>
        </div>
        <div class="flex justify-center animate-float">
          <div class="relative">
            <div class="absolute inset-0 gradient-primary rounded-3xl blur-3xl opacity-20 scale-110"></div>
            <img src="assets/img/1.jpg" alt="طفل مصاب بالصرع" class="relative rounded-3xl shadow-2xl w-full max-w-lg object-cover h-[520px]">
            <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl shadow-xl p-4 flex items-center gap-3">
              <div class="w-10 h-10 gradient-primary rounded-full flex items-center justify-center"><i data-lucide="shield-check" class="w-5 h-5 text-white"></i></div>
              <div><p class="text-xs text-gray-500">تشخيص موثوق</p><p class="text-sm font-bold text-gray-800"><?= $total_reports ?>+ حالة</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-16 gradient-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center text-white fade-in-section">
        <div class="p-6">
          <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="users" class="w-8 h-8 text-white"></i></div>
          <h3 class="text-4xl font-black mb-2"><?= $total_users ?>+</h3>
          <p class="text-purple-200 font-medium">مستخدم مسجل</p>
        </div>
        <div class="p-6">
          <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="stethoscope" class="w-8 h-8 text-white"></i></div>
          <h3 class="text-4xl font-black mb-2"><?= $total_reports ?>+</h3>
          <p class="text-purple-200 font-medium">حالة مشخصة</p>
        </div>
        <div class="p-6">
          <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="book-open" class="w-8 h-8 text-white"></i></div>
          <h3 class="text-4xl font-black mb-2"><?= $total_articles ?>+</h3>
          <p class="text-purple-200 font-medium">مقال توعوي</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-14 fade-in-section">
        <span class="text-purple-600 font-semibold text-sm">مميزاتنا</span>
        <h2 class="text-3xl sm:text-4xl font-black mt-2">لماذا <span class="gradient-text">منصة توعية لمرض الصرع؟</span></h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="fade-in-section bg-gradient-to-br from-purple-50 to-violet-50 rounded-3xl p-8 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-purple-100">
          <div class="w-14 h-14 gradient-primary rounded-2xl flex items-center justify-center mb-6"><i data-lucide="cpu" class="w-7 h-7 text-white"></i></div>
          <h3 class="text-xl font-bold mb-3">تشخيص ذكي</h3>
          <p class="text-gray-600 leading-relaxed">نظام يساعد الأطباء في تحليل الأعراض وتشخيص الحالات بدقة عالية وسرعة فائقة.</p>
        </div>
        <div class="fade-in-section bg-gradient-to-br from-purple-50 to-violet-50 rounded-3xl p-8 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-purple-100">
          <div class="w-14 h-14 gradient-primary rounded-2xl flex items-center justify-center mb-6"><i data-lucide="library" class="w-7 h-7 text-white"></i></div>
          <h3 class="text-xl font-bold mb-3">محتوى توعوي</h3>
          <p class="text-gray-600 leading-relaxed">مكتبة من المقالات والفيديوهات التوعوية باللغة العربية لمساعدة الأهل على فهم المرض.</p>
        </div>
        <div class="fade-in-section bg-gradient-to-br from-purple-50 to-violet-50 rounded-3xl p-8 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-purple-100">
          <div class="w-14 h-14 gradient-primary rounded-2xl flex items-center justify-center mb-6"><i data-lucide="activity" class="w-7 h-7 text-white"></i></div>
          <h3 class="text-xl font-bold mb-3">متابعة مستمرة</h3>
          <p class="text-gray-600 leading-relaxed">تتبع حالة طفلك وتلقَّ تقارير دورية من الطبيب المعالج مع تنبيهات فورية.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-20 gradient-primary">
    <div class="max-w-4xl mx-auto px-4 text-center fade-in-section">
      <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6"><i data-lucide="heart" class="w-10 h-10 text-white"></i></div>
      <h2 class="text-3xl sm:text-4xl font-black text-white mb-4">ابدأ رحلة الشفاء اليوم</h2>
      <p class="text-purple-200 text-lg mb-8 max-w-xl mx-auto">انضم لآلاف الأسر المصرية التي تثق في منصتنا لرعاية أطفالها</p>
      <a href="register.php" class="inline-flex items-center gap-2 bg-white text-purple-700 font-bold px-10 py-4 rounded-full hover:bg-purple-50 transition shadow-xl text-lg">
        <i data-lucide="user-plus" class="w-5 h-5"></i> سجّل مجاناً الآن
      </a>
    </div>
  </section>

  <footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div class="md:col-span-2">
          <a href="index.php" class="flex items-center gap-2 mb-4">
            <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
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
            <li><a href="forgot-password.php" class="text-gray-400 hover:text-purple-400 transition text-sm">نسيت الباسورد</a></li>
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
    document.getElementById('menuBtn').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.toggle('hidden');
    });
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in-section').forEach(el => observer.observe(el));
  </script>
</body>
</html>
