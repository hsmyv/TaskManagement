function adminSpaces() {
    return {
        spaces:      [],
        departments: [],
        allEmployees:[],
        members:     [],
        search:      '',
        filterDept:  '',
        filterStatus:'',

        showForm:    false,
        editMode:    false,
        saving:      false,
        error:       '',
        form:        {},

        showMembers:      false,
        selectedSpace:    null,
        newMember:        { employee_id: '', space_role: 'employee', _name: '' },

        showDelete:   false,
        deleteTarget: null,

        presetColors: ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#EC4899','#06B6D4','#64748B'],

        async load() {
            await Promise.all([
                this.loadSpaces(),
                this.loadDepartments(),
                this.loadAllEmployees(),
            ]);
        },

        async loadSpaces() {
            const data = await api('GET', '/spaces');
            this.spaces = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadDepartments() {
            const data = await api('GET', '/departments');
            this.departments = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadAllEmployees() {
            const data = await api('GET', '/employees');
            this.allEmployees = Array.isArray(data) ? data : (data?.data ?? []);
        },

        get filteredSpaces() {
            return this.spaces.filter(s => {
                const matchSearch = !this.search
                    || s.name.toLowerCase().includes(this.search.toLowerCase())
                    || (s.description ?? '').toLowerCase().includes(this.search.toLowerCase());
                const matchDept   = !this.filterDept   || s.department_id == this.filterDept;
                const matchStatus = this.filterStatus === ''
                    || (this.filterStatus === '1' ? s.is_active : !s.is_active);
                return matchSearch && matchDept && matchStatus;
            });
        },

        openCreate() {
            this.editMode = false;
            this.error    = '';
            this.form     = { name:'', description:'', color:'#3B82F6', department_id:'', manager_employee_id:'', is_active: true };
            this.showForm = true;
        },

        openEdit(space) {
            this.editMode = true;
            this.error    = '';
            this.form     = {
                id:            space.id,
                name:          space.name,
                description:   space.description ?? '',
                color:         space.color,
                department_id: space.department_id ?? '',
                manager_employee_id: space.manager_employee_id ?? space.manager?.id ?? '',
                is_active:     space.is_active,
            };
            this.showForm = true;
        },

        async submit() {
            this.error = '';
            if (!this.form.name.trim()) { this.error = 'Ad mütləqdir.'; return; }
            this.saving = true;
            try {
                const payload = {
                    name:          this.form.name,
                    description:   this.form.description || null,
                    color:         this.form.color,
                    department_id: this.form.department_id || null,
                    manager_employee_id: this.form.manager_employee_id || null,

                };

                if (this.editMode) {
                    payload.is_active = this.form.is_active;
                    const updated = await api('PUT', `/spaces/${this.form.id}`, payload);
                    const idx = this.spaces.findIndex(s => s.id === this.form.id);
                    if (idx !== -1) this.spaces[idx] = updated;
                } else {
                    const created = await api('POST', '/spaces', payload);
                    this.spaces.unshift(created);
                }

                this.showForm = false;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: this.editMode ? 'Space yeniləndi!' : 'Space yaradıldı!', type: 'success' }
                }));
            } catch(e) {
                this.error = e.message || 'Xəta baş verdi.';
            } finally {
                this.saving = false;
            }
        },

        async toggleActive(space) {
            try {
                const updated = await api('PUT', `/spaces/${space.id}`, { is_active: !space.is_active });
                const idx = this.spaces.findIndex(s => s.id === space.id);
                if (idx !== -1) this.spaces[idx] = updated;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: updated.is_active ? 'Aktiv edildi.' : 'Deaktiv edildi.', type: 'info' }
                }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        confirmDelete(space) {
            this.deleteTarget = space;
            this.showDelete   = true;
        },

        async deleteSpace() {
            this.saving = true;
            try {
                await api('DELETE', `/spaces/${this.deleteTarget.id}`);
                this.spaces     = this.spaces.filter(s => s.id !== this.deleteTarget.id);
                this.showDelete = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Space silindi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.saving = false;
            }
        },

        async openMembers(space) {
            this.selectedSpace = space;
            this.newMember     = { employee_id: '', space_role: 'employee', _name: '' };
            this.showMembers   = true;
            await this.loadMembers(space.id);
        },

        async loadMembers(spaceId) {
            const data     = await api('GET', `/spaces/${spaceId}/members`);
            this.members   = Array.isArray(data) ? data : (data?.data ?? []);
        },

        get availableEmployees() {
            const memberIds = this.members.map(m => m.id);
            return this.allEmployees.filter(e => !memberIds.includes(e.id));
        },

        filteredAvailable(q) {
            const lower = (q ?? '').toLowerCase();
            const memberIds = this.members.map(m => m.id);
            return this.allEmployees
                .filter(e => !memberIds.includes(e.id))
                .filter(e =>
                    e.full_name.toLowerCase().includes(lower) ||
                    (e.position ?? '').toLowerCase().includes(lower) ||
                    (e.department?.name ?? '').toLowerCase().includes(lower)
                )
                .slice(0, 10);
        },

        async addMember() {
            if (!this.newMember.employee_id) return;
            try {
                await api('POST', `/spaces/${this.selectedSpace.id}/members`, this.newMember);
                await this.loadMembers(this.selectedSpace.id);
                this.newMember = { employee_id: '', space_role: 'employee', _name: '' };
                const idx = this.spaces.findIndex(s => s.id === this.selectedSpace.id);
                if (idx !== -1) this.spaces[idx].members_count = (this.spaces[idx].members_count ?? 0) + 1;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv əlavə edildi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        async removeMember(member) {
            try {
                await api('DELETE', `/spaces/${this.selectedSpace.id}/members/${member.id}`);
                this.members = this.members.filter(m => m.id !== member.id);
                const idx = this.spaces.findIndex(s => s.id === this.selectedSpace.id);
                if (idx !== -1) this.spaces[idx].members_count = Math.max(0, (this.spaces[idx].members_count ?? 1) - 1);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv silindi.', type:'info' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },
    }
}
