(function(global) {
    const EMOJIS = '😀 😃 😄 😁 😊 🥰 😍 🤩 😘 😉 😎 🥳 😢 😭 😡 👍 👎 ❤️ 🙏 👋 🎉 ✨ 🔥 💯 💜 🏥 💊 📎 📷 ✅ ❌ ⭐ 🌟'.split(' ');

    function escapeHtml(text) {
        const el = document.createElement('div');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function parseJsonResponse(r) {
        return r.text().then(function(t) {
            return JSON.parse(t.replace(/^\uFEFF+/, '').trim());
        });
    }

    function initChat(options) {
        const apiBase = options.apiBase || '../api';
        const withId = options.withId;
        const myId = options.myId;
        const myName = options.myName || '';
        const myPic = options.myPic || null;
        const otherInitial = options.otherInitial || '';
        const otherPic = options.otherPic || null;
        const basePath = options.basePath || '../';

        const msgForm = document.getElementById('chatForm');
        const msgInput = document.getElementById('messageInput');
        const messagesArea = document.getElementById('messagesArea');
        const fileInput = document.getElementById('chatFileInput');
        const emojiPicker = document.getElementById('emojiPicker');
        const emojiBtn = document.getElementById('emojiBtn');
        const attachBtn = document.getElementById('attachBtn');
        const filePreview = document.getElementById('filePreview');
        const filePreviewName = document.getElementById('filePreviewName');
        const clearFileBtn = document.getElementById('clearFileBtn');

        if (!msgForm || !withId || !messagesArea) return;

        let lastMsgId = options.lastMsgId || 0;

        function buildAvatarHtml(isMine) {
            const pic = isMine ? myPic : otherPic;
            const initial = isMine ? [...myName][0] : otherInitial;
            if (pic) {
                const border = isMine ? 'border-purple-200' : 'border-gray-200';
                return '<img src="' + escapeHtml(pic) + '" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border-2 ' + border + '">';
            }
            const avatarClass = isMine
                ? 'w-8 h-8 gradient-primary rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0'
                : 'w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0';
            return '<div class="' + avatarClass + '">' + escapeHtml(initial) + '</div>';
        }

        function messageBodyHtml(m) {
            const type = m.msg_type || 'text';
            const textClass = m.isMine ? 'text-white text-sm' : 'text-gray-800 text-sm';
            const linkClass = m.isMine ? 'text-white/90 underline text-xs' : 'text-purple-600 underline text-xs';
            const id = m.id;

            if (type === 'image' && m.file_path) {
                const src = escapeHtml(basePath + m.file_path);
                const dl = escapeHtml(basePath + 'api/download_message_file.php?id=' + id);
                let cap = m.content ? '<p class="' + textClass + ' mt-2">' + escapeHtml(m.content).replace(/\n/g, '<br>') + '</p>' : '';
                return '<a href="' + dl + '" target="_blank"><img src="' + src + '" class="max-w-xs rounded-xl max-h-64 object-cover"></a>' + cap +
                    '<p class="mt-1"><a href="' + dl + '" class="' + linkClass + '">تنزيل الصورة</a></p>';
            }
            if (type === 'file' && m.file_path) {
                const dl = escapeHtml(basePath + 'api/download_message_file.php?id=' + id);
                const name = escapeHtml(m.file_name || 'ملف');
                let cap = m.content ? '<p class="' + textClass + ' mb-2">' + escapeHtml(m.content).replace(/\n/g, '<br>') + '</p>' : '';
                return cap + '<a href="' + dl + '" class="flex items-center gap-2 ' + textClass + ' bg-black/10 rounded-lg px-3 py-2">' +
                    '<span class="text-sm font-semibold truncate">' + name + ' — تنزيل</span></a>';
            }
            return '<p class="' + textClass + '">' + escapeHtml(m.content || '').replace(/\n/g, '<br>') + '</p>';
        }

        function clearEmptyState() {
            const empty = document.getElementById('emptyChatState');
            if (empty) empty.remove();
        }

        function appendMessage(data) {
            const id = data.id;
            if (document.querySelector('[data-msg-id="' + id + '"]')) return;
            clearEmptyState();
            const isMine = !!data.isMine;
            const div = document.createElement('div');
            div.setAttribute('data-msg-id', id);
            div.className = 'flex items-end gap-3' + (isMine ? ' flex-row-reverse' : '');
            const bubbleClass = isMine ? 'gradient-primary rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm' : 'bg-white rounded-2xl rounded-br-sm px-4 py-3 shadow-sm border border-gray-100';
            const timeAlign = isMine ? 'ml-1 text-left' : 'mr-1';
            const payload = Object.assign({}, data, { isMine: isMine });
            div.innerHTML = buildAvatarHtml(isMine) +
                '<div class="max-w-md"><div class="' + bubbleClass + '">' + messageBodyHtml(payload) + '</div>' +
                '<p class="text-xs text-gray-400 mt-1 ' + timeAlign + '">' + escapeHtml(data.time || '') + '</p></div>';
            messagesArea.appendChild(div);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        function previewText(data) {
            const type = data.msg_type || 'text';
            if (type === 'image') return '📷 صورة';
            if (type === 'file') return '📎 ' + (data.file_name || 'ملف');
            return (data.content || '').slice(0, 35);
        }

        function updateSidebarPreview(text) {
            const link = document.querySelector('[data-contact-id="' + withId + '"]');
            if (!link) return;
            let preview = link.querySelector('.contact-preview');
            if (!preview) {
                preview = document.createElement('p');
                preview.className = 'text-xs text-gray-400 truncate mt-0.5 contact-preview';
                const box = link.querySelector('.flex-1.min-w-0');
                if (box) box.appendChild(preview);
            }
            preview.textContent = text;
        }

        if (emojiPicker) {
            emojiPicker.innerHTML = EMOJIS.map(function(e) {
                return '<button type="button" class="emoji-pick hover:bg-purple-100 rounded p-1" data-emoji="' + e + '">' + e + '</button>';
            }).join('');
            emojiPicker.querySelectorAll('.emoji-pick').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    msgInput.value += btn.getAttribute('data-emoji');
                    msgInput.focus();
                });
            });
        }

        if (emojiBtn && emojiPicker) {
            emojiBtn.addEventListener('click', function() {
                emojiPicker.classList.toggle('hidden');
            });
        }

        if (attachBtn && fileInput) {
            attachBtn.addEventListener('click', function() { fileInput.click(); });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (fileInput.files && fileInput.files[0]) {
                    filePreviewName.textContent = fileInput.files[0].name;
                    filePreview.classList.remove('hidden');
                }
            });
        }

        if (clearFileBtn && fileInput) {
            clearFileBtn.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.classList.add('hidden');
            });
        }

        msgForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const content = msgInput.value.trim();
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!content && !hasFile) return;

            const btn = document.getElementById('chatSendBtn');
            if (btn) btn.disabled = true;

            const fd = new FormData();
            fd.append('receiver_id', withId);
            fd.append('content', content);
            if (hasFile) fd.append('attachment', fileInput.files[0]);

            const tempId = 'temp-' + Date.now();
            appendMessage({
                id: tempId,
                isMine: true,
                content: content,
                msg_type: hasFile ? 'file' : 'text',
                file_name: hasFile ? fileInput.files[0].name : '',
                time: 'الآن'
            });
            updateSidebarPreview(previewText({ content: content, msg_type: hasFile ? 'file' : 'text', file_name: hasFile ? fileInput.files[0].name : '' }));

            msgInput.value = '';
            if (fileInput) { fileInput.value = ''; filePreview.classList.add('hidden'); }
            if (emojiPicker) emojiPicker.classList.add('hidden');

            fetch(apiBase + '/send_message.php', { method: 'POST', body: fd })
                .then(parseJsonResponse)
                .then(function(data) {
                    const tempEl = document.querySelector('[data-msg-id="' + tempId + '"]');
                    if (data.success) {
                        if (tempEl) tempEl.remove();
                        appendMessage({
                            id: data.id,
                            isMine: true,
                            content: data.content,
                            msg_type: data.msg_type,
                            file_path: data.file_path,
                            file_name: data.file_name,
                            time: data.time
                        });
                        lastMsgId = Math.max(lastMsgId, parseInt(data.id, 10));
                        updateSidebarPreview(previewText(data));
                    } else if (tempEl) {
                        tempEl.remove();
                    }
                    if (btn) btn.disabled = false;
                })
                .catch(function() {
                    const tempEl = document.querySelector('[data-msg-id="' + tempId + '"]');
                    if (tempEl) tempEl.remove();
                    if (btn) btn.disabled = false;
                });
        });

        setInterval(function() {
            fetch(apiBase + '/get_messages.php?with=' + withId + '&last_id=' + lastMsgId)
                .then(parseJsonResponse)
                .then(function(msgs) {
                    if (!Array.isArray(msgs)) return;
                    msgs.forEach(function(m) {
                        const isMine = (parseInt(m.sender_id, 10) === parseInt(myId, 10));
                        const time = new Date(m.created_at).toLocaleTimeString('ar', { hour: '2-digit', minute: '2-digit' });
                        appendMessage({
                            id: m.id,
                            isMine: isMine,
                            content: m.content,
                            msg_type: m.msg_type,
                            file_path: m.file_path,
                            file_name: m.file_name,
                            time: time
                        });
                        lastMsgId = Math.max(lastMsgId, parseInt(m.id, 10));
                    });
                })
                .catch(function() {});
        }, 3000);

        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    global.initChat = initChat;
})(window);
