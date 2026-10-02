<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
if (empty($user['name'])) { $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']); if ($r && $row = $r->fetch_assoc()) { $user['name'] = $row['name']; $user['profile_picture'] = $row['profile_picture']; } }

$category = sanitize($_GET['category'] ?? '');
$search   = sanitize($_GET['search'] ?? '');
$where  = "WHERE (status='published' OR status IS NULL)"; $params = []; $types = "";
if ($category) { $where .= " AND category=?"; $params[] = $category; $types .= "s"; }
if ($search)   { $where .= " AND title LIKE ?"; $params[] = "%$search%"; $types .= "s"; }

$sql  = "SELECT * FROM videos $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cat_map   = ['awareness'=>'توعوي','diagnostic'=>'تشخيصي','treatment'=>'علاجي'];
$cat_color = ['awareness'=>'bg-blue-100 text-blue-700','diagnostic'=>'bg-orange-100 text-orange-700','treatment'=>'bg-green-100 text-green-700'];

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc()['cnt'];

function getYouTubeId(string $url): string {
    if (empty($url)) return '';
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الفيديوهات - منصة توعية لمرض الصرع</title>
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
      <a href="videos.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
        <i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span>
      </a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
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
        <h1 class="text-2xl font-bold text-gray-800">الفيديوهات</h1>
        <p class="text-sm text-gray-500">فيديوهات تعليمية وتوعوية عن مرض الصرع</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">

      
      <div class="flex flex-col md:flex-row gap-4 mb-8">
        <form method="GET" action="videos.php" class="flex gap-3 flex-1">
          <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="ابحث في الفيديوهات..."
                   class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 text-sm">
          </div>
          <?php if ($category): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
          <?php endif; ?>
          <button type="submit" class="gradient-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">بحث</button>
        </form>
      </div>

      
      <div class="flex flex-wrap gap-3 mb-8">
        <a href="videos.php<?= $search ? '?search='.urlencode($search) : '' ?>"
           class="px-6 py-2 rounded-full font-semibold text-sm transition <?= !$category ? 'gradient-primary text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-purple-50' ?>">
          الكل
        </a>
        <?php foreach ($cat_map as $key => $label): ?>
        <a href="videos.php?category=<?= $key ?><?= $search ? '&search='.urlencode($search) : '' ?>"
           class="px-6 py-2 rounded-full font-semibold text-sm transition <?= $category === $key ? 'gradient-primary text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-purple-50' ?>">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>

      
      <?php if (empty($videos)): ?>
      <div class="text-center py-16">
        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="video-off" class="w-8 h-8 text-purple-400"></i>
        </div>
        <p class="text-gray-500">لا توجد فيديوهات <?= $search ? 'تطابق بحثك' : 'في هذه الفئة' ?> بعد</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($videos as $idx => $video):
          $catKey   = $video['category'] ?? 'awareness';
          $catLabel = $cat_map[$catKey] ?? $catKey;
          $catCls   = $cat_color[$catKey] ?? 'bg-gray-100 text-gray-700';
          $ytId     = getYouTubeId($video['youtube_url'] ?? '');
          $thumbSrc = !empty($video['thumbnail']) ? '../' . htmlspecialchars($video['thumbnail']) : '../assets/img/1.jpg';
          if ($ytId) $thumbSrc = "https://img.youtube.com/vi/{$ytId}/hqdefault.jpg";
          $excerpt  = mb_substr(strip_tags($video['description'] ?? ''), 0, 80, 'UTF-8');
          $views    = number_format((int)($video['views'] ?? 0));
          $delay    = ($idx % 6) * 0.1;
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all animate-fadeInUp"
             style="animation-delay:<?= $delay ?>s;opacity:0">
          
          <div class="relative h-48 bg-gray-200 overflow-hidden cursor-pointer group"
               onclick="openVideo(<?= htmlspecialchars(json_encode($video['youtube_url'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($video['title'] ?? ''), ENT_QUOTES) ?>)">
            <img src="<?= htmlspecialchars($thumbSrc) ?>"
                 onerror="this.src='../assets/img/1.jpg'"
                 alt="<?= htmlspecialchars($video['title']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            
            <div class="absolute inset-0 bg-black/30 flex items-center justify-center group-hover:bg-black/40 transition">
              <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center group-hover:bg-white/30 transition backdrop-blur-sm">
                <i data-lucide="play" class="w-7 h-7 text-white mr-0.5"></i>
              </div>
            </div>
            
            <span class="absolute top-3 right-3 <?= $catCls ?> text-xs px-2 py-1 rounded-full font-semibold"><?= $catLabel ?></span>
          </div>

          <div class="p-5">
            <h3 class="text-base font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($video['title']) ?></h3>
            <?php if ($excerpt): ?>
            <p class="text-gray-500 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($excerpt) ?>...</p>
            <?php endif; ?>
            <div class="flex items-center justify-between text-xs text-gray-400">
              <span class="flex items-center gap-1">
                <i data-lucide="eye" class="w-3 h-3"></i>
                <?= $views ?> مشاهدة
              </span>
              <button onclick="openVideo(<?= htmlspecialchars(json_encode($video['youtube_url'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($video['title'] ?? ''), ENT_QUOTES) ?>)"
                      class="text-purple-600 font-semibold hover:text-purple-800 transition flex items-center gap-1">
                <i data-lucide="play-circle" class="w-4 h-4"></i>
                مشاهدة
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>

  
  <div id="videoModal" class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 id="videoModalTitle" class="text-base font-bold text-gray-800 truncate flex-1 ml-4"></h3>
        <button onclick="closeVideo()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition flex-shrink-0">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="relative" style="padding-bottom:56.25%">
        <iframe id="videoIframe"
                src=""
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    function getYouTubeId(url) {
      if (!url) return '';
      const patterns = [
        /youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/,
        /youtu\.be\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/,
      ];
      for (const p of patterns) {
        const m = url.match(p);
        if (m) return m[1];
      }
      return '';
    }

    function openVideo(url, title) {
      const ytId = getYouTubeId(url);
      if (!ytId) { alert('رابط الفيديو غير صحيح'); return; }
      document.getElementById('videoModalTitle').textContent = title || 'مشاهدة الفيديو';
      document.getElementById('videoIframe').src = 'https://www.youtube.com/embed/' + ytId + '?autoplay=1&rel=0';
      const modal = document.getElementById('videoModal');
      modal.style.display = 'flex';
      lucide.createIcons();
    }

    function closeVideo() {
      document.getElementById('videoIframe').src = '';
      const modal = document.getElementById('videoModal');
      modal.style.display = 'none';
    }

    document.getElementById('videoModal').addEventListener('click', function(e) {
      if (e.target === this) closeVideo();
    });
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
