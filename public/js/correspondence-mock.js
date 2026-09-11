(function () {
    const {users, conversations, authUserId} = window.CORRESPONDENCE_DATA;

    const state = {
        activeConversationId: null,
        activeMessageId: null,
        search: '',
        chip: 'all'
    };

    const els = {
        skeleton: document.getElementById('correspondence-skeleton'),
        body: document.getElementById('correspondence-body'),
        list: document.getElementById('conversationList'),
        searchInput: document.getElementById('searchConversation'),
        filterChips: document.getElementById('filterChips'),
        composeModal: document.getElementById('composeModal'),
        btnOpenCompose: document.getElementById('btnOpenCompose'),
        composeForm: document.getElementById('composeForm'),
        composeSubject: document.getElementById('composeSubject'),
        composeBody: document.getElementById('composeBody'),
        composeRecipients: document.getElementById('composeRecipients'),
        composeAttachment: document.getElementById('composeAttachment'),
        btnComposeSend: document.getElementById('btnComposeSend'),
        viewModal: document.getElementById('viewConversationModal'),
        viewSubject: document.getElementById('viewSubject'),
        viewParticipants: document.getElementById('viewParticipants'),
        viewMessages: document.getElementById('viewMessages'),
        actionArchiveModal: document.getElementById('actionArchiveModal'),
        actionMuteModal: document.getElementById('actionMuteModal'),
        actionAddModal: document.getElementById('actionAddModal')
    };
    function renderAvatar(user) {
        if (!user) return '';
        const initials = (user.name || '').split(' ').map(p => p[0]).join('').toUpperCase();
        const color = user.color || '#cccccc';
        return `<span class="avatar" style="background-color:${color};width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;font-size:12px;color:#fff;margin-left:2px">${initials}</span>`;
    }
    const pusher = new Pusher(window.PUSHER_CONFIG.key, {
        cluster: window.PUSHER_CONFIG.cluster,
        forceTLS: true
    });

    function init() {
        setTimeout(() => {
            if (els.skeleton) els.skeleton.classList.add('d-none');
            if (els.body) els.body.classList.remove('d-none');
            initializeUi();
            state.activeConversationId = conversations[0]?.id || null;
            if (state.activeConversationId) markAsRead(state.activeConversationId);
            renderMessageList();
        }, 450);

        bindEvents();
        initRealtime();
        initComposeModal();
    }

    function initializeUi() {
        reInitTooltips();
    }

    function initComposeModal() {
        if (els.composeRecipients) {
            els.composeRecipients.innerHTML = Object.values(users)
                .map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            $(els.composeRecipients).select2({
                width: '100%',
                dir: 'rtl',
                placeholder: 'انتخاب گیرندگان',
                closeOnSelect: false,
                dropdownParent: $('#composeModal')
            });
        }
    }

    function reInitTooltips() {
        const tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-tooltip]'));
        tooltipEls.forEach(el => {
            const existing = bootstrap.Tooltip.getInstance(el);
            if (existing) existing.dispose();
            new bootstrap.Tooltip(el);
        });
    }

    function bindEvents() {
        els.searchInput?.addEventListener('input', (e) => {
            state.search = e.target.value.trim();
            renderMessageList();
        });

        els.filterChips?.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (!chip) return;
            state.chip = chip.dataset.filter;
            Array.from(els.filterChips.querySelectorAll('.chip'))
                .forEach(c => c.classList.toggle('active', c === chip));
            renderMessageList();
        });

        els.btnComposeSend?.addEventListener('click', (e) => {
            e.preventDefault();
            handleComposeSend();
        });

        els.viewMessages?.addEventListener('click', (e) => {
            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;
            const {action, messageId} = actionBtn.dataset;
            handleMessageAction(action, messageId);
        });

        els.actionArchiveModal?.addEventListener('click', toggleArchive);
        els.actionMuteModal?.addEventListener('click', toggleMute);
        els.actionAddModal?.addEventListener('click', () => showToast('UI فقط: انتخاب عضو جدید پیاده‌سازی نشده است.'));
    }

    function renderMessageList() {
        if (!els.list) return;
        const flattened = flattenMessages()
            .filter(filterByChipMessage)
            .filter(filterBySearchMessage)
            .sort((a, b) => new Date(b.time) - new Date(a.time));

        if (!flattened.length) {
            els.list.innerHTML = '<div class="text-muted text-center py-3">موردی یافت نشد.</div>';
            return;
        }

        els.list.innerHTML = flattened.map(msg => {
            const activeClass = state.activeMessageId === msg.id ? 'active' : '';
            const unreadBadge = msg.unread ? `<span class="badge unread text-white">${msg.unread}</span>` : '';
            const flags = `
                ${msg.archived ? '<span class="badge bg-info text-dark">آرشیو</span>' : ''}
                ${msg.muted ? '<span class="badge bg-secondary">بی‌صدا</span>' : ''}
            `;
            return `<div class="conversation-item ${activeClass}" data-id="${msg.id}" data-conv="${msg.conversationId}">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-600 truncate" title="${msg.subject}"> موضوع :  ${msg.subject}</span>
                                ${unreadBadge}
                            </div>
                            <div class="text-muted truncate mt-1"> متن پیام :   ${msg.preview}</div>
                            <hr>
                            <div class="text-muted truncate" style="float:left;font-size: 11px" title="${msg.senderName}"> ارسال شده توسط :  ${msg.senderName}</div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted" style="font-size: 11px">${formatRelative(msg.time)}</small>
                                <div class="badges-inline">${flags}</div>
                            </div>
                        </div>
                    </div>`;
        }).join('');

        Array.from(els.list.querySelectorAll('.conversation-item')).forEach(item => {
            item.addEventListener('click', () => {
                state.activeConversationId = item.dataset.conv;
                state.activeMessageId = item.dataset.id;
                markAsRead(item.dataset.conv);
                renderMessageList();
                openMessageModal(item.dataset.conv);
            });
        });
    }

    function flattenMessages() {
        const list = [];
        conversations.forEach(conv => {
            // root
            const root = conv.messages.root;
            list.push({
                id: root.id,
                conversationId: conv.id,
                subject: conv.subject,
                senderId: root.senderId,
                senderName: users[root.senderId]?.name || 'نامشخص',
                body: root.body || '',
                time: root.time,
                archived: conv.archived,
                muted: conv.muted,
                unread: conv.unread,
                type: conv.type,
                deleted: false,
                direction: root.direction,
                attachments: root.attachments || [],
                preview: root.body || ''
            });
        });
        return list;
    }

    function filterByChipMessage(msg) {
        switch (state.chip) {
            case 'unread': return msg.unread > 0;
            case 'archived': return msg.archived;
            case 'muted': return msg.muted;
            case 'type-internal': return msg.type === 'internal';
            case 'type-external': return msg.type === 'external';
            case 'sent': return msg.direction === 'outgoing';
            default: return true;
        }
    }

    function filterBySearchMessage(msg) {
        if (!state.search) return true;
        const q = state.search.toLowerCase();
        return msg.subject.toLowerCase().includes(q) || msg.senderName.toLowerCase().includes(q) || msg.body.toLowerCase().includes(q);
    }

    function openMessageModal(conversationId) {
        const conv = conversations.find(c => c.id === conversationId);
        if (!conv) return;

        const root = conv.messages.root;
        if (els.viewSubject) els.viewSubject.textContent = conv.subject;
        if (els.viewParticipants) els.viewParticipants.innerHTML = conv.participants.map(id => renderAvatar(users[id])).join('');

        // ست کردن conversationId روی مودال
        els.viewModal.dataset.conversationId = conv.id;

        if (els.viewMessages) {
            els.viewMessages.innerHTML = renderThread(conv);
        }

        toggleHeaderActions(conv.archived, conv.muted);

        const modal = bootstrap.Modal.getInstance(els.viewModal) || new bootstrap.Modal(els.viewModal);
        modal.show();
    }

    function renderThread(conv) {
        let html = '';
        const rootMsg = conv.messages.root;
        html += renderMessage(rootMsg, false);

        conv.messages.replies.forEach(r => {
            html += renderMessage(r, true);
        });

        return html;
    }

    function renderMessage(msg, isReply) {
        const sender = users[msg.senderId];
        let attachmentsHtml = '';

        if (msg.attachments && msg.attachments.length) {
            attachmentsHtml = `
                <div class="message-attachments mt-2">
                    ${msg.attachments.map(a => `
                        <a href="${a.url}" target="_blank" class="d-block text-decoration-none">
                            📎 ${a.name}
                        </a>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div class="message-card ${isReply ? 'ms-4 mt-2 border-start ps-3' : ''}">
                <div class="fw-600">${sender?.name || ''}</div>
                <small class="text-muted">${formatDateTime(msg.time)}</small>
                <div class="message-body mt-2">${msg.body}</div>
                ${attachmentsHtml}
                ${!isReply ? `
                    <button class="btn btn-sm btn-outline-primary mt-2"
                        data-action="reply"
                        data-message-id="${msg.id}">
                        پاسخ
                    </button>` : ''}
            </div>
        `;
    }

    function formatDateTime(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleString('fa-IR', {hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short'});
    }

    function formatRelative(dateStr) {
        const d = new Date(dateStr);
        const now = new Date();
        const diff = (now - d) / (1000 * 60); // minutes
        if (diff < 60) return `${Math.max(1, Math.round(diff))} دقیقه قبل`;
        if (diff < 1440) return `${Math.round(diff / 60)} ساعت قبل`;
        return d.toLocaleDateString('fa-IR');
    }

    function markAsRead(conversationId) {
        const conv = conversations.find(c => c.id === conversationId);
        if (!conv) return;
        conv.unread = 0;
        conv.lastActivity = conv.messages.root.time || conv.lastActivity;
    }

    function handleMessageAction(action, messageId) {
        const conv = conversations.find(c => c.id === state.activeConversationId);
        if (!conv) return;
        const msg = flattenMessages().find(m => m.id === messageId);
        if (!msg) return;

        if (action === 'delete') return; // حذف غیرفعال شد
        if (action === 'reply') handleReply(messageId);

        openMessageModal(conv.id);
    }

    function handleReply(messageId) {
        // parent message id
        els.composeForm.dataset.parentId = messageId.replace('m','');

        // بستن مودال مشاهده
        const viewModalInstance = bootstrap.Modal.getInstance(els.viewModal);
        viewModalInstance?.hide();

        // ریست فیلدها
        const conv = conversations.find(c => c.id === els.viewModal.dataset.conversationId);
        els.composeSubject.value = conv?.subject || '';
        els.composeBody.value = '';

        // باز کردن مودال ارسال
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(els.composeModal).show();
        }, 300);
    }

    function handleComposeSend() {
        const subject = (els.composeSubject?.value || '').trim();
        const body = (els.composeBody?.value || '').trim();
        const recipientIds = $(els.composeRecipients || []).val() || [];

        if (!body || !recipientIds.length) {
            showToast('متن و گیرندگان الزامی است.');
            return;
        }

        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('body', body);

        recipientIds.forEach(id => formData.append('recipients[]', id));

        if (els.composeAttachment?.files?.length) {
            Array.from(els.composeAttachment.files).forEach(file => {
                formData.append('attachments[]', file);
            });
        }

        const postUrl = window.CORRESPONDENCE_POST_URL;
        fetch(postUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(res => {
                if (!res.ok) throw new Error();
                return res.json();
            })
            .then(data => {
                const modal = bootstrap.Modal.getInstance(els.composeModal) || new bootstrap.Modal(els.composeModal);
                modal.hide();

                // اضافه کردن پیام به خود فرستنده
                const conv = conversations.find(c => c.id === state.activeConversationId);
                if (conv) {
                    const attachments = els.composeAttachment?.files
                        ? Array.from(els.composeAttachment.files).map(f => ({
                            id: f.name,
                            name: f.name,
                            url: URL.createObjectURL(f),
                            size: f.size,
                            mime: f.type
                        }))
                        : [];

                    const newMessage = {
                        id: 'm' + data.id,
                        senderId: authUserId,
                        body: body,
                        time: new Date().toISOString(),
                        direction: 'outgoing',
                        attachments: attachments
                    };

                    if (els.composeForm.dataset.parentId) {
                        // اگر پاسخ است، به replies اضافه شود
                        conv.messages.replies.push(newMessage);
                    } else {
                        // پیام اصلی
                        conv.messages.root = newMessage;
                    }

                    // باز کردن یا بروزرسانی modal
                    if (state.activeConversationId === conv.id) {
                        openMessageModal(conv.id);
                    }

                    renderMessageList();
                }

                resetComposeForm();
                showToast('پیام با موفقیت ارسال شد.');
            })
            .catch(() => {
                showToast('خطا در ارسال پیام.');
            });
    }

    function resetComposeForm() {
        if (els.composeSubject) els.composeSubject.value = '';
        if (els.composeBody) els.composeBody.value = '';
        if (els.composeAttachment) els.composeAttachment.value = '';
        $(els.composeRecipients || []).val(null).trigger('change');
    }

    function toggleArchive() {
        const conv = conversations.find(c => c.id === state.activeConversationId);
        if (!conv) return;
        conv.archived = !conv.archived;
        toggleHeaderActions(conv.archived, conv.muted);
        renderMessageList();
        showToast(conv.archived ? 'مکالمه آرشیو شد.' : 'از آرشیو خارج شد.');
    }

    function toggleMute() {
        const conv = conversations.find(c => c.id === state.activeConversationId);
        if (!conv) return;
        conv.muted = !conv.muted;
        toggleHeaderActions(conv.archived, conv.muted);
        renderMessageList();
        showToast(conv.muted ? 'مکالمه بی‌صدا شد.' : 'صدا فعال شد.');
    }

    function toggleHeaderActions(isArchived, isMuted) {
        if (els.actionArchiveModal) els.actionArchiveModal.classList.toggle('active', isArchived);
        if (els.actionMuteModal) els.actionMuteModal.classList.toggle('active', isMuted);
    }

    function showToast(message) {
        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-primary border-0 position-fixed';
        toastEl.style.zIndex = 9999;
        toastEl.style.left = '20px';
        toastEl.style.bottom = '20px';
        toastEl.role = 'alert';
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        document.body.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, {delay: 2200});
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
    function initRealtime() {

        const channel = pusher.subscribe('private-correspondence');

        channel.bind('App\\Events\\MessageSent', function(data) {
            const conv = conversations.find(c => c.id == data.conversation_id);
            if (!conv) return;

            if (data.parent_id) {
                conv.messages.replies.push(data.message);
            } else {
                conv.messages.root = data.message;
            }

            // اگر conversation فعلی باز است، بلافاصله نمایش بده
            if (state.activeConversationId === conv.id) {
                openMessageModal(conv.id);
            }

            renderMessageList();
        });

    }

    document.addEventListener('DOMContentLoaded', init);
})();
