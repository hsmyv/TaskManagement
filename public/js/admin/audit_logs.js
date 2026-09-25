
function adminAuditLogs() {
    return {
        logs: [],
        options: { actions: [], boards: [], tasks: [] },
        loading: false,
        expanded: [],
        meta: { current_page: 1, last_page: 1, total: 0 },
        filters: {
            q: '',
            entity_type: '',
            action: '',
            task_id: '',
            board_id: '',
            from: '',
            to: '',
            per_page: 30,
            page: 1,
        },

        async load() {
            await this.loadOptions();
            await this.loadLogs();
        },

        async loadOptions() {
            try {
                this.options = await api('GET', '/admin/audit-logs/options');
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            }
        },

        async loadLogs(page = 1) {
            this.loading = true;
            this.filters.page = page;

            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) params.append(key, value);
            });

            try {
                const response = await api('GET', `/admin/audit-logs?${params.toString()}`);
                this.logs = response.data ?? [];
                this.meta = response.meta ?? { current_page: 1, last_page: 1, total: this.logs.length };
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                q: '',
                entity_type: '',
                action: '',
                task_id: '',
                board_id: '',
                from: '',
                to: '',
                per_page: 30,
                page: 1,
            };
            this.expanded = [];
            this.loadLogs();
        },

        prevPage() {
            if (this.meta.current_page > 1) this.loadLogs(this.meta.current_page - 1);
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) this.loadLogs(this.meta.current_page + 1);
        },

        toggleDetails(id) {
            this.expanded = this.expanded.includes(id)
                ? this.expanded.filter(item => item !== id)
                : [...this.expanded, id];
        },

        formatDate(value) {
            if (!value) return '—';
            return new Date(value).toLocaleString('az-AZ', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        },

        formatMeta(meta) {
            return JSON.stringify(meta ?? {}, null, 2);
        },

        entityLabel(type) {
            return {
                task: 'Tapşırıq',
                board: 'Board',
                board_list: 'Board list',
            }[type] ?? type;
        },

        actionLabel(action) {
            return {
                create: 'Yaradıldı',
                update: 'Yeniləndi',
                delete: 'Silindi',
                move: 'Daşındı',
                status_changed: 'Status dəyişdi',
                approved: 'Təsdiqləndi',
                assignees_updated: 'İcraçılar dəyişdi',
                helpers_updated: 'Köməkçilər dəyişdi',
                supervisors_updated: 'Müşahidəçilər dəyişdi',
                archive: 'Arxivləndi',
                unarchive: 'Arxivdən çıxarıldı',
            }[action] ?? action;
        },

        actionClass(action) {
            if (['delete', 'archive'].includes(action)) return 'bg-red-50 text-red-700';
            if (['create', 'approved'].includes(action)) return 'bg-green-50 text-green-700';
            if (['status_changed', 'move'].includes(action)) return 'bg-blue-50 text-blue-700';
            return 'bg-slate-100 text-slate-600';
        },
    };
}
