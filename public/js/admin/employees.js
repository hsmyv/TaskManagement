
function adminEmployees() {
    return {
        employees:   [],
        departments: [],
        roles:       [],
        loading:     false,
        meta:        { current_page: 1, last_page: 1, total: 0 },
        filters:     { q: '', department_id: '', role: '', status: '' },
        page:        1,

        showForm:     false,
        editMode:     false,
        saving:       false,
        error:        '',
        form:         {},

        showDelete:   false,
        deleteTarget: null,

        roleLabelMap: {
            administrator:    'Administrator',
            executive_manager:'İdarə heyəti üzvü',
            senior_manager:   'Baş menecer',
            middle_manager:   'Menecer',
            employee:         'Əməkdaş',
        },

        async load() {
            await Promise.all([
                this.loadEmployees(),
                this.loadDepartments(),
                this.loadRoles(),
            ]);
        },

        async loadEmployees() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.page });
                if (this.filters.q)             params.set('q',             this.filters.q);
                if (this.filters.department_id) params.set('department_id', this.filters.department_id);
                if (this.filters.role)          params.set('role',          this.filters.role);
                if (this.filters.status)        params.set('status',        this.filters.status);

                const res        = await api('GET', `/admin/employees?${params}`);
                this.employees   = res.data ?? [];
                this.meta        = res.meta  ?? {};
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.loading = false;
            }
        },

        async loadDepartments() {
            const data = await api('GET', '/departments');
            this.departments = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadRoles() {
            const data = await api('GET', '/admin/roles');
            this.roles = Array.isArray(data) ? data : [];
        },

        roleLabel(name) {
            return this.roleLabelMap[name] ?? name;
        },

        prevPage() { if (this.page > 1) { this.page--; this.loadEmployees(); } },
        nextPage() { if (this.page < this.meta.last_page) { this.page++; this.loadEmployees(); } },

        openCreate() {
            this.editMode = false;
            this.error    = '';
            this.form     = { name:'', surname:'', patronymic:'', email:'', password:'',
                              phone:'', position:'', department_id:'', role:'', is_active: true };
            this.showForm = true;
        },

        openEdit(emp) {
            this.editMode = true;
            this.error    = '';
            this.form     = {
                id:            emp.id,
                name:          emp.name,
                surname:       emp.surname,
                patronymic:    emp.patronymic ?? '',
                email:         emp.email,
                password:      '',
                phone:         emp.phone ?? '',
                position:      emp.position ?? '',
                department_id: emp.department?.id ?? '',
                role:          emp.roles?.[0] ?? '',
                is_active:     emp.is_active,
            };
            this.showForm = true;
        },

        async submit() {
            this.error = '';
            if (!this.form.name.trim() || !this.form.surname.trim()) {
                this.error = 'Ad və soyad mütləqdir.'; return;
            }
            if (!this.form.email.trim()) { this.error = 'E-poçt mütləqdir.'; return; }
            if (!this.editMode && !this.form.password) { this.error = 'Şifrə mütləqdir.'; return; }
            if (!this.form.role) { this.error = 'Rol seçilməlidir.'; return; }

            this.saving = true;
            try {
                const payload = { ...this.form };
                if (!payload.password) delete payload.password;
                if (!payload.department_id) payload.department_id = null;

                let result;
                if (this.editMode) {
                    result = await api('PUT', `/admin/employees/${this.form.id}`, payload);
                    const idx = this.employees.findIndex(e => e.id === this.form.id);
                    if (idx !== -1) this.employees[idx] = result;
                } else {
                    result = await api('POST', '/admin/employees', payload);
                    this.employees.unshift(result);
                    this.meta.total = (this.meta.total ?? 0) + 1;
                }

                this.showForm = false;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: this.editMode ? 'Məlumatlar yeniləndi!' : 'Əməkdaş əlavə edildi!', type:'success' }
                }));
            } catch(e) {
                this.error = e.message;
            } finally {
                this.saving = false;
            }
        },

        async toggleActive(emp) {
            try {
                const result = await api('PATCH', `/admin/employees/${emp.id}/toggle`);
                const idx = this.employees.findIndex(e => e.id === emp.id);
                if (idx !== -1) this.employees[idx] = result;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: result.is_active ? 'Aktiv edildi.' : 'Deaktiv edildi.', type:'info' }
                }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        confirmDelete(emp) {
            this.deleteTarget = emp;
            this.showDelete   = true;
        },

        async deleteEmployee() {
            this.saving = true;
            try {
                await api('DELETE', `/admin/employees/${this.deleteTarget.id}`);
                this.employees  = this.employees.filter(e => e.id !== this.deleteTarget.id);
                this.meta.total = Math.max(0, (this.meta.total ?? 1) - 1);
                this.showDelete = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Əməkdaş silindi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.saving = false;
            }
        },
    }
}
