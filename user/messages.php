<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/functions.php';
requireLogin('parent');
$user = refreshCurrentUser($conn);
if (empty($user['name'])) { $r = $conn->query("SELECT name, profile_picture FROM users WHERE id=" . (int)$_SESSION['user_id']); if ($r && $row = $r->fetch_assoc()) { $user['name'] = $row['name']; $user['profile_picture'] = $row['profile_picture']; } }

ensureChatSchema($conn);

$doctors = $conn->query("SELECT id, name, profile_picture FROM users WHERE role='doctor' AND status='active' AND (doctor_status='approved' OR doctor_status IS NULL)")->fetch_all(MYSQLI_ASSOC);

$with_id    = (int)($_GET['with'] ?? ($doctors[0]['id'] ?? 0));
$chat_messages = [];
$chat_user  = null;

if ($with_id) {
    $s2 = $conn->prepare("SELECT * FROM users WHERE id=?");
    $s2->bind_param("i", $with_id);
    $s2->execute();
    $chat_user = $s2->get_result()->fetch_assoc();

    $s3 = $conn->prepare("SELECT m.*, u.name as sender_name, u.profile_picture as sender_pic FROM messages m JOIN users u ON m.sender_id=u.id WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?) ORDER BY m.created_at ASC");
    $s3->bind_param("iiii", $user['id'], $with_id, $with_id, $user['id']);
    $s3->execute();
    $chat_messages = $s3->get_result()->fetch_all(MYSQLI_ASSOC);

    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$with_id AND receiver_id=" . (int)$user['id']);
}

$unread_per = [];
foreach ($doctors as $doc) {
    $sq = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
    $sq->bind_param("ii", $doc['id'], $user['id']);
    $sq->execute();
    $unread_per[$doc['id']] = $sq->get_result()->fetch_assoc()['cnt'];
}

$last_msg = [];
foreach ($doctors as $doc) {
    $lq = $conn->prepare("SELECT content, created_at FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at DESC LIMIT 1");
    $lq->bind_param("iiii", $user['id'], $doc['id'], $doc['id'], $user['id']);
    $lq->execute();
    $last_msg[$doc['id']] = $lq->get_result()->fetch_assoc();
}

$stmt_unread = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id=? AND is_read=0");
$stmt_unread->bind_param("i", $user['id']);
$stmt_unread->execute();
$unread_total = $stmt_unread->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>رسائل الطبيب - منصة توعية لمرض الصرع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    * { font-family: 'Cairo', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #8F00FF, #5500CC); }
    .sidebar { background: linear-gradient(180deg, #8F00FF 0%, #5500CC 100%); }
    .active-link { background: rgba(255,255,255,0.25) !important; color: white !important; }
    .chat-height { height: calc(100vh - 73px); }
    .chat-panel { min-height: 0; }
    .messages-area { min-height: 0; }
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
      <a href="messages.php" class="active-link flex items-center gap-3 px-4 py-3 rounded-xl text-white transition-all">
        <i data-lucide="message-circle" class="w-5 h-5"></i><span>رسائل الطبيب</span>
      </a>
      <a href="education.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/20 hover:text-white transition-all">
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

  
  <main class="mr-64 h-screen flex flex-col overflow-hidden">
    <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between sticky top-0 z-40">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">رسائل الطبيب</h1>
        <p class="text-sm text-gray-500">تواصل مع طبيبك المعالج</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($user['name']) ?></span>
        <?php notificationBell($conn, (int)$user['id']); ?>
        <?= avatarHtml($user, 'md', '../') ?>
      </div>
    </header>

    <div class="flex flex-1 min-h-0 overflow-hidden chat-height">

      
      <div class="w-72 bg-white border-l border-gray-100 flex flex-col flex-shrink-0 min-h-0">
        <div class="flex-1 min-h-0 overflow-y-auto">
        <div class="p-4 border-b border-gray-100 flex-shrink-0">
          <h2 class="font-bold text-gray-800 text-sm">الأطباء</h2>
        </div>
        <?php if (empty($doctors)): ?>
        <div class="p-6 text-center text-gray-400 text-sm">لا يوجد أطباء متاحون</div>
        <?php else: ?>
        <?php foreach ($doctors as $doc):
          $isActive = ($doc['id'] == $with_id);
          $lm = $last_msg[$doc['id']] ?? null;
          $ub = $unread_per[$doc['id']] ?? 0;
        ?>
        <a href="messages.php?with=<?= $doc['id'] ?>" data-contact-id="<?= $doc['id'] ?>"
           class="flex items-center gap-3 px-4 py-4 border-b border-gray-50 hover:bg-purple-50 transition-colors <?= $isActive ? 'bg-purple-50 border-r-4 border-r-purple-600' : '' ?>">
          <?= avatarHtml($doc, 'md', '../') ?>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="font-semibold text-gray-800 text-sm truncate">د. <?= htmlspecialchars($doc['name']) ?></p>
              <?php if ($ub > 0): ?>
              <span class="bg-purple-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0"><?= $ub ?></span>
              <?php endif; ?>
            </div>
            <?php if ($lm): ?>
            <p class="text-xs text-gray-400 truncate mt-0.5 contact-preview"><?= htmlspecialchars(mb_substr($lm['content'], 0, 35, 'UTF-8')) ?>...</p>
            <?php else: ?>
            <p class="text-xs text-gray-400 mt-0.5 contact-preview">ابدأ المحادثة</p>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
      </div>

      
      <div class="chat-panel flex flex-col flex-1 bg-gray-50 overflow-hidden min-h-0">
        <?php if ($chat_user): ?>

        
        <div class="flex items-center gap-4 px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
          <?= avatarHtml($chat_user, 'lg', '../') ?>
          <div>
            <p class="font-bold text-gray-800">د. <?= htmlspecialchars($chat_user['name']) ?></p>
            <p class="text-xs text-green-500 flex items-center gap-1">
              <span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span>
              طبيب متخصص
            </p>
          </div>
        </div>

        
        <div id="messagesArea" class="messages-area flex-1 p-6 space-y-4 overflow-y-auto overflow-x-hidden">
          <?php if (empty($chat_messages)): ?>
          <div id="emptyChatState" class="text-center py-12">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i data-lucide="message-circle" class="w-8 h-8 text-purple-400"></i>
            </div>
            <p class="text-gray-400 text-sm">ابدأ المحادثة مع الطبيب</p>
          </div>
          <?php else: ?>
          <?php foreach ($chat_messages as $msg):
            $isMine = ($msg['sender_id'] == $user['id']);
            $time   = date('h:i A', strtotime($msg['created_at']));
            $myPic  = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : null;
            $otherPic = !empty($chat_user['profile_picture']) ? '../' . $chat_user['profile_picture'] : null;
          ?>
          <?php if ($isMine): ?>
          <div class="flex items-end gap-3 flex-row-reverse" data-msg-id="<?= $msg['id'] ?>">
            <?php if ($myPic): ?>
              <img src="<?= htmlspecialchars($myPic) ?>" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border-2 border-purple-200">
            <?php else: ?>
              <div class="w-8 h-8 gradient-primary rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?= htmlspecialchars(mb_substr($user['name'], 0, 1, 'UTF-8')) ?></div>
            <?php endif; ?>
            <div class="max-w-md">
              <div class="gradient-primary rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
                <?= renderChatMessageBody($msg, true, '../') ?>
              </div>
              <p class="text-xs text-gray-400 mt-1 ml-1 text-left"><?= $time ?></p>
            </div>
          </div>
          <?php else: ?>
          <div class="flex items-end gap-3" data-msg-id="<?= $msg['id'] ?>">
            <?php if ($otherPic): ?>
              <img src="<?= htmlspecialchars($otherPic) ?>" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border-2 border-gray-200">
            <?php else: ?>
              <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?= htmlspecialchars(mb_substr($chat_user['name'], 0, 1, 'UTF-8')) ?></div>
            <?php endif; ?>
            <div class="max-w-md">
              <div class="bg-white rounded-2xl rounded-br-sm px-4 py-3 shadow-sm border border-gray-100">
                <?= renderChatMessageBody($msg, false, '../') ?>
              </div>
              <p class="text-xs text-gray-400 mt-1 mr-1"><?= $time ?></p>
            </div>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        
        <?php renderChatComposer($with_id); ?>

        <?php else: ?>
        <div class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i data-lucide="message-circle" class="w-10 h-10 text-purple-400"></i>
            </div>
            <p class="text-gray-500">اختر طبيباً لبدء المحادثة</p>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <script src="../assets/js/chat.js"></script>
  <script>
    lucide.createIcons();
    initChat({
      apiBase: '../api',
      basePath: '../',
      withId: <?= $with_id ?>,
      myId: <?= $user['id'] ?>,
      myName: <?= json_encode($user['name'], JSON_UNESCAPED_UNICODE) ?>,
      otherInitial: <?= json_encode(mb_substr($chat_user['name'] ?? 'د', 0, 1, 'UTF-8'), JSON_UNESCAPED_UNICODE) ?>,
      myPic: <?= json_encode(!empty($user['profile_picture']) ? '../' . $user['profile_picture'] : null, JSON_UNESCAPED_UNICODE) ?>,
      otherPic: <?= json_encode(!empty($chat_user['profile_picture']) ? '../' . $chat_user['profile_picture'] : null, JSON_UNESCAPED_UNICODE) ?>,
      lastMsgId: <?= !empty($chat_messages) ? (int)end($chat_messages)['id'] : 0 ?>
    });
  </script>
<?php notificationScripts('../api'); require_once '../config/view_as_banner.php'; ?>
</body>
</html>
