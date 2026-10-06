function adminDashboard() {
    return {
        loading: false,
        data: {
            cards: {},
            tasks_by_status: {},
            due_tomorrow: [],
            overdue: [],
            recent_logs: [],
            recent_notifications: [],
        },

        async load() {
            this.loading = true;
            try {
                this.data = await api('GET', '/admin/dashboard');
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            } finally {
                this.loading = false;
            }
        },

        get cards() {
            const c = this.data.cards || {};
            return [
                { key: 'employees', label: 'Əməkdaşlar', value: c.employees ?? 0, note: `${c.active_employees ?? 0} aktiv`, icon: '👥', bg: 'bg-blue-50' },
                { key: 'spaces', label: 'Space-lər', value: c.spaces ?? 0, note: `${c.active_spaces ?? 0} aktiv`, icon: '▦', bg: 'bg-indigo-50' },
                { key: 'boards', label: 'Boardlar', value: c.boards ?? 0, note: `${c.active_boards ?? 0} aktiv`, icon: '▤', bg: 'bg-green-50' },
                { key: 'tasks', label: 'Tapşırıqlar', value: c.tasks ?? 0, note: `${c.overdue_tasks ?? 0} gecikmiş · ${c.due_tomorrow_tasks ?? 0} sabah`, icon: '✓', bg: 'bg-amber-50' },
            ];
        },

        get totalTasks() {
            return Object.values(this.data.tasks_by_status || {}).reduce((sum, value) => sum + Number(value || 0), 0);
        },

        get statusRows() {
            const statuses = this.data.tasks_by_status || {};
            const labels = {
                todo: 'Görüləcək',
                in_progress: 'İcra olunur',
                waiting_for_approve: 'Təsdiq gözləyir',
                completed: 'Tamamlandı',
                canceled: 'Ləğv olundu',
            };
            const bars = {
                todo: 'bg-slate-400',
                in_progress: 'bg-blue-500',
                waiting_for_approve: 'bg-amber-500',
                completed: 'bg-green-500',
                canceled: 'bg-red-500',
            };

            return Object.keys(labels).map(key => {
                const value = Number(statuses[key] || 0);
                return {
                    key,
                    label: labels[key],
                    value,
                    percent: this.totalTasks ? Math.round((value / this.totalTasks) * 100) : 0,
                    bar: bars[key],
                };
            });
        },

        get overdue() {
            return this.data.overdue || [];
        },

        get dueTomorrow() {
            return this.data.due_tomorrow || [];
        },

        get recentLogs() {
            return this.data.recent_logs || [];
        },

        actionLabel(action) {
            return {
                create: 'yaratdı',
                update: 'yenilədi',
                delete: 'sildi',
                move: 'daşıdı',
                status_changed: 'status dəyişdi',
                approved: 'təsdiqlədi',
                assignees_updated: 'icraçıları dəyişdi',
                helpers_updated: 'köməkçiləri dəyişdi',
                supervisors_updated: 'müşahidəçiləri dəyişdi',
                archive: 'arxivlədi',
                unarchive: 'arxivdən çıxardı',
            }[action] ?? action;
        },

        formatDate(value) {
            if (!value) return '-';
            return window.formatShortDateTime ? window.formatShortDateTime(value) : value;
        },

        formatDateOnly(value) {
            if (!value) return '-';
            return window.formatShortDate ? window.formatShortDate(value) : value;
        },
    };
}
