function adminRoles() {
    return {
        roles:         [],
        loading:       true,
        showEmployees: false,
        selectedRole:  null,
        roleEmployees: [],
        empSearch:     '',

        get totalEmployees() {
            return this.roles.reduce((s, r) => s + (r.employee_count ?? 0), 0);
        },

        get filteredRoleEmployees() {
            if (!this.empSearch.trim()) return this.roleEmployees;
            const q = this.empSearch.toLowerCase();
            return this.roleEmployees.filter(e =>
                e.full_name.toLowerCase().includes(q) ||
                (e.position ?? '').toLowerCase().includes(q)
            );
        },

        async load() {
            this.loading = true;
            try {
                const data   = await api('GET', '/admin/roles');
                const counts = await api('GET', '/admin/employees?per_page=1').catch(() => null);
                this.roles   = Array.isArray(data) ? data : [];
                await this.loadCounts();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.loading = false;
            }
        },

        async loadCounts() {
            await Promise.all(this.roles.map(async (role) => {
                try {
                    const res = await api('GET', `/admin/employees?role=${role.name}&per_page=1`);
                    role.employee_count = res.meta?.total ?? 0;
                } catch(e) {
                    role.employee_count = 0;
                }
            }));
        },

        async loadRoleEmployees(role) {
            this.selectedRole  = role;
            this.empSearch     = '';
            this.showEmployees = true;
            this.roleEmployees = [];
            try {
                const res          = await api('GET', `/admin/employees?role=${role.name}&per_page=200`);
                this.roleEmployees = res.data ?? [];
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        roleIcon(name) {
            const icons = {
                administrator:    '🔑',
                executive_manager:'👔',
                senior_manager:   '🏆',
                middle_manager:   '📋',
                employee:         '👤',
            };
            return icons[name] ?? '⚙️';
        },

        roleColor(name) {
            const map = {
                administrator:    { bg: 'bg-red-100',    bar: 'bg-red-500' },
                executive_manager:{ bg: 'bg-orange-100', bar: 'bg-orange-500' },
                senior_manager:   { bg: 'bg-blue-100',   bar: 'bg-blue-500' },
                middle_manager:   { bg: 'bg-purple-100', bar: 'bg-purple-500' },
                employee:         { bg: 'bg-slate-100',  bar: 'bg-slate-400' },
            };
            return map[name] ?? { bg: 'bg-slate-100', bar: 'bg-slate-400' };
        },

        permLabel(perm) {
            const labels = {
                'space.create':             '➕ Space yarat',
                'space.update':             '✏️ Space redaktə',
                'space.delete':             '🗑️ Space sil',
                'space.view':               '👁️ Space görüntülə',
                'space.manage_members':     '👥 Üzvlər',
                'task.create':              '➕ Task yarat',
                'task.view.all':            '👁️ Bütün tasklar',
                'task.view.own':            '👁️ Öz taskları',
                'task.update.all':          '✏️ Bütün taskları redaktə',
                'task.update.own':          '✏️ Öz taskını redaktə',
                'task.delete.all':          '🗑️ Bütün taskları sil',
                'task.delete.own':          '🗑️ Öz taskını sil',
                'task.assign':              '👤 Məsul təyin et',
                'task.approve':             '✅ Təsdiqlə',
                'task.update.deadline.any': '📅 Deadline dəyiş',
                'comment.create':           '💬 Şərh yaz',
                'comment.delete.own':       '🗑️ Öz şərhini sil',
                'comment.delete.any':       '🗑️ Hər şərhi sil',
                'attachment.upload':        '📎 Fayl yüklə',
                'attachment.delete.own':    '🗑️ Öz faylını sil',
                'attachment.delete.any':    '🗑️ Hər faylı sil',
                'admin.access':             '🔐 Admin panel',
                'admin.manage_roles':       '⚙️ Rol idarəetmə',
                'admin.manage_employees':   '👥 Əməkdaş idarəetmə',
            };
            return labels[perm] ?? perm;
        },

        permColor(perm) {
            if (perm.startsWith('admin'))      return 'bg-red-50 text-red-700';
            if (perm.startsWith('space'))      return 'bg-blue-50 text-blue-700';
            if (perm.includes('delete'))       return 'bg-orange-50 text-orange-700';
            if (perm.includes('view'))         return 'bg-slate-100 text-slate-600';
            if (perm.includes('approve'))      return 'bg-green-50 text-green-700';
            return 'bg-purple-50 text-purple-700';
        },
    }
}
