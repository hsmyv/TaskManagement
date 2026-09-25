const API_BASE   = '/api';
const CSRF_TOKEN = document.querySelector('meta[name=csrf-token]').getAttribute('content');


async function api(method, url, data = null, isFormData = false) {
    const opts = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
        },
        credentials: 'same-origin',
    };
    if (data) opts.body = isFormData ? data : JSON.stringify(data);
    const res = await fetch(API_BASE + url, opts);
    if (!res.ok) {
        const err = await res.json().catch(() => ({ message: 'Xəta baş verdi.' }));
        throw new Error(err.message || 'Xəta');
    }
    if (res.status === 204) return null;
    return res.json();
}

function appLayout() {
    return {}
}

function notificationBell() {
    return {
        open: false,
        unread: 0,
        notifications: [],
        _timer: null,

        init() {
            this.fetchUnreadCount();
            this._timer = setInterval(() => this.fetchUnreadCount(), 30_000);
        },

        async fetchUnreadCount() {
            try {
                const res  = await api('GET', '/notifications/unread-count');
                const prev = this.unread;
                this.unread = res.count;
                if (res.count > prev && prev !== null) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Yeni bildiriş var 🔔', type: 'info' }
                    }));
                }
            } catch(e) {}
        },

        async loadNotifications() {
            try {
                const res          = await api('GET', '/notifications?per_page=20');
                this.notifications = res.data;
                this.unread        = res.unread;
            } catch(e) {}
        },

        async markRead(n) {
            if (!n.is_read) {
                await api('PATCH', `/notifications/${n.id}/read`);
                n.is_read = true;
                this.unread = Math.max(0, this.unread - 1);
            }

            const taskId = n.data?.task_id || n.notifiable_entity_id;
            if (taskId) {
                this.open = false;
                if (window.location.pathname.startsWith('/tasks')) {
                    window.location.href = `/tasks/${taskId}`;
                    return;
                }

                window.dispatchEvent(new CustomEvent('open-task-modal', { detail: { taskId } }));
            }
        },

        async markAllRead() {
            await api('PATCH', '/notifications/read-all');
            this.notifications.forEach(n => n.is_read = true);
            this.unread = 0;
        },

        // ── Event tipinə görə ikona ──────────────────────────────────────
        notificationIcon(n) {
            const icons = {
                task_created:        '📋',
                task_updated:        '✏️',
                task_deleted:        '🗑️',
                assignee_changed:    '👤',
                status_changed:      '🔄',
                comment_added:       '💬',
                attachment_added:    '📎',
                attachment_deleted:  '🗑️',
                approval_requested:  '⏳',
                task_approved:       '✅',
                deadline_reminder:   '⏰',
                task_overdue:        '🔴',
            };
            return icons[n.event] ?? '🔔';
        },

        notificationText(n) {
            const d = n.data ?? {};
            const title = d.task_title ? `"${d.task_title}"` : 'tapşırıq';

            const map = {
                task_created:       () => `${d.created_by ?? 'Biri'} yeni tapşırıq yaratdı: ${title}`,
                task_updated:       () => `${d.updated_by ?? 'Biri'} tapşırığı yenilədi: ${title}`,
                task_deleted:       () => `${d.deleted_by ?? 'Biri'} tapşırığı sildi: ${title}`,
                assignee_changed:   () => `${d.assigned_by ?? 'Biri'} sizi tapşırığa əlavə etdi: ${title}`,
                status_changed:     () => `${d.changed_by ?? 'Biri'} statusu dəyişdi: ${d.from_label ?? d.from_status} → ${d.to_label ?? d.to_status} (${title})`,
                comment_added:      () => `${d.commented_by ?? 'Biri'} şərh yazdı: ${title}`,
                attachment_added:   () => `${d.uploaded_by ?? 'Biri'} fayl əlavə etdi: ${title}`,
                attachment_deleted: () => `${d.deleted_by ?? 'Biri'} faylı sildi: ${title}`,
                approval_requested: () => `Təsdiqiniz gözlənilir: ${title}`,
                task_approved:      () => `${d.approved_by ?? 'Biri'} tapşırığı təsdiqlədi: ${title}`,
                deadline_reminder:  () => `Deadline yaxınlaşır (${d.due_date ?? ''}): ${title}`,
                task_overdue:       () => `Gecikmiş tapşırıq: ${title}`,
            };

            return map[n.event]?.() ?? (d.task_title ?? 'Yeni bildiriş');
        },

        formatDate(dt) {
            return new Date(dt).toLocaleDateString('az-AZ', {
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        }
    }
}

function createSpaceModal() {
    return {
        open:        false,
        saving:      false,
        error:       '',
        departments: [],
        form:        { name: '', description: '', color: '#3B82F6', department_id: '' },

        async init() {
            try {
                const data = await api('GET', '/departments');
                this.departments = Array.isArray(data) ? data : (data?.data ?? []);
            } catch(e) {}
        },

        async submit() {
            this.error = '';
            if (!this.form.name.trim()) {
                this.error = 'Space adı mütləqdir.';
                return;
            }
            this.saving = true;
            try {
                await api('POST', '/spaces', {
                    name:          this.form.name,
                    description:   this.form.description || null,
                    color:         this.form.color,
                    department_id: this.form.department_id || null,
                });
                this.open = false;
                this.form = { name: '', description: '', color: '#3B82F6', department_id: '' };
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Space uğurla yaradıldı!', type: 'success' }
                }));
                setTimeout(() => window.location.reload(), 800);
            } catch(e) {
                this.error = e.message || 'Xəta baş verdi.';
            } finally {
                this.saving = false;
            }
        }
    }
}



function toastManager() {
    return {
        toasts: [],
        addToast({ message, type = 'info' }) {
            const id = Date.now();
            this.toasts.push({ id, message, type, visible: true });
            setTimeout(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = false;
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 500);
            }, 4000);
        }
    }
}
