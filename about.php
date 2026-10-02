<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_reports = $conn->query("SELECT COUNT(*) as cnt FROM reports")->fetch_assoc()['cnt'];
$total_articles = $conn->query("SELECT COUNT(*) as cnt FROM articles")->fetch_assoc()['cnt'];
$total_doctors = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='doctor'")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - عن المنصة</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .fade-in-section { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
    .fade-in-section.visible { opacity: 1; transform: translateY(0); }
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
          <a href="about.php" class="text-purple-700 font-semibold text-sm">عن المنصة</a>
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
      <a href="index.php" class="block text-gray-600">الرئيسية</a>
      <a href="about.php" class="block text-purple-700 font-semibold">عن المنصة</a>
      <a href="contact.php" class="block text-gray-600">تواصل معنا</a>
      <a href="login.php" class="block text-purple-700 font-semibold">تسجيل الدخول</a>
      <a href="register.php" class="block gradient-primary text-white font-semibold px-4 py-2 rounded-full text-center">إنشاء حساب</a>
    </div>
  </nav>

  <section class="pt-16 gradient-primary min-h-[50vh] flex items-center relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-10 right-10 w-64 h-64 bg-white rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10 text-center text-white">
      <span class="inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-6">🧠 قصتنا</span>
      <h1 class="text-4xl sm:text-5xl font-black mb-4">عن <span class="text-yellow-300">منصة توعية لمرض الصرع</span></h1>
      <p class="text-purple-200 text-lg max-w-2xl mx-auto">نحن فريق من الأطباء والمطورين المصريين نؤمن بأن كل طفل يستحق أفضل رعاية صحية ممكنة</p>
    </div>
  </section>

  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div class="fade-in-section">
          <span class="text-purple-600 font-semibold text-sm">قصتنا</span>
          <h2 class="text-3xl sm:text-4xl font-black mt-2 mb-6">كيف بدأت <span class="gradient-text">منصة توعية لمرض الصرع؟</span></h2>
          <div class="space-y-4 text-gray-600 leading-relaxed">
            <p>في عام 2024، لاحظ مجموعة من أطباء الأعصاب المصريين أن كثيراً من الأطفال المصابين بالصرع يعانون من تأخر في التشخيص بسبب قلة الوعي وصعوبة الوصول للمتخصصين.</p>
            <p>قرر الفريق بناء منصة رقمية تجمع الأهل بالأطباء المتخصصين، وتوفر محتوى توعوياً باللغة العربية، وتساعد الأطباء في التشخيص المبكر.</p>
            <p>اليوم، منصة توعية لمرض الصرع تخدم مئات الأسر المصرية وتضم شبكة من أفضل أطباء الأعصاب في مصر.</p>
          </div>
        </div>
        <div class="fade-in-section">
          <div class="grid grid-cols-2 gap-4">
            <div class="relative overflow-hidden rounded-3xl shadow-xl"><img src="assets/img/1.jpg" class="w-full h-56 object-cover"><div class="absolute inset-0 gradient-primary opacity-20"></div></div>
            <div class="relative overflow-hidden rounded-3xl shadow-xl mt-8"><img src="assets/img/5.jpg" class="w-full h-56 object-cover"><div class="absolute inset-0 gradient-primary opacity-20"></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-16 gradient-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center text-white fade-in-section">
        <div class="p-4"><h3 class="text-4xl font-black mb-1"><?= $total_users ?>+</h3><p class="text-purple-200 text-sm">مستخدم مسجل</p></div>
        <div class="p-4"><h3 class="text-4xl font-black mb-1"><?= $total_doctors ?>+</h3><p class="text-purple-200 text-sm">طبيب متخصص</p></div>
        <div class="p-4"><h3 class="text-4xl font-black mb-1"><?= $total_articles ?>+</h3><p class="text-purple-200 text-sm">مقال توعوي</p></div>
        <div class="p-4"><h3 class="text-4xl font-black mb-1"><?= $total_reports ?>+</h3><p class="text-purple-200 text-sm">حالة مشخصة</p></div>
      </div>
    </div>
  </section>

  <section class="py-20 bg-gradient-to-br from-purple-50 to-violet-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="fade-in-section bg-white rounded-3xl p-10 shadow-lg border border-purple-100">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mb-6"><i data-lucide="target" class="w-8 h-8 text-white"></i></div>
          <h3 class="text-2xl font-black mb-4">رسالتنا</h3>
          <p class="text-gray-600 leading-relaxed">توفير رعاية صحية متخصصة وميسورة لكل طفل مصاب بالصرع في مصر، من خلال تقنيات حديثة وفريق طبي متخصص.</p>
        </div>
        <div class="fade-in-section bg-white rounded-3xl p-10 shadow-lg border border-purple-100">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mb-6"><i data-lucide="eye" class="w-8 h-8 text-white"></i></div>
          <h3 class="text-2xl font-black mb-4">رؤيتنا</h3>
          <p class="text-gray-600 leading-relaxed">أن نكون المنصة الرائدة في الوطن العربي لتشخيص ومتابعة أمراض الأعصاب عند الأطفال بحلول عام 2030.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-16 gradient-primary">
    <div class="max-w-4xl mx-auto px-4 text-center fade-in-section">
      <h2 class="text-3xl font-black text-white mb-4">انضم إلى مجتمعنا اليوم</h2>
      <p class="text-purple-200 mb-8">كن جزءاً من مجتمع يهتم بصحة أطفالنا</p>
      <a href="register.php" class="inline-flex items-center gap-2 bg-white text-purple-700 font-bold px-8 py-3.5 rounded-full hover:bg-purple-50 transition shadow-xl">
        <i data-lucide="user-plus" class="w-5 h-5"></i> سجّل مجاناً
      </a>
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
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in-section').forEach(el => observer.observe(el));
  </script>
</body>
</html>
