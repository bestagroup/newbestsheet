(function () {
    if (window.__CORRESPONDENCE_BOOTED__) return;
    window.__CORRESPONDENCE_BOOTED__ = true;
    const {users, conversations, authUserId} = window.CORRESPONDENCE_DATA;

    const state = {
        activeConversationId: null,
        activeMessageId: null,
        search: '',
        chip: 'all'
    };
    const PAGINATION = {
        page: 1,
        perPage: 10
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

    function normalizeId(id) {
        return id == null ? '' : String(id);
    }

    function isSameId(left, right) {
        return normalizeId(left) === normalizeId(right);
    }

    function canonicalMessageId(id) {
        return normalizeId(id).replace(/^m/i, '');
    }

    function isSameMessageId(left, right) {
        const a = canonicalMessageId(left);
        const b = canonicalMessageId(right);
        if (!a || !b) return false;
        return a === b;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function sanitizeColor(value) {
        const raw = String(value || '').trim();
        const isHex = /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(raw);
        return isHex ? raw : '#cccccc';
    }

    function sanitizeUrl(url) {
        try {
            const parsed = new URL(String(url || ''), window.location.origin);
            const protocol = parsed.protocol.toLowerCase();
            if (protocol === 'http:' || protocol === 'https:' || protocol === 'blob:') return parsed.href;
        } catch (_) {}
        return '#';
    }

    function renderAvatar(user) {
        if (!user) return '';

        const initials = escapeHtml(
            (user.name || '')
                .split(' ')
                .map(p => p[0])
                .join('')
                .toUpperCase()
        );

        const color = sanitizeColor(user.color);
        const name = escapeHtml(user.name || '');

        return `
        <span
            class="avatar"
            data-bs-toggle="tooltip"
            data-bs-placement="bottom"
            title="${name}"
            style="
                background-color:${color};
                width:28px;
                height:28px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                border-radius:50%;
                font-size:12px;
                color:#fff;
                margin-left:2px;
                cursor:pointer;
            ">
            ${initials}
        </span>
    `;
    }

    const channelSubscriptions = window.__CORRESPONDENCE_CHANNELS__ || (window.__CORRESPONDENCE_CHANNELS__ = new Set());
    const channelBindings = window.__CORRESPONDENCE_CHANNEL_BINDINGS__ || (window.__CORRESPONDENCE_CHANNEL_BINDINGS__ = new Set());
    const pusher = window.__CORRESPONDENCE_PUSHER__ || (window.__CORRESPONDENCE_PUSHER__ = new Pusher(window.PUSHER_CONFIG.key, {
        cluster: window.PUSHER_CONFIG.cluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }
    }));

    let refreshTimer = null;
    let activeConversationChannelName = null;

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
            els.composeRecipients.innerHTML = '';
            Object.values(users).forEach((u) => {
                const option = document.createElement('option');
                option.value = String(u.id ?? '');
                option.textContent = u.name || '';
                els.composeRecipients.appendChild(option);
            });
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
        els.searchInput = document.getElementById('searchConversation');

        els.searchInput?.addEventListener('input', (e) => {
            state.search = e.target.value.trim();  // متن سرچ را ذخیره می‌کنیم
            if (typeof resetPagination === 'function') resetPagination(); // اگر pagination داری صفحه را ریست کن
            renderMessageList(); // رندر مجدد لیست با فیلتر جدید
        });

        els.filterChips?.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (!chip) return;

            state.chip = chip.dataset.filter;

            Array.from(els.filterChips.querySelectorAll('.chip'))
                .forEach(c => c.classList.toggle('active', c === chip));

            if (typeof resetPagination === 'function') {
                resetPagination();
            }

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

        const totalPages = Math.ceil(flattened.length / PAGINATION.perPage);
        PAGINATION.page = Math.min(PAGINATION.page, totalPages);

        const start = (PAGINATION.page - 1) * PAGINATION.perPage;
        const items = flattened.slice(start, start + PAGINATION.perPage);

        els.list.innerHTML = `
        ${items.map(renderConversationItem).join('')}
        ${renderPagination(totalPages)}
    `;

        bindConversationClicks();
    }

    // 1️⃣ نمایش نام ارسال‌کننده در لیست مکالمات (conversation list)
// جایگزین renderConversationItem

// فقط renderConversationItem را اصلاح کن

    function renderConversationItem(msg) {
        const activeClass = isSameId(state.activeMessageId, msg.id) ? 'active' : '';
        const unreadBadge = msg.unread ? `<span class="badge unread text-white">${msg.unread}</span>` : '';

        const safeSubject = escapeHtml(msg.subject);
        const safePreview = escapeHtml(msg.preview);
        const safeSender = escapeHtml(msg.senderName || '');

        return `
    <div class="conversation-item ${activeClass}"
         data-id="${escapeHtml(msg.id)}"
         data-conv="${escapeHtml(msg.conversationId)}">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-600 truncate">موضوع: ${safeSubject}</span>
                ${unreadBadge}
            </div>

            <div class="text-muted truncate mt-1">
                متن پیام: ${safePreview}
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mt-1">
                <!-- سمت راست: زمان -->
                <small class="text-muted" style="font-size:11px">
                  زمان ارسال : ${formatRelative(msg.time)}
                </small>

                <!-- سمت چپ: نام ارسال‌کننده -->
                <small class="text-muted fw-600" style="font-size:11px">
                   ارسال کننده :  ${safeSender}
                </small>
            </div>
        </div>
    </div>`;
    }
    function renderPagination(totalPages) {
        if (totalPages <= 1) return '';

        let html = `<div class="d-flex justify-content-center gap-1 py-2">`;

        for (let i = 1; i <= totalPages; i++) {
            html += `
            <button
                class="btn btn-sm ${i === PAGINATION.page ? 'btn-primary' : 'btn-outline-secondary'}"
                data-page="${i}">
                ${i}
            </button>
        `;
        }

        html += `</div>`;
        return html;
    }
    els.list?.addEventListener('click', (e) => {
        const pageBtn = e.target.closest('[data-page]');
        if (!pageBtn) return;

        PAGINATION.page = Number(pageBtn.dataset.page);
        renderMessageList();
    });
    function bindConversationClicks() {
        Array.from(els.list.querySelectorAll('.conversation-item')).forEach(item => {
            item.addEventListener('click', () => {
                state.activeConversationId = item.dataset.conv;
                subscribeActiveConversationChannel();
                state.activeMessageId = item.dataset.id;
                markAsRead(item.dataset.conv);
                renderMessageList();
                openMessageModal(item.dataset.conv);
            });
        });
    }
    function resetPagination() {
        PAGINATION.page = 1;
    }
    // function renderMessageList() {
    //     if (!els.list) return;
    //     const flattened = flattenMessages()
    //         .filter(filterByChipMessage)
    //         .filter(filterBySearchMessage)
    //         .sort((a, b) => new Date(b.time) - new Date(a.time));
    //
    //     if (!flattened.length) {
    //         els.list.innerHTML = '<div class="text-muted text-center py-3">موردی یافت نشد.</div>';
    //         return;
    //     }
    //
    //     els.list.innerHTML = flattened.map(msg => {
    //         const activeClass = state.activeMessageId === msg.id ? 'active' : '';
    //         const unreadBadge = msg.unread ? `<span class="badge unread text-white">${msg.unread}</span>` : '';
    //         const flags = `
    //             ${msg.archived ? '<span class="badge bg-info text-dark">آرشیو</span>' : ''}
    //             ${msg.muted ? '<span class="badge bg-secondary">بی‌صدا</span>' : ''}
    //         `;
    //         return `<div class="conversation-item ${activeClass}" data-id="${msg.id}" data-conv="${msg.conversationId}">
    //                     <div class="p-3">
    //                         <div class="d-flex justify-content-between align-items-center mb-1">
    //                             <span class="fw-600 truncate" title="${msg.subject}"> موضوع :  ${msg.subject}</span>
    //                             ${unreadBadge}
    //                         </div>
    //                         <div class="text-muted truncate mt-1"> متن پیام :   ${msg.preview}</div>
    //                         <hr>
    //                         <div class="text-muted truncate" style="float:left;font-size: 11px" title="${msg.senderName}"> ارسال شده توسط :  ${msg.senderName}</div>
    //                         <div class="d-flex justify-content-between align-items-center mt-1">
    //                             <small class="text-muted" style="font-size: 11px">${formatRelative(msg.time)}</small>
    //                             <div class="badges-inline">${flags}</div>
    //                         </div>
    //                     </div>
    //                 </div>`;
    //     }).join('');
    //
    //     Array.from(els.list.querySelectorAll('.conversation-item')).forEach(item => {
    //         item.addEventListener('click', () => {
    //             state.activeConversationId = item.dataset.conv;
    //             subscribeActiveConversationChannel();
    //             state.activeMessageId = item.dataset.id;
    //             markAsRead(item.dataset.conv);
    //             renderMessageList();
    //             openMessageModal(item.dataset.conv);
    //         });
    //     });
    // }

    function flattenMessages() {
        const list = [];
        conversations.forEach(conv => {
            const root = conv?.messages?.root;
            if (!root) return;
            const lastMsg = getLastMessage(conv) || root;
            const participants = Array.isArray(conv.participants) ? conv.participants : [];
            const recipients = participants
                .filter((id) => !isSameId(id, authUserId))
                .map((id) => users[id]?.name || '')
                .filter(Boolean)
                .join(' ');
            list.push({
                id: root.id,
                conversationId: conv.id,
                subject: conv.subject,
                senderId: lastMsg.senderId,
                senderName: users[lastMsg.senderId]?.name || 'نامشخص',
                body: lastMsg.body || '',
                time: lastMsg.time,
                archived: conv.archived,
                muted: conv.muted,
                unread: conv.unread,
                type: conv.type,
                deleted: false,
                direction: root.direction,
                attachments: root.attachments || [],
                preview: lastMsg.body || '',
                recipients
            });
        });
        return list;
    }

    function filterByChipMessage(msg) {
        switch (state.chip) {
            case 'sent':
                return isSameId(msg.senderId, authUserId);

            case 'received':
                return !isSameId(msg.senderId, authUserId);

            case 'all':
            default:
                return true;
        }
    }

    function filterBySearchMessage(msg) {
        if (!state.search) return true;

        const q = state.search.toLowerCase();
        const subjectMatch = msg.subject?.toLowerCase().includes(q);
        const bodyMatch = msg.body?.toLowerCase().includes(q);
        const senderMatch = msg.senderName?.toLowerCase().includes(q);
        const recipientsMatch = msg.recipients?.toLowerCase().includes(q);
        return subjectMatch || bodyMatch || senderMatch || recipientsMatch;
    }

    function openMessageModal(conversationId) {
        const convId = normalizeId(conversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (!conv || !els.viewModal) return;

        if (els.viewSubject) {
            els.viewSubject.textContent = conv.subject;
        }

        const participants = Array.isArray(conv.participants) ? conv.participants : [];

        if (els.viewParticipants) {
            els.viewParticipants.innerHTML = participants
                .map(id => renderAvatar(users[id]))
                .join('');
        }

        els.viewModal.dataset.conversationId = conv.id;

        if (els.viewMessages) {
            els.viewMessages.innerHTML = renderThread(conv);
        }

        toggleHeaderActions(conv.archived, conv.muted);

        const modal =
            bootstrap.Modal.getInstance(els.viewModal) ||
            new bootstrap.Modal(els.viewModal);

        modal.show();

        reInitTooltips();
    }

    function renderThread(conv) {
        let html = '';
        const rootMsg = conv?.messages?.root;
        if (rootMsg) html += renderMessage(rootMsg, false);
        const replies = Array.isArray(conv?.messages?.replies) ? conv.messages.replies.slice() : [];
        replies.sort((a, b) => {
            const t1 = new Date(a?.time || 0).getTime();
            const t2 = new Date(b?.time || 0).getTime();
            return t1 - t2;
        });
        replies.forEach(r => html += renderMessage(r, true));
        return html;
    }

    function getLastMessage(conv) {
        const all = [];
        if (conv?.messages?.root) all.push(conv.messages.root);
        if (Array.isArray(conv?.messages?.replies)) all.push(...conv.messages.replies);
        if (!all.length) return null;
        return all.reduce((latest, msg) => {
            if (!latest) return msg;
            const latestTime = new Date(latest.time).getTime();
            const msgTime = new Date(msg.time).getTime();
            return msgTime >= latestTime ? msg : latest;
        }, null);
    }

// 2️⃣ نمایش نام دریافت‌کنندگان داخل خود پیام برای ارسال‌کننده
// جایگزین renderMessage

    function renderMessage(msg, isReply) {
        const sender = users[msg.senderId];
        const isSelf = Number(msg.senderId) === Number(authUserId);

        let attachmentsHtml = '';
        if (msg.attachments?.length) {
            attachmentsHtml = `<div class="message-attachments mt-2">
            ${msg.attachments.map(a =>
                `<a href="${sanitizeUrl(a.url)}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="d-block text-decoration-none">
                    📎 ${escapeHtml(a.name)}
                 </a>`
            ).join('')}
        </div>`;
        }

        const alignmentClass = isSelf ? 'message-self' : 'message-other';
        const replyClass = isReply ? 'message-reply' : '';
        const canReply = !isSelf;

        return `
    <div class="message-card ${alignmentClass} ${replyClass}">
        <div class="fw-600">ارسال کننده :  ${escapeHtml(sender?.name || '')}</div>

        <div class="message-body mt-2">متن پیام  :  ${escapeHtml(msg.body)}</div>

        ${attachmentsHtml}

        <!-- تاریخ و ساعت ارسال پیام شمسی -->
        <small class="text-muted mt-1 d-block" style="font-size:11px"> <hr>
          تاریخ ارسال پیام :   ${formatDateTime(msg.time)}
        </small>

        ${canReply
            ? `<button class="btn btn-sm btn-outline-primary mt-2"
                       data-action="reply"
                       data-message-id="${msg.id}">
                   پاسخ
               </button>`
            : ''}
    </div>`;
    }

// اگر formatDateTime فعلی دارید، همان را استفاده کنید تا تاریخ و ساعت شمسی شود
    function formatDateTime(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleString('fa-IR', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: 'short'
        });
    }

    function formatDateTime(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleString('fa-IR', {hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short'});
    }

    function formatRelative(dateStr) {
        const d = new Date(dateStr);
        const now = new Date();
        const diff = (now - d) / (1000 * 60);
        if (diff < 60) return `${Math.max(1, Math.round(diff))} دقیقه قبل`;
        if (diff < 1440) return `${Math.round(diff / 60)} ساعت قبل`;
        return d.toLocaleDateString('fa-IR');
    }

    function ensureConversationMessages(conv) {
        if (!conv.messages || typeof conv.messages !== 'object') {
            conv.messages = {root: null, replies: []};
        }
        if (!Array.isArray(conv.messages.replies)) {
            conv.messages.replies = [];
        }
    }

    function upsertConversationMessage(conv, message, parentId) {
        ensureConversationMessages(conv);
        const incomingId = message?.id;

        if (incomingId != null) {
            if (conv.messages.root && isSameMessageId(conv.messages.root.id, incomingId)) {
                conv.messages.root = {...conv.messages.root, ...message};
                return {inserted: false, message: conv.messages.root};
            }

            const existingReplyIndex = conv.messages.replies.findIndex((m) => isSameMessageId(m?.id, incomingId));
            if (existingReplyIndex >= 0) {
                conv.messages.replies[existingReplyIndex] = {...conv.messages.replies[existingReplyIndex], ...message};
                return {inserted: false, message: conv.messages.replies[existingReplyIndex]};
            }
        }

        const shouldAppendToReplies = !!parentId || !!conv.messages.root;
        if (shouldAppendToReplies) {
            conv.messages.replies.push(message);
            return {inserted: true, message};
        }

        conv.messages.root = message;
        return {inserted: true, message};
    }

    function markAsRead(conversationId) {
        const convId = normalizeId(conversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (!conv) return;
        conv.unread = 0;
        conv.lastActivity = conv?.messages?.root?.time || conv.lastActivity;
    }

    function handleMessageAction(action, messageId) {
        const convId = normalizeId(state.activeConversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (!conv) return;
        const allMsgs = [conv.messages.root].concat(conv.messages.replies || []).filter(Boolean);
        const msg = allMsgs.find(m => isSameMessageId(m.id, messageId));
        if (!msg) return;
        if (action === 'delete') return;
        if (action === 'reply') handleReply(messageId);
        openMessageModal(conv.id);
    }

    function handleReply(messageId) {
        els.composeForm.dataset.parentId = normalizeId(messageId).replace(/^m/, '');
        const viewModalInstance = bootstrap.Modal.getInstance(els.viewModal);
        viewModalInstance?.hide();

        const convId = normalizeId(els.viewModal.dataset.conversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (conv) {
            els.composeForm.dataset.conversationId = conv.id;
        }
        els.composeSubject.value = conv?.subject || '';
        els.composeBody.value = '';
        setRecipientsForReply(conv);

        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(els.composeModal).show();
        }, 300);
    }

    function setRecipientsForReply(conv) {
        if (!els.composeRecipients) return;
        if (!conv) {
            clearReplyRecipients();
            return;
        }
        const allIds = conv?.participants || [];
        const replyRecipients = allIds.filter(id => Number(id) !== Number(authUserId));

        $(els.composeRecipients).val(replyRecipients.map(String)).trigger('change');
        $(els.composeRecipients).prop('disabled', true);

        const group = els.composeRecipients.closest('.mb-3');
        if (group) group.classList.add('d-none');

        els.composeForm.dataset.isReply = '1';
    }

    function clearReplyRecipients() {
        if (!els.composeRecipients) return;
        $(els.composeRecipients).prop('disabled', false);

        const group = els.composeRecipients.closest('.mb-3');
        if (group) group.classList.remove('d-none');

        delete els.composeForm.dataset.isReply;
    }

    function setComposeSending(isSending) {
        if (!els.btnComposeSend) return;
        if (!els.btnComposeSend.dataset.defaultHtml) {
            els.btnComposeSend.dataset.defaultHtml = els.btnComposeSend.innerHTML;
        }

        els.btnComposeSend.disabled = isSending;
        els.btnComposeSend.dataset.loading = isSending ? '1' : '0';

        if (isSending) {
            els.btnComposeSend.innerHTML = '<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>در حال ارسال...';
            return;
        }

        els.btnComposeSend.innerHTML = els.btnComposeSend.dataset.defaultHtml;
    }

    function handleComposeSend() {
        if (els.btnComposeSend?.dataset.loading === '1') return;

        const subject = (els.composeSubject?.value || '').trim();
        const body = (els.composeBody?.value || '').trim();
        const recipientIds = $(els.composeRecipients || []).val() || [];
        const conversationId = els.composeForm.dataset.conversationId || '';
        const parentId = els.composeForm.dataset.parentId || '';

        if (!body || (!recipientIds.length && !conversationId)) return showToast('متن و گیرندگان الزامی است.');

        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('body', body);
        recipientIds.forEach(id => formData.append('recipients[]', id));
        if (conversationId) formData.append('conversation_id', conversationId);
        if (parentId) formData.append('parent_id', parentId);

        if (els.composeAttachment?.files?.length) {
            Array.from(els.composeAttachment.files).forEach(file => formData.append('attachments[]', file));
        }

        setComposeSending(true);
        fetch(window.CORRESPONDENCE_POST_URL, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
            body: formData
        })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                const modal = bootstrap.Modal.getInstance(els.composeModal) || new bootstrap.Modal(els.composeModal);
                modal.hide();

                const attachments = els.composeAttachment?.files
                    ? Array.from(els.composeAttachment.files).map(f => ({id: f.name,name: f.name,url: URL.createObjectURL(f),size: f.size,mime: f.type}))
                    : [];

                const newMessage = {
                    id: 'm' + data.message_id,
                    senderId: authUserId,
                    body,
                    time: new Date().toISOString(),
                    direction: 'outgoing',
                    attachments
                };

                const targetConversationId = data.conversation_id || conversationId || state.activeConversationId;
                const targetConvId = normalizeId(targetConversationId);
                let conv = conversations.find(c => normalizeId(c.id) === targetConvId);

                if (conv) {
                    const result = upsertConversationMessage(conv, newMessage, parentId);
                    conv.lastActivity = result.message.time || conv.lastActivity;
                } else {
                    const participants = Array.from(new Set([authUserId].concat(recipientIds.map(id => Number(id)))));
                    conv = {
                        id: targetConversationId,
                        subject: subject || '(بدون موضوع)',
                        participants,
                        unread: 0,
                        lastActivity: newMessage.time,
                        messages: {root: newMessage, replies: []}
                    };
                    conversations.unshift(conv);
                }

                state.activeConversationId = conv.id;
                state.activeMessageId = newMessage.id;
                renderMessageList();

                resetComposeForm();
                showToast('پیام با موفقیت ارسال شد.');
            })
            .catch(() => showToast('خطا در ارسال پیام.'))
            .finally(() => setComposeSending(false));
    }

    function resetComposeForm() {
        els.composeSubject.value = '';
        els.composeBody.value = '';
        els.composeAttachment.value = '';
        $(els.composeRecipients).val(null).trigger('change');
        delete els.composeForm.dataset.parentId;
        delete els.composeForm.dataset.conversationId;
        clearReplyRecipients();
    }

    function toggleArchive() {
        const convId = normalizeId(state.activeConversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (!conv) return;
        conv.archived = !conv.archived;
        toggleHeaderActions(conv.archived, conv.muted);
        renderMessageList();
        showToast(conv.archived ? 'مکالمه آرشیو شد.' : 'از آرشیو خارج شد.');
    }

    function toggleMute() {
        const convId = normalizeId(state.activeConversationId);
        const conv = conversations.find(c => normalizeId(c.id) === convId);
        if (!conv) return;
        conv.muted = !conv.muted;
        toggleHeaderActions(conv.archived, conv.muted);
        renderMessageList();
        showToast(conv.muted ? 'مکالمه بی‌صدا شد.' : 'صدا فعال شد.');
    }

    function toggleHeaderActions(isArchived,isMuted){
        if(els.actionArchiveModal) els.actionArchiveModal.classList.toggle('active',isArchived);
        if(els.actionMuteModal) els.actionMuteModal.classList.toggle('active',isMuted);
    }

    function showToast(message){
        const toastEl=document.createElement('div');
        toastEl.className='toast align-items-center text-bg-primary border-0 position-fixed';
        toastEl.style.zIndex=9999; toastEl.style.left='20px'; toastEl.style.bottom='20px'; toastEl.role='alert';
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex';
        const body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message || '';
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close btn-close-white me-2 m-auto';
        closeBtn.setAttribute('data-bs-dismiss', 'toast');
        closeBtn.setAttribute('aria-label', 'Close');
        wrapper.appendChild(body);
        wrapper.appendChild(closeBtn);
        toastEl.appendChild(wrapper);
        document.body.appendChild(toastEl);
        const toast=new bootstrap.Toast(toastEl,{delay:2200}); toast.show();
        toastEl.addEventListener('hidden.bs.toast',()=>toastEl.remove());
    }

    function scheduleRefresh() {
        if (!window.CORRESPONDENCE_REFRESH_URL) return;
        if (refreshTimer) return;
        refreshTimer = setTimeout(() => {
            refreshTimer = null;
            refreshConversations();
        }, 300);
    }

    function refreshConversations() {
        fetch(window.CORRESPONDENCE_REFRESH_URL, {
            headers: {'Accept': 'application/json'}
        })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                if (!data || !Array.isArray(data.conversations)) return;
                if (data.users) Object.assign(users, data.users);
                conversations.splice(0, conversations.length, ...data.conversations);
                renderMessageList();
                subscribeActiveConversationChannel();

                const isModalOpen = els.viewModal?.classList.contains('show');
                if (isModalOpen && state.activeConversationId) {
                    openMessageModal(state.activeConversationId);
                }
            })
            .catch(() => {});
    }

    function initRealtime(){
        subscribeRefreshChannel();
        subscribeActiveConversationChannel();
    }

    function ensureChannel(name) {
        if (!channelSubscriptions.has(name)) {
            pusher.subscribe(name);
            channelSubscriptions.add(name);
        }
        return pusher.channel(name);
    }

    function bindOnce(channel, event, handler) {
        const key = `${channel.name}:${event}`;
        if (channelBindings.has(key)) return;
        channel.bind(event, handler);
        channelBindings.add(key);
    }

    function subscribeRefreshChannel() {
        const refreshChannel = ensureChannel('correspondence.refresh');
        bindOnce(refreshChannel, 'correspondence.refresh', function(){
            scheduleRefresh();
        });
    }

    function subscribeActiveConversationChannel() {
        if (!state.activeConversationId) return;
        const nextChannelName = `private-conversation.${state.activeConversationId}`;
        if (activeConversationChannelName && activeConversationChannelName !== nextChannelName) {
            pusher.unsubscribe(activeConversationChannelName);
            channelSubscriptions.delete(activeConversationChannelName);
            channelBindings.delete(`${activeConversationChannelName}:message.sent`);
        }
        activeConversationChannelName = nextChannelName;

        const channel = ensureChannel(nextChannelName);
        bindOnce(channel, 'message.sent', function(data){
            const target = conversations.find(c => isSameId(c.id, data?.conversation_id));
            if (!target) {
                scheduleRefresh();
                return;
            }

            const incomingMessage = data?.message || {};
            const incomingParentId = data?.parent_id ?? data?.parentId ?? incomingMessage.parent_id ?? incomingMessage.parentId;
            const normalizedIncomingMessage = {
                ...incomingMessage,
                id: incomingMessage.id ?? data?.message_id ?? data?.messageId,
                senderId: incomingMessage.senderId ?? incomingMessage.sender_id ?? incomingMessage.sender?.id
            };

            const result = upsertConversationMessage(target, normalizedIncomingMessage, incomingParentId);

            target.lastActivity = normalizedIncomingMessage.time || target.lastActivity;
            if (result.inserted && Number(normalizedIncomingMessage.senderId) !== Number(authUserId)) {
                if (isSameId(state.activeConversationId, target.id)) {
                    markAsRead(target.id);
                } else {
                    target.unread = (Number(target.unread) || 0) + 1;
                }
            }

            if (isSameId(state.activeConversationId, target.id)) openMessageModal(target.id);
            renderMessageList();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
