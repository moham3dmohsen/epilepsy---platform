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
if ($search)   { $where .= " AND (title LIKE ? OR content LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= "ss"; }

$sql  = "SELECT * FROM articles $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cat_map   = ['awareness'=>'توعوي','guidance'=>'إرشادي','medical'=>'طبي'];
$cat_color = ['awareness'=>'bg-blue-100 text-blue-700','guidance'=>'bg-green-100 text-green-700','medical'=>'bg-purple-100 text-purple-700'];

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>المحتوى التعليمي - منصة توعية لمرض الصرع</title>
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
      <a href="education.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
        <i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span>
      </a>
      <a href="videos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
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
        <h1 class="text-2xl font-bold text-gray-800">المحتوى التعليمي</h1>
        <p class="text-sm text-gray-500">مقالات وموارد توعوية عن مرض الصرع</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">

      
      <div class="flex flex-col md:flex-row gap-4 mb-8">
        <form method="GET" action="education.php" class="flex gap-3 flex-1">
          <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="ابحث في المقالات..."
                   class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 text-sm">
          </div>
          <?php if ($category): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
          <?php endif; ?>
          <button type="submit" class="gradient-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">بحث</button>
        </form>
      </div>

      
      <div class="flex flex-wrap gap-3 mb-8">
        <a href="education.php<?= $search ? '?search='.urlencode($search) : '' ?>"
           class="px-6 py-2 rounded-full font-semibold text-sm transition <?= !$category ? 'gradient-primary text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-purple-50' ?>">
          الكل
        </a>
        <?php foreach ($cat_map as $key => $label): ?>
        <a href="education.php?category=<?= $key ?><?= $search ? '&search='.urlencode($search) : '' ?>"
           class="px-6 py-2 rounded-full font-semibold text-sm transition <?= $category === $key ? 'gradient-primary text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-purple-50' ?>">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>

      
      <?php if (empty($articles)): ?>
      <div class="text-center py-16">
        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="book-open" class="w-8 h-8 text-purple-400"></i>
        </div>
        <p class="text-gray-500">لا توجد مقالات <?= $search ? 'تطابق بحثك' : 'في هذه الفئة' ?> بعد</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($articles as $idx => $article):
          $catKey   = $article['category'] ?? 'awareness';
          $catLabel = $cat_map[$catKey] ?? $catKey;
          $catCls   = $cat_color[$catKey] ?? 'bg-gray-100 text-gray-700';
          $excerpt  = mb_substr(strip_tags($article['content'] ?? ''), 0, 100, 'UTF-8');
          $imgSrc   = !empty($article['image']) ? '../' . htmlspecialchars($article['image']) : '../assets/img/1.jpg';
          $delay    = ($idx % 6) * 0.1;
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all animate-fadeInUp"
             style="animation-delay:<?= $delay ?>s;opacity:0">
          <div class="h-48 overflow-hidden bg-gray-100">
            <img src="<?= $imgSrc ?>"
                 onerror="this.src='../assets/img/1.jpg'"
                 alt="<?= htmlspecialchars($article['title']) ?>"
                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
          </div>
          <div class="p-6">
            <div class="flex items-center gap-2 mb-3">
              <span class="<?= $catCls ?> px-3 py-1 rounded-full text-xs font-semibold"><?= $catLabel ?></span>
              <?php if (!empty($article['read_time'])): ?>
              <span class="text-xs text-gray-400"><?= htmlspecialchars($article['read_time']) ?> دقائق قراءة</span>
              <?php endif; ?>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($article['title']) ?></h3>
            <p class="text-gray-600 text-sm mb-4 leading-relaxed"><?= htmlspecialchars($excerpt) ?>...</p>
            <button onclick="openArticle(<?= $article['id'] ?>)"
                    class="text-purple-600 font-semibold text-sm hover:text-purple-800 flex items-center gap-1 transition-colors">
              اقرأ المزيد
              <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>

  
  <div id="articleModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
      <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
        <h3 id="modalTitle" class="text-lg font-bold text-gray-800"></h3>
        <button onclick="closeArticle()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="p-6">
        <div id="modalImage" class="mb-5 rounded-xl overflow-hidden hidden">
          <img id="modalImg" src="" alt="" class="w-full h-56 object-cover">
        </div>
        <div id="modalMeta" class="flex items-center gap-3 mb-4"></div>
        <div id="modalContent" class="text-gray-700 text-sm leading-relaxed prose prose-sm max-w-none"></div>
      </div>
    </div>
  </div>

  <?php
  $articlesJson = json_encode(array_map(function($a) {
    return [
      'id'       => (int)$a['id'],
      'title'    => $a['title'],
      'content'  => $a['content'],
      'category' => $a['category'] ?? '',
      'image'    => $a['image'] ?? '',
      'read_time'=> $a['read_time'] ?? '',
    ];
  }, $articles), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  ?>

  <script>
    lucide.createIcons();

    const articles = <?= $articlesJson ?>;
    const catMap   = { awareness:'توعوي', guidance:'إرشادي', medical:'طبي' };
    const catColor = { awareness:'bg-blue-100 text-blue-700', guidance:'bg-green-100 text-green-700', medical:'bg-purple-100 text-purple-700' };

    function openArticle(id) {
      const a = articles.find(x => x.id == id);
      if (!a) return;
      document.getElementById('modalTitle').textContent = a.title;

      const imgWrap = document.getElementById('modalImage');
      const img     = document.getElementById('modalImg');
      if (a.image) {
        img.src = '../' + a.image;
        img.alt = a.title;
        img.onerror = function() { this.src = '../assets/img/1.jpg'; };
        imgWrap.classList.remove('hidden');
      } else {
        imgWrap.classList.add('hidden');
      }

      const meta = document.getElementById('modalMeta');
      const catLabel = catMap[a.category] || a.category;
      const catCls   = catColor[a.category] || 'bg-gray-100 text-gray-700';
      meta.innerHTML = '<span class="' + catCls + ' px-3 py-1 rounded-full text-xs font-semibold">' + catLabel + '</span>' +
        (a.read_time ? '<span class="text-xs text-gray-400">' + a.read_time + ' دقائق قراءة</span>' : '');

      document.getElementById('modalContent').innerHTML = a.content;

      const modal = document.getElementById('articleModal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      lucide.createIcons();
    }

    function closeArticle() {
      const modal = document.getElementById('articleModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    document.getElementById('articleModal').addEventListener('click', function(e) {
      if (e.target === this) closeArticle();
    });
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
