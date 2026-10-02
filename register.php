<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/functions.php';

if (isLoggedIn()) redirect('user/dashboard.php');

$error   = '';
$success = '';
$registrationClosed = !isRegistrationOpen($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($registrationClosed) {
        $error = settingIsOn($conn, 'maintenance_mode')
            ? 'التسجيل غير متاح حالياً — المنصة في وضع الصيانة.'
            : 'التسجيل مغلق حالياً من قبل الإدارة.';
    } else {
    $name     = sanitize($_POST['name']     ?? '');
    $email    = sanitize($_POST['email']    ?? '');
    $phone    = sanitize($_POST['phone']    ?? '');
    $password = $_POST['password']          ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';
    $role     = in_array($_POST['role'] ?? '', ['parent', 'doctor']) ? $_POST['role'] : 'parent';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($role === 'doctor' && (empty($_FILES['syndicate_card']['name']) || $_FILES['syndicate_card']['error'] === UPLOAD_ERR_NO_FILE)) {
        $error = 'يرجى رفع صورة كارنيه النقابة الطبية';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'البريد الإلكتروني مستخدم بالفعل';
        } else {
            // رفع صورة الكارنيه إذا كان الدور طبيب
            $syndicate_card = null;
            if ($role === 'doctor') {
                $syndicate_card = uploadImage($_FILES['syndicate_card'], 'doctor_cards');
                if (empty($syndicate_card)) {
                    $error = 'فشل رفع الصورة. تأكد أن الملف صورة صحيحة (JPG, PNG, GIF, WEBP)';
                    goto end_registration;
                }
            }

            $hashed       = password_hash($password, PASSWORD_DEFAULT);
            $doctor_status = ($role === 'doctor') ? 'pending' : null;

            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, syndicate_card, doctor_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $email, $phone, $hashed, $role, $syndicate_card, $doctor_status);
            if ($stmt->execute()) {
                $newId = (int)$conn->insert_id;
                if ($role === 'doctor') {
                    notifyAdmins($conn, 'system', 'طلب تسجيل طبيب جديد', "طلب جديد من د. $name ($email)", 'users.php?role=doctor&status=active', 'new_user');
                    notifyUser($conn, $newId, 'system', 'طلب التسجيل قيد المراجعة', 'تم استلام طلبك وسيتم مراجعته من الإدارة.', 'login.php');
                } else {
                    notifyAdmins($conn, 'system', 'ولي أمر جديد', "تسجيل جديد: $name ($email)", 'users.php?role=parent', 'new_user');
                    notifyUser($conn, $newId, 'system', 'مرحباً بك في المنصة', 'تم إنشاء حسابك بنجاح. سجّل الدخول للبدء.', 'login.php');
                }
                $_SESSION['registration_success'] = true;
                $_SESSION['registered_email'] = $email;
                if ($role === 'doctor') {
                    $_SESSION['doctor_pending'] = true;
                }
                header("Location: login.php");
                exit;
            } else {
                $error = 'حدث خطأ. حاول مرة أخرى.';
            }
        }
    }
    end_registration:;
    }
}

$old = [
    'name'  => htmlspecialchars($_POST['name']  ?? ''),
    'email' => htmlspecialchars($_POST['email'] ?? ''),
    'phone' => htmlspecialchars($_POST['phone'] ?? ''),
    'role'  => $_POST['role'] ?? 'parent',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة توعية لمرض الصرع - إنشاء حساب</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; }
    html, body { margin: 0; padding: 0; height: 100vh; width: 100vw; }
    body { display: flex; overflow: hidden; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .gradient-text { background: linear-gradient(135deg, #8F00FF, #5500CC); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeInUp { animation: fadeInUp 0.6s ease forwards; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .animate-float { animation: float 3s ease-in-out infinite; }
    .input-focus:focus { outline: none; border-color: #8F00FF; box-shadow: 0 0 0 3px rgba(143,0,255,0.15); }
    .account-type-btn { transition: all 0.2s; }
    .account-type-btn.active { background: linear-gradient(135deg, #8F00FF, #5500CC); color: white; border-color: transparent; }
    .scroll-form { overflow-y: auto; max-height: 100vh; padding-top: 3rem; }
    .scroll-form::-webkit-scrollbar { width: 5px; }
    .scroll-form::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
    .main-container { display: flex; width: 100vw; height: 100vh; }
    @media (max-width: 1024px) {
      .scroll-form { padding-top: 2rem; }
    }
  </style>
</head>
<body>
  <div class="main-container">

    <!-- Left Side - Desktop Only -->
    <div class="hidden lg:flex lg:w-1/2" style="height:100vh;background:linear-gradient(135deg, #8F00FF, #5500CC);flex-direction:column;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden;">
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
          <img src="assets/img/2.jpg" alt="عائلة سعيدة" class="rounded-3xl shadow-2xl w-72 h-64 object-cover mx-auto">
          <img src="assets/img/4.jpg" alt="طفل سعيد" class="rounded-2xl shadow-xl w-32 h-32 object-cover absolute -bottom-4 -right-4 border-4 border-white/30">
        </div>
        <h2 class="text-2xl font-bold mb-3">انضم لمجتمعنا</h2>
        <p class="text-purple-200 text-base leading-relaxed max-w-xs mx-auto">سجّل الآن وابدأ رحلة الرعاية الصحية المتخصصة لطفلك</p>
        <div class="mt-8 space-y-3">
          <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-300 flex-shrink-0"></i>
            <p class="text-sm text-purple-100">تشخيص دقيق من أفضل الأطباء</p>
          </div>
          <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-300 flex-shrink-0"></i>
            <p class="text-sm text-purple-100">متابعة مستمرة على مدار الساعة</p>
          </div>
          <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-300 flex-shrink-0"></i>
            <p class="text-sm text-purple-100">محتوى توعوي باللغة العربية</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side - Form -->
    <div class="scroll-form w-full lg:w-1/2" style="height:100vh;padding:2rem 1.5rem;background:#f9fafb;overflow-y:auto;">
      <div class="w-full max-w-md mx-auto animate-fadeInUp" style="padding:2rem 0;">

        <!-- Mobile Logo -->
        <div class="lg:hidden flex items-center justify-center gap-2 mb-6">
          <img src="assets/img/logo1.png" alt="منصة توعية لمرض الصرع" class="h-10 w-auto">
          <span class="text-base font-bold gradient-text">منصة توعية لمرض الصرع</span>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8 border border-purple-100">
          <div class="mb-8">
            <h1 class="text-2xl font-black text-gray-800 mb-2">إنشاء حساب جديد</h1>
            <p class="text-gray-500 text-sm">أدخل بياناتك لإنشاء حسابك</p>
          </div>

          <?php if ($error): ?>
          <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($success): ?>
          <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($success) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($registrationClosed): ?>
          <div class="mb-5 flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm font-semibold">
            <i data-lucide="lock" class="w-5 h-5 flex-shrink-0"></i>
            <span>التسجيل غير متاح حالياً. تواصل مع الإدارة أو حاول لاحقاً.</span>
          </div>
          <?php endif; ?>

          <!-- Account Type -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">نوع الحساب</label>
            <div class="grid grid-cols-2 gap-3">
              <button type="button" onclick="setAccountType('parent', this)"
                      class="account-type-btn <?= $old['role'] === 'parent' ? 'active' : 'border-2 border-gray-200 text-gray-600' ?> border-2 border-purple-200 rounded-xl py-3 px-4 flex items-center justify-center gap-2 font-semibold text-sm">
                <i data-lucide="users" class="w-4 h-4"></i>
                ولي أمر
              </button>
              <button type="button" onclick="setAccountType('doctor', this)"
                      class="account-type-btn <?= $old['role'] === 'doctor' ? 'active' : 'border-2 border-gray-200 text-gray-600' ?> rounded-xl py-3 px-4 flex items-center justify-center gap-2 font-semibold text-sm">
                <i data-lucide="stethoscope" class="w-4 h-4"></i>
                طبيب
              </button>
            </div>
          </div>

          <form method="POST" action="register.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="role" id="accountType" value="<?= htmlspecialchars($old['role']) ?>">

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="text" name="name" placeholder="محمد أحمد علي"
                       value="<?= $old['name'] ?>"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-4 text-sm bg-gray-50 transition" required>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="email" name="email" placeholder="example@email.com"
                       value="<?= $old['email'] ?>"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-4 text-sm bg-gray-50 transition" required>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="phone" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="tel" name="phone" placeholder="01xxxxxxxxx"
                       value="<?= $old['phone'] ?>"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-4 text-sm bg-gray-50 transition" dir="ltr">
              </div>
            </div>

            <!-- حقل كارنيه النقابة - يظهر فقط للطبيب -->
            <div id="syndicateCardField" class="<?= $old['role'] === 'doctor' ? '' : 'hidden' ?>">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                صورة كارنيه النقابة الطبية
                <span class="text-red-500">*</span>
              </label>
              <div class="relative border-2 border-dashed border-purple-200 rounded-xl p-4 bg-purple-50 hover:border-purple-400 transition cursor-pointer" onclick="document.getElementById('syndicateInput').click()">
                <input type="file" name="syndicate_card" id="syndicateInput" accept="image/*" class="hidden" onchange="previewCard(this)">
                <div id="cardPlaceholder" class="flex flex-col items-center gap-2 text-center">
                  <i data-lucide="id-card" class="w-10 h-10 text-purple-400"></i>
                  <p class="text-sm font-semibold text-purple-700">اضغط لرفع صورة الكارنيه</p>
                  <p class="text-xs text-gray-400">JPG, PNG, WEBP - حجم أقصى 5MB</p>
                </div>
                <div id="cardPreview" class="hidden flex-col items-center gap-2">
                  <img id="cardPreviewImg" src="" alt="معاينة الكارنيه" class="max-h-32 rounded-lg object-contain shadow">
                  <p id="cardFileName" class="text-xs text-purple-700 font-semibold"></p>
                  <p class="text-xs text-gray-400">اضغط لتغيير الصورة</p>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                <i data-lucide="info" class="w-3 h-3"></i>
                سيتم مراجعة الكارنيه من قِبل الإدارة قبل تفعيل حسابك
              </p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="password" name="password" id="pass1" placeholder="••••••••"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-10 text-sm bg-gray-50 transition" required>
                <button type="button" onclick="togglePass('pass1')" class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <i data-lucide="eye" class="w-5 h-5 text-gray-400 hover:text-purple-600 transition"></i>
                </button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور</label>
              <div class="relative">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input type="password" name="confirm_password" id="pass2" placeholder="••••••••"
                       class="input-focus w-full border border-gray-200 rounded-xl py-3 pr-10 pl-10 text-sm bg-gray-50 transition" required>
                <button type="button" onclick="togglePass('pass2')" class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <i data-lucide="eye" class="w-5 h-5 text-gray-400 hover:text-purple-600 transition"></i>
                </button>
              </div>
            </div>

            <label class="flex items-start gap-2 cursor-pointer">
              <input type="checkbox" class="w-4 h-4 accent-purple-600 mt-0.5 flex-shrink-0" required>
              <span class="text-sm text-gray-600">أوافق على <a href="#" class="text-purple-600 font-semibold">شروط الاستخدام</a> و<a href="#" class="text-purple-600 font-semibold">سياسة الخصوصية</a></span>
            </label>

            <button type="submit" <?= $registrationClosed ? 'disabled' : '' ?> class="w-full gradient-primary text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
              <i data-lucide="user-plus" class="w-5 h-5"></i>
              إنشاء الحساب
            </button>
          </form>

          <div class="mt-6 text-center">
            <p class="text-gray-500 text-sm">لديك حساب بالفعل؟ <a href="login.php" class="text-purple-600 font-bold hover:text-purple-800 transition">تسجيل الدخول</a></p>
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

    function togglePass(id) {
      const input = document.getElementById(id);
      const btn   = input.nextElementSibling;
      const icon  = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      lucide.createIcons();
    }

    function setAccountType(type, btn) {
      document.getElementById('accountType').value = type;
      document.querySelectorAll('.account-type-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.add('border-2', 'border-gray-200', 'text-gray-600');
      });
      btn.classList.add('active');
      btn.classList.remove('border-gray-200', 'text-gray-600');

      // إظهار/إخفاء حقل الكارنيه
      const cardField = document.getElementById('syndicateCardField');
      const cardInput = document.getElementById('syndicateInput');
      if (type === 'doctor') {
        cardField.classList.remove('hidden');
        cardInput.required = true;
      } else {
        cardField.classList.add('hidden');
        cardInput.required = false;
      }
    }

    function previewCard(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('cardPreviewImg').src = e.target.result;
          document.getElementById('cardFileName').textContent = file.name;
          document.getElementById('cardPlaceholder').classList.add('hidden');
          document.getElementById('cardPreview').classList.remove('hidden');
          document.getElementById('cardPreview').classList.add('flex');
        };
        reader.readAsDataURL(file);
      }
    }

    (function () {
      const savedRole = document.getElementById('accountType').value;
      document.querySelectorAll('.account-type-btn').forEach(btn => {
        const btnType = btn.getAttribute('onclick').match(/'(\w+)'/)[1];
        if (btnType === savedRole) {
          btn.classList.add('active');
          btn.classList.remove('border-gray-200', 'text-gray-600');
        }
      });
      // تفعيل حقل الكارنيه إذا كان الدور محفوظاً كطبيب
      if (savedRole === 'doctor') {
        document.getElementById('syndicateCardField').classList.remove('hidden');
        document.getElementById('syndicateInput').required = true;
      }
    })();
  </script>
</body>
</html>
