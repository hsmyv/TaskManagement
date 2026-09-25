function taskDetail(taskId) {
    return {
        taskId,
        task:             {},
        comments:         [],
        newComment:       '',
        newChecklistItem: '',
        editingTitle:     false,
        editTitle:        '',
        editingDesc:      false,
        editDesc:         '',
        showSubtaskForm:  false,
        newSubtask:       { title:'', due_date:'' },
        _pollTimer:       null,

        editingDetails:  false,
        savingDetails:   false,
        detailForm:      {},

        editingAssignees:       false,
        savingAssignees:        false,
        selectedAssignees:      [],
        assigneeSearch:         '',
        showAssigneeSuggestions:false,
        spaceEmployees:         [],
        assigneeSuggestions:    [],

        statuses: [
            { value:'todo',                label:'Görüləcək',       icon:'📋' },
            { value:'in_progress',         label:'İcra olunur',     icon:'🔄' },
            { value:'waiting_for_approve', label:'Təsdiq gözləyir', icon:'⏳' },
            { value:'completed',           label:'Tamamlandı',      icon:'✅' },
            { value:'canceled',            label:'Ləğv olundu',     icon:'❌' },
        ],

        get checklistProgress() {
            const items = this.task.checklists || [];
            if (!items.length) return 0;
            return Math.round(items.filter(c => c.is_done).length / items.length * 100);
        },

        async init() {
            await this.loadTask();
            await this.loadComments();

            this._pollTimer = setInterval(() => {
                this.loadTask();
                this.loadComments();
            }, 20_000);

            window.addEventListener('confirm-status-change', async (e) => {
                await this.doChangeStatus(e.detail.status, e.detail.comment);
            });
        },

        async loadTask() {
            try {
                this.task = await api('GET', `/tasks/${this.taskId}`);
            } catch(e) {}
        },

        async loadComments() {
            try {
                this.comments = await api('GET', `/tasks/${this.taskId}/comments`);
            } catch(e) {}
        },

        openDetails() {
            this.detailForm = {
                start_date:      this.task.start_date      ?? '',
                due_date:        this.task.due_date        ?? '',
                priority:        this.task.priority        ?? 'medium',
                estimated_hours: this.task.estimated_hours ?? '',
            };
            this.editingDetails = true;
        },

        async saveDetails() {
            this.savingDetails = true;
            try {
                const payload = {
                    start_date:      this.detailForm.start_date      || null,
                    due_date:        this.detailForm.due_date         || null,
                    priority:        this.detailForm.priority,
                    estimated_hours: this.detailForm.estimated_hours  || null,
                };
                const updated = await api('PUT', `/tasks/${this.taskId}`, payload);
                this.task.start_date      = updated.start_date;
                this.task.due_date        = updated.due_date;
                this.task.priority        = updated.priority;
                this.task.estimated_hours = updated.estimated_hours;
                this.task.is_overdue      = updated.is_overdue;
                this.editingDetails = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Detallar yeniləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            } finally {
                this.savingDetails = false;
            }
        },

        async openAssignees() {
            this.selectedAssignees       = [...(this.task.assignees ?? [])];
            this.assigneeSearch          = '';
            this.assigneeSuggestions     = [];
            this.showAssigneeSuggestions = false;
            this.editingAssignees        = true;
        },

        async searchAssigneeEmployees() {
            if (this.assigneeSearch.length < 2) {
                this.assigneeSuggestions = [];
                this.showAssigneeSuggestions = false;
                return;
            }

            try {
                const data = await api('GET', `/employees/search?q=${encodeURIComponent(this.assigneeSearch)}`);
                const employees = Array.isArray(data) ? data : (data?.data || []);
                const selectedIds = this.selectedAssignees.map(e => e.id);
                this.assigneeSuggestions = employees.filter(e => !selectedIds.includes(e.id));
                this.showAssigneeSuggestions = this.assigneeSuggestions.length > 0;
            } catch(e) {
                this.assigneeSuggestions = [];
                this.showAssigneeSuggestions = false;
            }
        },

        addAssignee(emp) {
            if (!this.selectedAssignees.find(e => e.id === emp.id)) {
                this.selectedAssignees.push(emp);
            }
            this.assigneeSearch          = '';
            this.showAssigneeSuggestions = false;
        },

        removeAssignee(id) {
            this.selectedAssignees = this.selectedAssignees.filter(e => e.id !== id);
        },

        async saveAssignees() {
            this.savingAssignees = true;
            try {
                const updated = await api('PATCH', `/tasks/${this.taskId}/assignees`, {
                    assignee_ids: this.selectedAssignees.map(e => e.id),
                });
                this.task.assignees    = updated.assignees ?? this.selectedAssignees;
                this.editingAssignees  = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Məsul şəxslər yeniləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            } finally {
                this.savingAssignees = false;
            }
        },

        changeStatus(status) {
            window.dispatchEvent(new CustomEvent('open-status-modal', { detail: { status } }));
        },

        async doChangeStatus(status, comment) {
            try {
                const res          = await api('PATCH', `/tasks/${this.taskId}/status`, { status, comment });
                this.task.status       = res.status;
                this.task.status_label = res.status_label;
                await this.loadTask();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Status dəyişdirildi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        async approveTask() {
            try {
                await api('PATCH', `/tasks/${this.taskId}/approve`);
                await this.loadTask();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Təsdiqləndi!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        async saveTitle() {
            if (!this.editTitle.trim()) return;
            try {
                await api('PUT', `/tasks/${this.taskId}`, { title: this.editTitle });
                this.task.title   = this.editTitle;
                this.editingTitle = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Saxlandı', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        async saveDesc() {
            try {
                await api('PUT', `/tasks/${this.taskId}`, { description: this.editDesc });
                this.task.description = this.editDesc;
                this.editingDesc      = false;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        async submitComment() {
            if (!this.newComment.trim()) return;
            try {
                const comment = await api('POST', `/tasks/${this.taskId}/comments`, { body: this.newComment });
                this.comments.unshift(comment);
                this.newComment = '';
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        async deleteComment(commentId) {
            try {
                await api('DELETE', `/comments/${commentId}`);
                this.comments = this.comments.filter(c => c.id !== commentId);
            } catch(e) {}
        },

        async addChecklist() {
            if (!this.newChecklistItem.trim()) return;
            try {
                const item = await api('POST', `/tasks/${this.taskId}/checklists`, { title: this.newChecklistItem });
                if (!this.task.checklists) this.task.checklists = [];
                this.task.checklists.push(item);
                this.newChecklistItem = '';
            } catch(e) {}
        },

        async toggleChecklist(item) {
            try {
                const res = await api('PATCH', `/checklists/${item.id}/toggle`);
                item.is_done = res.is_done;
            } catch(e) {}
        },

        async deleteChecklist(id) {
            try {
                await api('DELETE', `/checklists/${id}`);
                this.task.checklists = this.task.checklists.filter(c => c.id !== id);
            } catch(e) {}
        },

        async createSubtask() {
            if (!this.newSubtask.title.trim()) return;
            try {
                const sub = await api('POST', `/tasks/${this.taskId}/subtasks`, this.newSubtask);
                if (!this.task.subtasks) this.task.subtasks = [];
                this.task.subtasks.push(sub);
                this.newSubtask      = { title:'', due_date:'' };
                this.showSubtaskForm = false;
            } catch(e) {}
        },

        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            try {
                const att = await api('POST', `/tasks/${this.taskId}/attachments`, fd, true);
                if (!this.task.attachments) this.task.attachments = [];
                this.task.attachments.push(att);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Fayl yükləndi!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
            event.target.value = '';
        },

        async deleteAttachment(id) {
            try {
                await api('DELETE', `/attachments/${id}`);
                this.task.attachments = this.task.attachments.filter(a => a.id !== id);
            } catch(e) {}
        },

        getExt(name)      { return name?.split('.').pop().toUpperCase().slice(0,4) || 'FILE'; },
        priorityLabel(p)  { return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[p] || p; },
        formatDate(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleDateString('az-AZ', {
                day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'
            });
        },
    }
}
