<?php
$message = 'المنصة قيد الصيانة حالياً. نعود قريباً.';
if (isset($conn) && $conn instanceof mysqli && function_exists('getSetting')) {
    $message = getSetting($conn, 'maintenance_message', $message);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>صيانة - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>* { font-family: 'Cairo', sans-serif; } .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
  <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-gray-100 p-10 text-center">
    <div class="w-16 h-16 rounded-full gradient-primary text-white flex items-center justify-center text-2xl font-bold mx-auto mb-6">⚙</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">وضع الصيانة</h1>
    <p class="text-gray-600 leading-relaxed mb-6"><?= htmlspecialchars($message) ?></p>
    <a href="login.php" class="inline-block gradient-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90">تسجيل دخول الإدارة</a>
  </div>
</body>
</html>
