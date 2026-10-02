<?php

function notificationBell(mysqli $conn, int $userId): void {
    $count = getUnreadNotificationCount($conn, $userId);
    ?>
    <button id="bellBtn" type="button" class="relative p-2 text-gray-500 hover:text-purple-600 transition-colors" aria-label="الإشعارات">
      <i data-lucide="bell" class="w-6 h-6"></i>
      <?php if ($count > 0): ?>
      <span id="notifBadge" class="absolute -top-1 -left-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold leading-none rounded-full border-2 border-white shadow-sm"><?= $count > 99 ? '99+' : (int)$count ?></span>
      <?php endif; ?>
    </button>
    <?php
}

function notificationScripts(string $apiBase = '../api'): void {
    $api = json_encode(rtrim($apiBase, '/'), JSON_UNESCAPED_UNICODE);
    ?>
<style>
  #notifDropdown .notif-item.unread { background: #faf5ff; }
  #notifDropdown .notif-item.read { opacity: 0.75; }
  #notifAllModal { display: none; }
  #notifAllModal.is-open { display: flex; }
</style>
<script>
(function() {
    const API = <?= $api ?>;
    const bellBtn = document.getElementById('bellBtn');
    if (!bellBtn) return;

    function parseJsonResponse(r) {
        return r.text().then(function(t) {
            return JSON.parse(t.replace(/^\uFEFF+/, '').trim());
        });
    }

    function escapeHtml(text) {
        const el = document.createElement('div');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return '';
        }
    }

    function updateBadge(count) {
        let badge = document.getElementById('notifBadge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.id = 'notifBadge';
                badge.className = 'absolute -top-1 -left-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold leading-none rounded-full border-2 border-white shadow-sm';
                bellBtn.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = '';
        } else if (badge) {
            badge.remove();
        }
    }

    function refreshBadge() {
        fetch(API + '/notification_count.php')
            .then(parseJsonResponse)
            .then(function(data) {
                if (data && typeof data.count === 'number') updateBadge(data.count);
            })
            .catch(function() {});
    }

    function markOneRead(id) {
        const body = new URLSearchParams();
        body.append('id', String(id));
        return fetch(API + '/mark_notification_read.php', { method: 'POST', body: body })
            .then(parseJsonResponse)
            .then(function(data) {
                if (data && typeof data.count === 'number') updateBadge(data.count);
                return data;
            });
    }

    function notifItemHtml(n) {
        const isRead = parseInt(n.is_read, 10) === 1;
        const link = n.link || '#';
        const body = n.body || '';
        const cls = 'notif-item flex items-start gap-3 p-4 hover:bg-purple-50 border-b border-gray-50 transition cursor-pointer ' + (isRead ? 'read' : 'unread');
        return '<div class="' + cls + '" data-id="' + n.id + '" data-link="' + escapeHtml(link) + '" role="button" tabindex="0">' +
            '<div class="w-8 h-8 gradient-primary rounded-full flex items-center justify-center flex-shrink-0">' +
            '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div>' +
            '<div class="flex-1 min-w-0">' +
            '<p class="text-sm font-semibold text-gray-800">' + escapeHtml(n.title) + '</p>' +
            '<p class="text-xs text-gray-500 truncate">' + escapeHtml(body) + '</p>' +
            '<p class="text-[10px] text-gray-400 mt-0.5">' + escapeHtml(formatTime(n.created_at)) + '</p>' +
            '</div></div>';
    }

    function bindNotifClicks(container) {
        container.querySelectorAll('.notif-item').forEach(function(el) {
            function go() {
                const id = parseInt(el.getAttribute('data-id'), 10);
                const link = el.getAttribute('data-link') || '#';
                if (!id) return;
                markOneRead(id).finally(function() {
                    if (link && link !== '#') window.location.href = link;
                });
            }
            el.addEventListener('click', go);
            el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });
    }

    function renderList(container, notifs, emptyMsg) {
        if (!notifs || !notifs.length) {
            container.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">' + emptyMsg + '</div>';
            return;
        }
        container.innerHTML = notifs.map(notifItemHtml).join('');
        bindNotifClicks(container);
    }

    const dropdown = document.createElement('div');
    dropdown.id = 'notifDropdown';
    dropdown.className = 'absolute top-12 left-0 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 hidden flex flex-col';
    dropdown.innerHTML =
        '<div class="p-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">' +
        '<span class="font-bold text-gray-800">الإشعارات</span>' +
        '<button type="button" id="markAllReadBtn" class="text-xs text-purple-600 hover:underline">تحديد الكل كمقروء</button></div>' +
        '<div id="notifList" class="max-h-64 overflow-y-auto flex-1"><div class="p-4 text-center text-gray-400 text-sm">جاري التحميل...</div></div>' +
        '<div class="p-3 border-t border-gray-100 flex-shrink-0">' +
        '<button type="button" id="viewAllNotifBtn" class="w-full text-center text-sm font-semibold text-purple-600 hover:text-purple-800 py-2 rounded-xl hover:bg-purple-50 transition">عرض الكل</button></div>';
    bellBtn.style.position = 'relative';
    bellBtn.appendChild(dropdown);

    const modal = document.createElement('div');
    modal.id = 'notifAllModal';
    modal.className = 'fixed inset-0 z-[100] items-center justify-center p-4 bg-black/50';
    modal.innerHTML =
        '<div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" dir="rtl">' +
        '<div class="p-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">' +
        '<h3 class="font-bold text-gray-800 text-lg">كل الإشعارات</h3>' +
        '<div class="flex items-center gap-3">' +
        '<button type="button" id="markAllReadModalBtn" class="text-xs text-purple-600 hover:underline">تحديد الكل كمقروء</button>' +
        '<button type="button" id="closeNotifModalBtn" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>' +
        '</div></div>' +
        '<div id="notifAllList" class="overflow-y-auto flex-1"><div class="p-4 text-center text-gray-400 text-sm">جاري التحميل...</div></div></div>';
    document.body.appendChild(modal);

    function loadNotifications() {
        fetch(API + '/notifications.php')
            .then(parseJsonResponse)
            .then(function(notifs) {
                const list = document.getElementById('notifList');
                if (list) renderList(list, notifs, 'لا توجد إشعارات جديدة');
            })
            .catch(function() {
                const list = document.getElementById('notifList');
                if (list) list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">حدث خطأ في التحميل</div>';
            });
    }

    function loadAllNotifications() {
        const list = document.getElementById('notifAllList');
        if (!list) return;
        list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">جاري التحميل...</div>';
        fetch(API + '/notifications.php?all=1&limit=50')
            .then(parseJsonResponse)
            .then(function(notifs) {
                renderList(list, notifs, 'لا توجد إشعارات');
            })
            .catch(function() {
                list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">حدث خطأ في التحميل</div>';
            });
    }

    function openAllModal() {
        modal.classList.add('is-open');
        dropdown.classList.add('hidden');
        loadAllNotifications();
    }

    function closeAllModal() {
        modal.classList.remove('is-open');
    }

    function markAllRead() {
        fetch(API + '/mark_notifications_read.php', { method: 'POST' })
            .then(parseJsonResponse)
            .then(function() {
                const list = document.getElementById('notifList');
                const allList = document.getElementById('notifAllList');
                if (list) list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">لا توجد إشعارات جديدة</div>';
                if (allList) allList.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">لا توجد إشعارات جديدة</div>';
                updateBadge(0);
            });
    }

    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) loadNotifications();
    });

    document.getElementById('markAllReadBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        markAllRead();
    });

    document.getElementById('viewAllNotifBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openAllModal();
    });

    document.getElementById('closeNotifModalBtn').addEventListener('click', closeAllModal);
    document.getElementById('markAllReadModalBtn').addEventListener('click', markAllRead);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeAllModal();
    });

    modal.querySelector('.bg-white').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== bellBtn && !bellBtn.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAllModal();
    });

    refreshBadge();
    setInterval(refreshBadge, 30000);
})();
</script>
    <?php
}
