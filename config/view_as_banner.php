<?php if (isViewingAs()): ?>
<div class="fixed bottom-0 left-0 right-0 z-[9999] bg-yellow-400 text-gray-900 px-6 py-3 flex items-center justify-between shadow-2xl">
  <div class="flex items-center gap-3">
    <div class="w-8 h-8 bg-gray-900 rounded-full flex items-center justify-center">
      <i data-lucide="eye" class="w-4 h-4 text-yellow-400"></i>
    </div>
    <span class="font-bold text-sm">أنت تشاهد المنصة كـ: <strong><?= htmlspecialchars($_SESSION['view_as']['name']) ?></strong></span>
    <span class="text-xs bg-gray-900 text-yellow-400 px-2 py-0.5 rounded-full font-semibold">
      <?= $_SESSION['view_as']['role'] === 'doctor' ? 'طبيب' : 'ولي أمر' ?>
    </span>
  </div>
  <a href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : '../admin/' ?>exit_view.php" class="flex items-center gap-2 bg-gray-900 text-yellow-400 px-4 py-2 rounded-xl font-bold text-sm hover:bg-gray-800 transition">
    <i data-lucide="x" class="w-4 h-4"></i>
    الخروج من وضع العرض
  </a>
</div>
<?php endif; ?>
