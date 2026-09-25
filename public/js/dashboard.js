function dashboard() {
    return {
        stats: {},
        spaces: [],
        spaceStats: [],
        groupedTasks: {},
        spacesLoading: false,
        tasksLoading: false,
        showCreateModal: false,
        creating: false,
        taskModalOpen: false,
        taskDetail: null,
        taskLoading: false,
        quickComment: '',
        taskComments: [],
        taskCommentsLoading: false,
        editingTaskAssignees: false,
        selectedTaskAssignees: [],
        taskAssigneeSearch: '',
        taskAssigneeResults: [],
        editingTaskCollaborators: false,
        selectedTaskHelpers: [],
        selectedTaskSupervisors: [],
        taskHelperSearch: '',
        taskHelperResults: [],
        taskSupervisorSearch: '',
        taskSupervisorResults: [],
        editingTaskDates: false,
        taskDateForm: { start_date:'', due_date:'' },
        showInlineSubtaskForm: false,
        newInlineSubtask: { title:'', due_date:'', assignee_ids:[] },
        showChecklistForm: false,
        newChecklistItem: { title: '' },
        filters: { priority: '', status: '', due_days: '', overdue: false, space_id: '', q: '', onlyMe: true },
        newTask: {},
        statusSections: [
            { key:'todo', label:'Görüləcək' },
            { key:'in_progress', label:'İcra olunur' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir' },
            { key:'completed', label:'Tamamlandı' },
            { key:'canceled', label:'Ləğv olundu' },
        ],

        async init() {
            window.addEventListener('open-task-modal', event => {
                const taskId = event.detail?.taskId;
                if (taskId) this.openTaskModal(taskId);
            });
            await this.loadTasks();
        },

        async loadTasks() {
            this.spacesLoading = this.spaces.length === 0;
            this.tasksLoading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (key === 'onlyMe') return;
                    if (value === true) params.set(key, 1);
                    else if (value) params.set(key, value);
                });
                if (!this.filters.onlyMe) params.set('scope', 'all');
                const data = await api('GET', `/dashboard?${params.toString()}`);
                this.stats = data.stats || {};
                this.spaces = data.my_spaces || [];
                this.spaceStats = data.space_stats || [];
                this.groupedTasks = data.grouped_tasks || {};
                this.decorateSpaces();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            } finally {
                this.spacesLoading = false;
                this.tasksLoading = false;
            }
        },

        decorateSpaces() {
            const tasks = Object.values(this.groupedTasks).flat();
            this.spaces = this.spaces.map(space => ({
                ...space,
                overdue_count: tasks.filter(t => Number(t.space_id) === Number(space.id) && t.is_overdue).length,
            }));
        },

        statPart(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
        },

        allDashboardTasks() {
            return Object.values(this.groupedTasks || {}).flat();
        },

        setDueFilter(days) {
            if (this.isDueFilterActive(days)) {
                this.filters.due_days = '';
                this.loadTasks();
                return;
            }
            this.filters.overdue = false;
            this.filters.due_days = String(days);
            this.loadTasks();
        },

        setOverdueFilter() {
            this.filters.due_days = '';
            this.filters.overdue = !this.filters.overdue;
            this.loadTasks();
        },

        isDueFilterActive(days) {
            return !this.filters.overdue && String(this.filters.due_days) === String(days);
        },

        resetFilters() {
            this.filters = { priority: '', status: '', due_days: '', overdue: false, space_id: '', q: '', onlyMe: true };
            this.loadTasks();
        },

        orderedSpaces() {
            const selectedId = Number(this.filters.space_id || 0);
            const list = [...(this.spaces || [])];
            if (!selectedId) return list;

            return list.sort((a, b) => {
                if (Number(a.id) === selectedId) return -1;
                if (Number(b.id) === selectedId) return 1;
                return String(a.name || '').localeCompare(String(b.name || ''), 'az');
            });
        },

        statusColor(status) {
            return {
                todo: '#c9d6ea',
                in_progress: '#f6a21a',
                waiting_for_approve: '#9a67ff',
                completed: '#31d66d',
                canceled: '#ef5757',
            }[status] || '#ffffff';
        },

        statusTotal(status) {
            const fieldMap = {
                todo: 'todo_count',
                in_progress: 'in_progress_count',
                waiting_for_approve: 'waiting_count',
                completed: 'completed_count',
                canceled: 'canceled_count',
            };
            const field = fieldMap[status];
            if (!field) return 0;

            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space[field] || 0), 0);
        },

        overallTotal() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.tasks_total || 0), 0);
        },

        overallCompleted() {
            return this.statusTotal('completed');
        },

        overallOverdue() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.overdue_count || 0), 0);
        },

        overallBoards() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.boards_count || 0), 0);
        },

        activeTotal() {
            return this.statusTotal('todo') + this.statusTotal('in_progress') + this.statusTotal('waiting_for_approve');
        },

        statPercent(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
        },

        completionRate() {
            return this.statPercent(this.overallCompleted(), this.overallTotal());
        },

        overdueRate() {
            return this.statPercent(this.overallOverdue(), this.overallTotal());
        },

        statusDonutStyle() {
            const total = this.overallTotal();
            if (!total) return 'background: rgba(255,255,255,.12)';

            let start = 0;
            const segments = this.statusSections.map((section) => {
                const value = this.statusTotal(section.key);
                if (!value) return null;

                const end = start + (value / total) * 100;
                const segment = `${this.statusColor(section.key)} ${start}% ${end}%`;
                start = end;
                return segment;
            }).filter(Boolean);

            return segments.length
                ? `background: conic-gradient(${segments.join(', ')})`
                : 'background: rgba(255,255,255,.12)';
        },

        completionDonutStyle() {
            const percent = this.completionRate();
            return `background: conic-gradient(#31d66d 0 ${percent}%, rgba(255,255,255,.12) ${percent}% 100%)`;
        },

        overdueDonutStyle() {
            const percent = this.overdueRate();
            return `background: conic-gradient(#ef5757 0 ${percent}%, rgba(255,255,255,.12) ${percent}% 100%)`;
        },

        sortedSpaceStats() {
            return [...(this.spaceStats || [])].sort((a, b) => Number(b.tasks_total || 0) - Number(a.tasks_total || 0));
        },

        riskySpaces() {
            return [...(this.spaceStats || [])].sort((a, b) => Number(b.overdue_count || 0) - Number(a.overdue_count || 0));
        },

        spaceCompletion(space) {
            return this.statPercent(space?.completed_count || 0, space?.tasks_total || 0);
        },

        sortedByCompletion() {
            return [...(this.spaceStats || [])].sort((a, b) => {
                const diff = this.spaceCompletion(b) - this.spaceCompletion(a);
                if (diff !== 0) return diff;
                return Number(b.tasks_total || 0) - Number(a.tasks_total || 0);
            });
        },

        visibleStatusSections() {
            if (!this.filters.status) return this.statusSections;
            return this.statusSections.filter(s => s.key === this.filters.status);
        },

        openCreateTask() {
            this.newTask = {
                space_id: this.filters.space_id || '',
                title: '',
                description: '',
                priority: 'medium',
                visibility: 'all_members',
                start_date: new Date().toISOString().split('T')[0],
                due_date: '',
                assignee_ids: [],
                helper_ids: [],
                supervisor_ids: [],
                require_approval: false,
                deadline_locked: false,
                assigned_by_id: null,
            };
            this.showCreateModal = true;
        },

        async createTask() {
            if (!this.newTask.space_id || !this.newTask.title.trim()) return;
            this.creating = true;
            try {
                await api('POST', `/spaces/${this.newTask.space_id}/tasks`, this.newTask);
                this.showCreateModal = false;
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq yaradıldı!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.creating = false;
            }
        },

        async openTaskModal(id) {
            this.taskModalOpen = true;
            this.taskDetail = null;
            this.taskLoading = true;
            this.quickComment = '';
            this.taskComments = [];
            this.editingTaskAssignees = false;
            this.editingTaskCollaborators = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
            this.showChecklistForm = false;
            this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
            this.newChecklistItem = { title: '' };
            try {
                this.taskDetail = await api('GET', `/tasks/${id}`);
                await this.loadTaskComments();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
                this.taskModalOpen = false;
            } finally {
                this.taskLoading = false;
            }
        },

        closeTaskModal() {
            this.taskModalOpen = false;
            this.taskDetail = null;
            this.quickComment = '';
            this.taskComments = [];
            this.editingTaskAssignees = false;
            this.editingTaskCollaborators = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
            this.showChecklistForm = false;
            this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
            this.newChecklistItem = { title: '' };
        },

        canEditTask(task) {
            const authId = AUTH_USER?.id;
            return !!task && (task.can?.update || task.creator?.id === authId || task.assigned_by_id === authId || task.assigner?.id === authId);
        },

        prepareTaskDates() {
            this.taskDateForm = {
                start_date: this.taskDetail?.start_date || '',
                due_date: this.taskDetail?.due_date || '',
            };
        },

        async loadTaskComments() {
            if (!this.taskDetail?.id) return;
            this.taskCommentsLoading = true;
            try {
                const data = await api('GET', `/tasks/${this.taskDetail.id}/comments`);
                this.taskComments = Array.isArray(data) ? data : (data?.data || []);
            } catch(e) {
                this.taskComments = [];
            } finally {
                this.taskCommentsLoading = false;
            }
        },

        openTaskAssigneeEditor() {
            this.selectedTaskAssignees = [...(this.taskDetail?.assignees || [])];
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
            this.editingTaskAssignees = true;
        },

        async searchTaskAssignees() {
            if ((this.taskAssigneeSearch || '').length < 2) {
                this.taskAssigneeResults = [];
                return;
            }
            try {
                let url = `/employees/search?q=${encodeURIComponent(this.taskAssigneeSearch)}`;
                const data = await api('GET', url);
                const arr = Array.isArray(data) ? data : (data?.data || []);
                const ids = this.selectedTaskAssignees.map(e => e.id);
                this.taskAssigneeResults = arr.filter(e => !ids.includes(e.id));
            } catch(e) {
                this.taskAssigneeResults = [];
            }
        },

        selectTaskAssignee(emp) {
            if (!this.selectedTaskAssignees.find(e => e.id === emp.id)) this.selectedTaskAssignees.push(emp);
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
        },

        removeTaskAssignee(id) {
            this.selectedTaskAssignees = this.selectedTaskAssignees.filter(e => e.id !== id);
        },

        async saveTaskAssignees() {
            if (!this.taskDetail?.id) return;
            try {
                const updated = await api('PATCH', `/tasks/${this.taskDetail.id}/assignees`, { assignee_ids: this.selectedTaskAssignees.map(e => e.id) });
                this.taskDetail.assignees = updated.assignees ?? this.selectedTaskAssignees;
                this.editingTaskAssignees = false;
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        openTaskCollaboratorEditor() {
            this.selectedTaskHelpers = [...(this.taskDetail?.helpers || [])];
            this.selectedTaskSupervisors = [...(this.taskDetail?.supervisors || [])];
            this.taskHelperSearch = '';
            this.taskHelperResults = [];
            this.taskSupervisorSearch = '';
            this.taskSupervisorResults = [];
            this.editingTaskCollaborators = true;
        },

        async searchTaskRole(role) {
            const isHelper = role === 'helper';
            const search = isHelper ? this.taskHelperSearch : this.taskSupervisorSearch;
            if ((search || '').length < 2) {
                if (isHelper) this.taskHelperResults = [];
                else this.taskSupervisorResults = [];
                return;
            }

            try {
                let url = `/employees/search?q=${encodeURIComponent(search)}`;
                const data = await api('GET', url);
                const arr = Array.isArray(data) ? data : (data?.data || []);
                const selected = isHelper ? this.selectedTaskHelpers : this.selectedTaskSupervisors;
                const ids = selected.map(e => e.id);
                const filtered = arr.filter(e => !ids.includes(e.id));
                if (isHelper) this.taskHelperResults = filtered;
                else this.taskSupervisorResults = filtered;
            } catch(e) {
                if (isHelper) this.taskHelperResults = [];
                else this.taskSupervisorResults = [];
            }
        },

        selectTaskRole(role, emp) {
            if (role === 'helper') {
                if (!this.selectedTaskHelpers.find(e => e.id === emp.id)) this.selectedTaskHelpers.push(emp);
                this.taskHelperSearch = '';
                this.taskHelperResults = [];
                return;
            }

            if (!this.selectedTaskSupervisors.find(e => e.id === emp.id)) this.selectedTaskSupervisors.push(emp);
            this.taskSupervisorSearch = '';
            this.taskSupervisorResults = [];
        },

        removeTaskRole(role, id) {
            if (role === 'helper') {
                this.selectedTaskHelpers = this.selectedTaskHelpers.filter(e => e.id !== id);
                return;
            }

            this.selectedTaskSupervisors = this.selectedTaskSupervisors.filter(e => e.id !== id);
        },

        async saveTaskCollaborators() {
            if (!this.taskDetail?.id) return;
            try {
                const updated = await api('PUT', `/tasks/${this.taskDetail.id}`, {
                    helper_ids: this.selectedTaskHelpers.map(e => e.id),
                    supervisor_ids: this.selectedTaskSupervisors.map(e => e.id),
                });
                this.taskDetail.helpers = updated.helpers ?? this.selectedTaskHelpers;
                this.taskDetail.supervisors = updated.supervisors ?? this.selectedTaskSupervisors;
                this.editingTaskCollaborators = false;
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async saveTaskDates() {
            if (!this.taskDetail?.id) return;
            try {
                const updated = await api('PUT', `/tasks/${this.taskDetail.id}`, {
                    start_date: this.taskDateForm.start_date || null,
                    due_date: this.taskDateForm.due_date || null,
                });
                this.taskDetail.start_date = updated.start_date ?? this.taskDateForm.start_date;
                this.taskDetail.due_date = updated.due_date ?? this.taskDateForm.due_date;
                this.editingTaskDates = false;
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async createInlineSubtask() {
            if (!this.taskDetail?.id || !this.newInlineSubtask.title?.trim()) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/subtasks`, this.newInlineSubtask);
                this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
                this.showInlineSubtaskForm = false;
                await this.refreshTaskDetail();
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        canEditSubtask(subtask) {
            if (!subtask) return false;
            return !!(subtask.can?.update || subtask.creator?.id === AUTH_USER?.id || (subtask.assignees || []).some(person => person.id === AUTH_USER?.id));
        },

        prepareSubtaskEdit(subtask) {
            if (!subtask.edit) {
                subtask.edit = {
                    title: subtask.title || '',
                    due_date: subtask.due_date || '',
                    assignee_ids: (subtask.assignees || []).map(person => person.id),
                };
            }
        },

        async saveSubtask(subtask) {
            if (!subtask?.id || !subtask.edit?.title?.trim()) return;
            try {
                await api('PUT', `/tasks/${subtask.id}`, {
                    title: subtask.edit.title,
                    due_date: subtask.edit.due_date || null,
                });
                await api('PATCH', `/tasks/${subtask.id}/assignees`, {
                    assignee_ids: subtask.edit.assignee_ids || [],
                });
                subtask.editing = false;
                await this.refreshTaskDetail();
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq yeniləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xeta', type:'error' } }));
            }
        },

        async completeSubtask(subtask) {
            if (!subtask?.id || !this.canEditSubtask(subtask)) return;
            try {
                await api('PATCH', `/tasks/${subtask.id}/order`, { status: 'completed' });
                await this.refreshTaskDetail();
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq təsdiqləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xeta', type:'error' } }));
            }
        },

        async refreshTaskDetail() {
            if (!this.taskDetail?.id) return;
            this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
        },

        async createChecklistItem() {
            if (!this.taskDetail?.id || !this.newChecklistItem.title?.trim()) return;
            try {
                const item = await api('POST', `/tasks/${this.taskDetail.id}/checklists`, { title: this.newChecklistItem.title });
                if (!Array.isArray(this.taskDetail.checklists)) this.taskDetail.checklists = [];
                this.taskDetail.checklists.push(item);
                this.newChecklistItem = { title: '' };
                this.showChecklistForm = false;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async toggleChecklistItem(item) {
            if (!item?.id) return;
            try {
                const updated = await api('PATCH', `/checklists/${item.id}/toggle`);
                item.is_done = updated?.is_done ?? updated?.is_completed ?? !item.is_done;
                item.is_completed = updated?.is_completed ?? updated?.is_done ?? item.is_done;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async deleteChecklistItem(item) {
            if (!item?.id) return;
            try {
                await api('DELETE', `/checklists/${item.id}`);
                this.taskDetail.checklists = (this.taskDetail.checklists || []).filter(x => x.id !== item.id);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async uploadTaskFile(event) {
            const file = event.target.files?.[0];
            if (!file || !this.taskDetail?.id) return;
            const fd = new FormData();
            fd.append('file', file);
            try {
                const att = await api('POST', `/tasks/${this.taskDetail.id}/attachments`, fd, true);
                if (!Array.isArray(this.taskDetail.attachments)) this.taskDetail.attachments = [];
                this.taskDetail.attachments.push(att);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
            event.target.value = '';
        },

        async submitTaskComment() {
            if (!this.quickComment.trim() || !this.taskDetail?.id) return;
            try {
                const comment = await api('POST', `/tasks/${this.taskDetail.id}/comments`, { body: this.quickComment });
                this.taskComments.unshift(comment);
                this.quickComment = '';
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        attachmentExt(name) {
            return name?.split('.').pop()?.toUpperCase()?.slice(0, 4) || 'FILE';
        },

        statusLabel(status) {
            return this.statusSections.find(s => s.key === status)?.label || status || '-';
        },

        priorityLabel(priority) {
            return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[priority] || priority || '';
        },

        statusDotClass(status) {
            return {
                in_progress: 'bg-[#ffa80d]',
                waiting_for_approve: 'bg-[#8b5cf6]',
                completed: 'bg-[#00c83a]',
                todo: 'bg-[#cbd5e1]',
                canceled: 'bg-[#ff3030]',
            }[status] || 'bg-white/50';
        },

        statusTextClass(status) {
            return {
                in_progress: 'text-[#ffa80d]',
                waiting_for_approve: 'text-[#8b5cf6]',
                completed: 'text-[#00c83a]',
                todo: 'text-[#cbd5e1]',
                canceled: 'text-[#ff5757]',
            }[status] || 'text-white';
        },

        taskProgress(task) {
            if (!task) return 0;
            if (task.progress !== undefined && task.progress !== null && task.progress !== '') {
                const value = parseInt(task.progress, 10);
                return Number.isNaN(value) ? 0 : Math.max(0, Math.min(100, value));
            }
            const progress = task.checklist_progress;
            if (progress && typeof progress === 'object') return Number(progress.percentage || 0);
            if (Number.isFinite(Number(progress))) return Number(progress);
            if (task.status === 'completed') return 100;
            if (task.status === 'waiting_for_approve') return 85;
            if (task.status === 'in_progress') return 80;
            return 0;
        },

        formatDate(dt) {
            if (!dt) return '';
                const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return `${day}/${month}/${year}`;
        },
    }
}

function employeePicker(spaceId = null) {
    return {
        search: '',
        results: [],
        selected: [],
        open: false,
        spaceId,
        single: false,

        init(initialSelected = [], single = false) {
            this.single = single;
            this.selected = Array.isArray(initialSelected) ? initialSelected : [];
        },

        async searchEmployees(spaceId = null) {
            if ((this.search || '').length < 2) {
                this.results = [];
                return;
            }
            try {
                let url = `/employees/search?q=${encodeURIComponent(this.search)}`;
                const data = await api('GET', url);
                const arr = Array.isArray(data) ? data : (data?.data || []);
                this.results = this.single ? arr : arr.filter(e => !this.selected.find(s => s.id === e.id));
                this.open = true;
            } catch(e) {
                this.results = [];
            }
        },

        async loadAllEmployees() {
            try {
                const data = await api('GET', '/employees');
                const arr = Array.isArray(data) ? data : (data?.data || []);
                this.results = this.single ? arr : arr.filter(e => !this.selected.find(s => s.id === e.id));
                this.open = true;
            } catch(e) {
                this.results = [];
            }
        },

        select(emp) {
            if (this.single) {
                this.selected = [emp];
            } else if (!this.selected.find(s => s.id === emp.id)) {
                this.selected.push(emp);
            }
            this.search = '';
            this.results = [];
            this.open = false;
        },

        remove(id) {
            this.selected = this.selected.filter(e => e.id !== id);
        },
    }
}
