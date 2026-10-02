<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('admin');
$user = refreshCurrentUser($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = sanitize($_POST['title']);
        $desc  = sanitize($_POST['description']);
        $url   = sanitize($_POST['youtube_url']);
        $cat   = $_POST['category'];
        $thumb = '';
        if (!empty($_FILES['thumbnail']['name'])) {
            $thumb = uploadImage($_FILES['thumbnail'], 'videos');
        }
        $status = newContentStatus($conn);
        $stmt = $conn->prepare("INSERT INTO videos (admin_id,title,description,youtube_url,thumbnail,category,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $_SESSION['user_id'], $title, $desc, $url, $thumb, $cat, $status);
        $stmt->execute();
        if ($status === 'published') {
            notifyParents($conn, 'system', 'فيديو تعليمي جديد', "تم نشر فيديو: $title", '../user/videos.php');
        }

    } elseif ($action === 'publish') {
        $id = (int)$_POST['id'];
        $row = $conn->query("SELECT title, status FROM videos WHERE id=$id")->fetch_assoc();
        if ($row && ($row['status'] ?? 'published') !== 'published') {
            $conn->query("UPDATE videos SET status='published' WHERE id=$id");
            notifyParents($conn, 'system', 'فيديو تعليمي جديد', 'تم نشر فيديو: ' . $row['title'], '../user/videos.php');
        }

    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $title = sanitize($_POST['title']);
        $desc  = sanitize($_POST['description']);
        $url   = sanitize($_POST['youtube_url']);
        $cat   = $_POST['category'];
        $thumb = sanitize($_POST['existing_thumb']);
        if (!empty($_FILES['thumbnail']['name'])) {
            $thumb = uploadImage($_FILES['thumbnail'], 'videos');
        }
        $stmt = $conn->prepare("UPDATE videos SET title=?,description=?,youtube_url=?,thumbnail=?,category=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $desc, $url, $thumb, $cat, $id);
        $stmt->execute();

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM videos WHERE id=$id");
    }

    header('Location: videos.php');
    exit;
}

$videos = $conn->query("SELECT v.*, u.name as admin_name FROM videos v JOIN users u ON v.admin_id=u.id ORDER BY v.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$cat_map   = ['awareness' => 'توعية', 'diagnostic' => 'تشخيص', 'treatment' => 'علاج'];
$cat_color = ['awareness' => 'bg-blue-100 text-blue-700', 'diagnostic' => 'bg-purple-100 text-purple-700', 'treatment' => 'bg-green-100 text-green-700'];

function ytThumb($url) {
    preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m);
    return isset($m[1]) ? 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg' : '';
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
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    .modal-overlay { background: rgba(0,0,0,0.5); }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #8F00FF; border-radius: 3px; }
  </style>
</head>
<body class="bg-gray-50">

  
  <aside class="sidebar w-64 min-h-screen fixed right-0 top-0 z-50 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/20">
      <div class="flex items-center gap-3">
        <img src="../assets/img/logo1.png" class="h-10 w-auto">
        <span class="text-white text-sm font-bold leading-tight">منصة توعية<br>لمرض الصرع</span>
      </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>لوحة التحكم</span></a>
      <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="users" class="w-5 h-5"></i><span>المستخدمون</span></a>
      <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="mail" class="w-5 h-5"></i><span>رسائل التواصل</span></a>
      <a href="videos.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all"><i data-lucide="video" class="w-5 h-5"></i><span>الفيديوهات</span></a>
      <a href="content.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="book-open" class="w-5 h-5"></i><span>المحتوى التعليمي</span></a>
      <a href="reports.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>التقارير</span></a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="settings" class="w-5 h-5"></i><span>الإعدادات</span></a>
      <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all"><i data-lucide="user-circle" class="w-5 h-5"></i><span>الملف الشخصي</span></a>
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
        <h1 class="text-2xl font-bold text-gray-800">إدارة الفيديوهات</h1>
        <p class="text-sm text-gray-500"><?= count($videos) ?> فيديو</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <button onclick="openAddModal()" class="gradient-primary text-white px-5 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:opacity-90 transition-opacity">
          <i data-lucide="plus" class="w-4 h-4"></i> إضافة فيديو
        </button>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="p-8">
      <?php if (empty($videos)): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
        <div class="w-20 h-20 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="video-off" class="w-10 h-10 text-purple-300"></i>
        </div>
        <p class="text-gray-500 text-lg font-semibold">لا توجد فيديوهات بعد</p>
        <p class="text-gray-400 text-sm mt-1">ابدأ بإضافة أول فيديو تعليمي</p>
        <button onclick="openAddModal()" class="mt-4 gradient-primary text-white px-6 py-2.5 rounded-xl font-semibold hover:opacity-90">إضافة فيديو</button>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($videos as $v): ?>
        <?php
          $thumb = !empty($v['thumbnail']) ? '../' . $v['thumbnail'] : ytThumb($v['youtube_url']);
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group">
          
          <div class="relative h-44 bg-gray-100 overflow-hidden">
            <?php if ($thumb): ?>
            <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($v['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-100 to-purple-200">
              <i data-lucide="video" class="w-12 h-12 text-purple-400"></i>
            </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <a href="<?= htmlspecialchars($v['youtube_url']) ?>" target="_blank" class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg">
                <i data-lucide="play" class="w-5 h-5 text-purple-600 mr-0.5"></i>
              </a>
            </div>
            <span class="absolute top-3 right-3 <?= $cat_color[$v['category']] ?? 'bg-gray-100 text-gray-700' ?> text-xs px-2 py-1 rounded-full font-semibold">
              <?= $cat_map[$v['category']] ?? $v['category'] ?>
            </span>
            <?php if (($v['status'] ?? 'published') === 'draft'): ?>
            <span class="absolute top-3 left-3 bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded-full font-semibold">مسودة</span>
            <?php endif; ?>
          </div>
          
          <div class="p-5">
            <h3 class="font-bold text-gray-800 mb-1 line-clamp-1"><?= htmlspecialchars($v['title']) ?></h3>
            <p class="text-gray-500 text-sm line-clamp-2 mb-3"><?= htmlspecialchars($v['description'] ?? '') ?></p>
            <div class="flex items-center justify-between text-xs text-gray-400">
              <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> <?= number_format($v['views']) ?> مشاهدة</span>
              <span><?= date('d M Y', strtotime($v['created_at'])) ?></span>
            </div>
            <div class="flex gap-2 mt-4 flex-wrap">
              <?php if (($v['status'] ?? 'published') === 'draft'): ?>
              <form method="POST" class="flex-1 min-w-[120px]">
                <input type="hidden" name="action" value="publish">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                <button type="submit" class="w-full flex items-center justify-center gap-1 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors text-sm font-semibold">
                  <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> نشر
                </button>
              </form>
              <?php endif; ?>
              <button onclick='openEditModal(<?= json_encode($v) ?>)' class="flex-1 min-w-[120px] flex items-center justify-center gap-1 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm font-semibold">
                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> تعديل
              </button>
              <button onclick="confirmDelete(<?= $v['id'] ?>, '<?= htmlspecialchars($v['title'], ENT_QUOTES) ?>')" class="flex-1 min-w-[120px] flex items-center justify-center gap-1 py-2 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors text-sm font-semibold">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> حذف
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>

  
  <div id="addModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">إضافة فيديو جديد</h3>
        <button onclick="closeAddModal()" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">عنوان الفيديو</label>
            <input type="text" name="title" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الوصف</label>
            <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"></textarea>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رابط يوتيوب</label>
            <input type="url" name="youtube_url" required placeholder="https://www.youtube.com/watch?v=..." class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">التصنيف</label>
            <select name="category" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              <option value="awareness">توعية</option>
              <option value="diagnostic">تشخيص</option>
              <option value="treatment">علاج</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">صورة مصغرة (اختياري)</label>
            <input type="file" name="thumbnail" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button type="submit" class="flex-1 gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90">إضافة</button>
          <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="editModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">تعديل الفيديو</h3>
        <button onclick="closeEditModal()" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="existing_thumb" id="edit_existing_thumb">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">عنوان الفيديو</label>
            <input type="text" name="title" id="edit_title" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الوصف</label>
            <textarea name="description" id="edit_desc" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"></textarea>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رابط يوتيوب</label>
            <input type="url" name="youtube_url" id="edit_url" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">التصنيف</label>
            <select name="category" id="edit_cat" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400">
              <option value="awareness">توعية</option>
              <option value="diagnostic">تشخيص</option>
              <option value="treatment">علاج</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">صورة مصغرة جديدة (اختياري)</label>
            <input type="file" name="thumbnail" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button type="submit" class="flex-1 gradient-primary text-white py-3 rounded-xl font-semibold hover:opacity-90">حفظ التغييرات</button>
          <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="deleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center">
      <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i data-lucide="trash-2" class="w-8 h-8 text-red-500"></i>
      </div>
      <h3 class="text-xl font-bold text-gray-800 mb-2">تأكيد الحذف</h3>
      <p class="text-gray-500 mb-6">هل أنت متأكد من حذف الفيديو <strong id="delete_name"></strong>؟</p>
      <form method="POST">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <div class="flex gap-3">
          <button type="submit" class="flex-1 bg-red-500 text-white py-3 rounded-xl font-semibold hover:bg-red-600">حذف</button>
          <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200">إلغاء</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    lucide.createIcons();

    function openAddModal() {
      document.getElementById('addModal').classList.remove('hidden');
      document.getElementById('addModal').classList.add('flex');
    }
    function closeAddModal() {
      document.getElementById('addModal').classList.add('hidden');
      document.getElementById('addModal').classList.remove('flex');
    }
    function openEditModal(v) {
      document.getElementById('edit_id').value             = v.id;
      document.getElementById('edit_title').value          = v.title;
      document.getElementById('edit_desc').value           = v.description || '';
      document.getElementById('edit_url').value            = v.youtube_url;
      document.getElementById('edit_cat').value            = v.category;
      document.getElementById('edit_existing_thumb').value = v.thumbnail || '';
      document.getElementById('editModal').classList.remove('hidden');
      document.getElementById('editModal').classList.add('flex');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
      document.getElementById('editModal').classList.remove('flex');
    }
    function confirmDelete(id, name) {
      document.getElementById('delete_id').value = id;
      document.getElementById('delete_name').textContent = name;
      document.getElementById('deleteModal').classList.remove('hidden');
      document.getElementById('deleteModal').classList.add('flex');
    }
    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
      document.getElementById('deleteModal').classList.remove('flex');
    }
    ['addModal','editModal','deleteModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.add('hidden');
          this.classList.remove('flex');
        }
      });
    });
  </script>
  <?php notificationScripts('../api'); ?>
</body>
</html>
