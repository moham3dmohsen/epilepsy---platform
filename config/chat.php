<?php

function ensureChatSchema(mysqli $conn): void {
    static $done = false;
    if ($done) {
        return;
    }
    $check = $conn->query("SHOW COLUMNS FROM messages LIKE 'msg_type'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN msg_type ENUM('text','image','file') NOT NULL DEFAULT 'text' AFTER content");
        $conn->query("ALTER TABLE messages ADD COLUMN file_path VARCHAR(500) NULL DEFAULT NULL AFTER msg_type");
        $conn->query("ALTER TABLE messages ADD COLUMN file_name VARCHAR(255) NULL DEFAULT NULL AFTER file_path");
    }
    $done = true;
}

function sanitizeChatText(string $input): string {
    return trim($input);
}

function uploadChatAttachment(array $file): array {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return [];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileMimes  = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
    ];

    $msgType = 'file';
    if (in_array($mime, $imageMimes, true)) {
        $msgType = 'image';
    } elseif (!in_array($mime, $fileMimes, true)) {
        return [];
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('chat_', true) . ($ext ? '.' . strtolower($ext) : '');
    $dir      = __DIR__ . '/../uploads/chat/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dest = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [];
    }

    return [
        'msg_type'  => $msgType,
        'file_path' => 'uploads/chat/' . $filename,
        'file_name' => basename($file['name']),
    ];
}

function chatMessagePreview(array $msg): string {
    $type = $msg['msg_type'] ?? 'text';
    if ($type === 'image') {
        return '📷 صورة';
    }
    if ($type === 'file') {
        return '📎 ' . ($msg['file_name'] ?? 'ملف');
    }
    $text = $msg['content'] ?? '';
    return mb_strlen($text) > 40 ? mb_substr($text, 0, 40, 'UTF-8') . '...' : $text;
}

function renderChatMessageBody(array $msg, bool $isMine, string $base = '../'): string {
    $type = $msg['msg_type'] ?? 'text';
    $textClass = $isMine ? 'text-white text-sm' : 'text-gray-800 text-sm';
    $linkClass = $isMine ? 'text-white/90 underline' : 'text-purple-600 underline';

    if ($type === 'image' && !empty($msg['file_path'])) {
        $src = htmlspecialchars($base . $msg['file_path']);
        $dl  = htmlspecialchars($base . 'api/download_message_file.php?id=' . (int)$msg['id']);
        $cap = !empty($msg['content']) ? '<p class="' . $textClass . ' mt-2">' . nl2br(htmlspecialchars($msg['content'])) . '</p>' : '';
        return '<a href="' . $dl . '" target="_blank" rel="noopener"><img src="' . $src . '" alt="صورة" class="max-w-xs rounded-xl max-h-64 object-cover"></a>' . $cap
            . '<p class="mt-1"><a href="' . $dl . '" class="text-xs ' . $linkClass . '">تنزيل الصورة</a></p>';
    }

    if ($type === 'file' && !empty($msg['file_path'])) {
        $dl   = htmlspecialchars($base . 'api/download_message_file.php?id=' . (int)$msg['id']);
        $name = htmlspecialchars($msg['file_name'] ?? 'ملف مرفق');
        $cap  = !empty($msg['content']) ? '<p class="' . $textClass . ' mb-2">' . nl2br(htmlspecialchars($msg['content'])) . '</p>' : '';
        return $cap . '<a href="' . $dl . '" class="flex items-center gap-2 ' . $textClass . ' bg-black/10 rounded-lg px-3 py-2 hover:opacity-90">'
            . '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
            . '<span class="text-sm font-semibold truncate">' . $name . '</span></a>';
    }

    return '<p class="' . $textClass . '">' . nl2br(htmlspecialchars($msg['content'] ?? '')) . '</p>';
}

function renderChatComposer(int $withId): void {
    ?>
    <form id="chatForm" method="POST" enctype="multipart/form-data" class="px-6 py-4 bg-white border-t border-gray-100 flex-shrink-0">
      <input type="hidden" name="receiver_id" value="<?= $withId ?>">
      <input type="file" id="chatFileInput" name="attachment" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
      <div id="emojiPicker" class="hidden mb-3 p-3 bg-gray-50 border border-gray-200 rounded-xl max-h-32 overflow-y-auto flex flex-wrap gap-1 text-xl"></div>
      <div id="filePreview" class="hidden mb-2 flex items-center gap-2 text-sm text-gray-600 bg-purple-50 px-3 py-2 rounded-xl">
        <span id="filePreviewName" class="flex-1 truncate"></span>
        <button type="button" id="clearFileBtn" class="text-red-500 hover:text-red-700 text-xs font-semibold">إلغاء</button>
      </div>
      <div class="flex items-end gap-2">
        <button type="button" id="emojiBtn" class="p-3 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-xl transition flex-shrink-0" title="إيموجي">
          <i data-lucide="smile" class="w-5 h-5"></i>
        </button>
        <button type="button" id="attachBtn" class="p-3 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-xl transition flex-shrink-0" title="مرفق">
          <i data-lucide="paperclip" class="w-5 h-5"></i>
        </button>
        <input type="text" name="content" id="messageInput" placeholder="اكتب رسالتك..." autocomplete="off"
               class="flex-1 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-gray-800 text-sm">
        <button type="submit" id="chatSendBtn" class="gradient-primary text-white p-3 rounded-xl hover:opacity-90 transition flex-shrink-0">
          <i data-lucide="send" class="w-5 h-5"></i>
        </button>
      </div>
    </form>
    <?php
}
