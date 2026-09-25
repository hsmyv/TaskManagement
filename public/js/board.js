function boardHub(spaceId, boardId) {
    return {
        spaceId,
        boardId,
        grouped: {},
        membersLoading: false,
        members: [],
        commentsLoading: false,
        comments: [],
        quickComment: '',
        replyingTo: null,
        replyText: '',
        expandedComments: {},
        editingTaskAssignees: false,
        selectedTaskAssignees: [],
        taskAssigneeSearch: '',
        taskAssigneeResults: [],
        editingTaskMain: false,
        taskMainForm: { title:'', description:'' },
        editingTaskDates: false,
        taskDateForm: { start_date:'', due_date:'' },
        showInlineSubtaskForm: false,
        newInlineSubtask: { title:'', due_date:'', assignee_ids:[] },
        showChecklistForm: false,
        newChecklistItem: { title: '' },
        showCreateModal: false,
        creating: false,
        draggedTask: null,
        statusUpdating: false,
        newTask: { title:'', description:'', priority:'medium', visibility:'all_members', start_date:'', due_date:'', assignee_ids:[], assigned_by_id: null },
        newTaskSubtaskDraft: { title:'', due_date:'' },
        newTaskSubtasks: [],
        newTaskSubtaskAssigneeSearch: '',
        newTaskSubtaskAssigneeResults: [],
        newTaskSubtaskAssignees: [],
        approvingTask: false,
        cancelingTask: false,
        defaultAvatar: 'https://ui-avatars.com/api/?name=User&background=1f3b75&color=fff',
        filters: {
            priority: '',
            status: '',
            dueSoon: false,
            overdue: false,
            onlyMe: false,
            dateFrom: '',
            dateTo: '',
        },
        filterMenus: [
            { key:'priority', options:[
                { value:'', label:'Bütün prioritetlər' },
                { value:'low', label:'Aşağı' },
                { value:'medium', label:'Orta' },
                { value:'high', label:'Yüksək' },
                { value:'urgent', label:'Təcili' },
            ] },
            { key:'status', options:[
                { value:'', label:'Bütün statuslar' },
                { value:'todo', label:'Görüləcək' },
                { value:'in_progress', label:'İcra olunur' },
                { value:'waiting_for_approve', label:'Təsdiq gözləyir' },
                { value:'completed', label:'Tamamlandı' },
                { value:'canceled', label:'Ləğv olundu' },
            ] },
            { key:'dueSoon', options:[
                { value:false, label:'Bütün tarixlər' },
                { value:true, label:'Son 7 gün' },
            ] },
            { key:'overdue', options:[
                { value:false, label:'Hamısı' },
                { value:true, label:'Gecikmiş' },
            ] },
        ],
        statusSections: [
            { key:'todo',                label:'Görüləcək',       color:'#c4c8d6' },
            { key:'in_progress',         label:'İcra olunur',     color:'#f7aa14' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir', color:'#955bf7' },
            { key:'completed',           label:'Tamamlandı',      color:'#0cc53f' },
            { key:'canceled',            label:'Ləğv olundu',     color:'#ef4444' },
        ],

        taskModalOpen: false,
        taskDetail: null,

        get displayMembers() {
            return this.members;
        },

        async init() {
            const savedOnlyMe = localStorage.getItem(`board:${this.boardId}:onlyMe`);
            this.filters.onlyMe = savedOnlyMe === null ? true : savedOnlyMe === '1';
            window.addEventListener('open-task-modal', event => {
                const taskId = event.detail?.taskId;
                if (taskId) this.openTaskModal(taskId);
            });
            await this.refresh();
        },

        allTasks() {
            return Object.values(this.grouped || {}).flatMap(items => Array.isArray(items) ? items : []);
        },

        collectMembersFromTasks() {
            const map = new Map();
            this.allTasks().forEach(task => {
                (task.assignees || []).forEach(person => {
                    if (person?.id && !map.has(person.id)) {
                        map.set(person.id, person);
                    }
                });
            });
            this.members = Array.from(map.values());
        },

        filterLabel(key) {
            const menu = this.filterMenus.find(item => item.key === key);
            const option = menu?.options.find(item => item.value === this.filters[key]);
            return option?.label || menu?.options?.[0]?.label || '';
        },

        isFilterSelected(key, value) {
            return this.filters[key] === value;
        },

        async setFilter(key, value) {
            this.filters[key] = value;
            if (key === 'onlyMe') {
                localStorage.setItem(`board:${this.boardId}:onlyMe`, value ? '1' : '0');
            }
            await this.refresh();
        },

        async refresh() {
            this.membersLoading = true;
            const params = new URLSearchParams();
            params.set('board_id', this.boardId);
            params.set('grouped', '1');
            if (this.filters.priority) params.set('priority', this.filters.priority);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.dueSoon) params.set('due_soon', '1');
            if (this.filters.overdue) params.set('overdue', '1');
            if (this.filters.dateFrom) params.set('due_date_from', this.filters.dateFrom);
            if (this.filters.dateTo) params.set('due_date_to', this.filters.dateTo);
            if (this.filters.onlyMe && AUTH_USER?.id) params.set('assignee_id', AUTH_USER.id);

            const data = await api('GET', `/spaces/${this.spaceId}/tasks?${params.toString()}`);
            this.grouped = data || {};

            if (this.filters.status && !this.grouped[this.filters.status]) {
                this.grouped[this.filters.status] = [];
            }

            this.collectMembersFromTasks();
            this.membersLoading = false;
        },

        onDragTaskStart(task, event) {
            this.draggedTask = task;
            event?.dataTransfer?.setData('text/plain', String(task.id));
            if (event?.dataTransfer) event.dataTransfer.effectAllowed = 'move';
        },

        allowedNextStatuses(task) {
            if (!task) return [];
            const canManagerReturn = task.status === 'completed' && this.canReturnCompletedTask(task);
            if (canManagerReturn) return ['todo', 'in_progress', 'waiting_for_approve'];
            return {
                todo: ['in_progress', 'canceled'],
                in_progress: ['waiting_for_approve', 'canceled'],
                waiting_for_approve: ['completed', 'in_progress', 'canceled'],
                completed: [],
                canceled: ['todo'],
            }[task.status] || [];
        },

        canReturnCompletedTask(task) {
            if (!task) return false;
            const roles = AUTH_USER?.roles || [];
            return !!(task.can?.update || roles.includes('administrator') || roles.includes('executive_manager') || roles.includes('senior_manager') || roles.includes('middle_manager'));
        },

        canDropOnStatus(status) {
            if (!this.draggedTask || this.statusUpdating || this.draggedTask.status === status) return false;
            if (!this.allowedNextStatuses(this.draggedTask).includes(status)) return false;
            if (status === 'completed') return this.canApproveTask(this.draggedTask);
            if (status === 'canceled') return this.canCancelTask(this.draggedTask);
            return true;
        },

        async onDropToStatus(status) {
            const task = this.draggedTask;
            const canDrop = this.canDropOnStatus(status);
            this.draggedTask = null;
            if (!task?.id || !canDrop) return;
            this.statusUpdating = true;
            try {
                if (status === 'completed') {
                    await api('PATCH', `/tasks/${task.id}/approve`);
                } else {
                    await api('PATCH', `/tasks/${task.id}/order`, { status });
                }
                await this.refresh();
                if (this.taskDetail?.id === task.id) await this.refreshTaskDetail();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Status dəyişmədi', type:'error' } }));
            } finally {
                this.statusUpdating = false;
            }
        },

        openCreateTask() {
            this.newTask = {
                title: '',
                description: '',
                priority: 'medium',
                visibility: 'all_members',
                start_date: new Date().toISOString().split('T')[0],
                due_date: '',
                assignee_ids: [],
                assigned_by_id: null,
            };
            this.newTaskSubtaskDraft = { title:'', due_date:'' };
            this.newTaskSubtasks = [];
            this.newTaskSubtaskAssigneeSearch = '';
            this.newTaskSubtaskAssigneeResults = [];
            this.newTaskSubtaskAssignees = [];
            this.showCreateModal = true;
        },

        addNewTaskSubtask() {
            const title = (this.newTaskSubtaskDraft.title || '').trim();
            if (!title) return;
            this.newTaskSubtasks.push({
                title,
                due_date: this.newTaskSubtaskDraft.due_date || '',
                assignee_ids: this.newTaskSubtaskAssignees.map(person => person.id),
                assignees: [...this.newTaskSubtaskAssignees],
            });
            this.newTaskSubtaskDraft = { title:'', due_date:'' };
            this.newTaskSubtaskAssigneeSearch = '';
            this.newTaskSubtaskAssigneeResults = [];
            this.newTaskSubtaskAssignees = [];
        },

        removeNewTaskSubtask(index) {
            this.newTaskSubtasks.splice(index, 1);
        },

        async searchNewTaskSubtaskAssignees() {
            if ((this.newTaskSubtaskAssigneeSearch || '').length < 1) {
                this.newTaskSubtaskAssigneeResults = [];
                return;
            }
            try {
                const data = await api('GET', `/employees/search?q=${encodeURIComponent(this.newTaskSubtaskAssigneeSearch)}`);
                const arr = Array.isArray(data) ? data : (data?.data || []);
                const selectedIds = this.newTaskSubtaskAssignees.map(person => person.id);
                this.newTaskSubtaskAssigneeResults = arr.filter(person => !selectedIds.includes(person.id));
            } catch(e) {
                this.newTaskSubtaskAssigneeResults = [];
            }
        },

        selectNewTaskSubtaskAssignee(emp) {
            if (!this.newTaskSubtaskAssignees.find(person => person.id === emp.id)) {
                this.newTaskSubtaskAssignees.push(emp);
            }
            this.newTaskSubtaskAssigneeSearch = '';
            this.newTaskSubtaskAssigneeResults = [];
        },

        removeNewTaskSubtaskAssignee(id) {
            this.newTaskSubtaskAssignees = this.newTaskSubtaskAssignees.filter(person => person.id !== id);
        },

        async createDraftSubtasks(parentTaskId, subtasks) {
            for (const subtask of subtasks) {
                await api('POST', `/tasks/${parentTaskId}/subtasks`, {
                    title: subtask.title,
                    due_date: subtask.due_date || null,
                    assignee_ids: subtask.assignee_ids || [],
                });
            }
        },

        async createTask() {
            if (!this.newTask.title?.trim()) return;
            this.creating = true;
            try {
                this.addNewTaskSubtask();
                const subtasks = [...this.newTaskSubtasks];
                const response = await api('POST', `/boards/${this.boardId}/tasks`, this.newTask);
                const created = response?.data || response;
                if (created?.id && subtasks.length) await this.createDraftSubtasks(created.id, subtasks);
                this.showCreateModal = false;
                this.newTaskSubtaskDraft = { title:'', due_date:'' };
                this.newTaskSubtasks = [];
                this.newTaskSubtaskAssigneeSearch = '';
                this.newTaskSubtaskAssigneeResults = [];
                this.newTaskSubtaskAssignees = [];
                await this.refresh();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq yaradıldı', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.creating = false;
            }
        },

        exportTasks() {
            const params = new URLSearchParams();
            params.set('board_id', this.boardId);
            if (this.filters.priority) params.set('priority', this.filters.priority);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.dueSoon) params.set('due_soon', '1');
            if (this.filters.overdue) params.set('overdue', '1');
            if (this.filters.dateFrom) params.set('due_date_from', this.filters.dateFrom);
            if (this.filters.dateTo) params.set('due_date_to', this.filters.dateTo);
            if (this.filters.onlyMe && AUTH_USER?.id) params.set('assignee_id', AUTH_USER.id);
            window.location.href = `/api/spaces/${this.spaceId}/tasks/export?${params.toString()}`;
        },

        async archiveBoard() {
            if (!confirm('Board arxivlənsin?')) return;
            try {
                await api('PATCH', `/boards/${this.boardId}/archive`);
                window.location.href = `/spaces/${this.spaceId}`;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Board arxivlənmədi', type:'error' } }));
            }
        },

        async openTaskModal(id) {
            this.taskModalOpen = true;
            this.taskDetail = null;
            this.comments = [];
            this.quickComment = '';
            this.replyingTo = null;
            this.replyText = '';
            this.expandedComments = {};
            this.editingTaskAssignees = false;
            this.editingTaskMain = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
            this.showChecklistForm = false;
            this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
            this.newChecklistItem = { title: '' };
            this.commentsLoading = true;
            try {
                this.taskDetail = await api('GET', `/tasks/${id}`);
                try {
                    const commentsRes = await api('GET', `/tasks/${id}/comments`);
                    this.comments = Array.isArray(commentsRes) ? commentsRes : (commentsRes.data || []);
                } catch (e) {
                    this.comments = this.taskDetail?.comments || [];
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Xəta', type: 'error' } }));
                this.taskModalOpen = false;
            } finally {
                this.commentsLoading = false;
            }
        },

        closeTaskModal() {
            this.taskModalOpen = false;
            this.taskDetail = null;
            this.comments = [];
            this.quickComment = '';
            this.replyingTo = null;
            this.replyText = '';
            this.expandedComments = {};
            this.editingTaskAssignees = false;
            this.editingTaskMain = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
            this.showChecklistForm = false;
        },

        canEditTask(task) {
            const authId = AUTH_USER?.id;
            return !!task && (task.can?.update || task.creator?.id === authId || task.assigned_by_id === authId || task.assigner?.id === authId);
        },

        canEditSubtask(subtask) {
            return !!subtask && !!(subtask.can?.update || subtask.creator?.id === AUTH_USER?.id || (subtask.assignees || []).some(person => person.id === AUTH_USER?.id));
        },

        openTaskMainEditor() {
            this.taskMainForm = {
                title: this.taskDetail?.title || '',
                description: this.taskDetail?.description || '',
            };
            this.editingTaskMain = true;
        },

        async saveTaskMain() {
            if (!this.taskDetail?.id || !this.taskMainForm.title?.trim()) return;
            try {
                const updated = await api('PUT', `/tasks/${this.taskDetail.id}`, {
                    title: this.taskMainForm.title,
                    description: this.taskMainForm.description || null,
                });
                this.taskDetail.title = updated.title ?? this.taskMainForm.title;
                this.taskDetail.description = updated.description ?? this.taskMainForm.description;
                this.editingTaskMain = false;
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Tapşırıq yenilənmədi', type:'error' } }));
            }
        },

        canToggleChecklistItem(task) {
            return !!task && !!(task.can?.toggle_checklist || task.creator?.id === AUTH_USER?.id || (task.assignees || []).some(person => person.id === AUTH_USER?.id));
        },

        canApproveTask(task) {
            return !!task && !!(task.can?.approve || task.creator?.id === AUTH_USER?.id);
        },

        canShowApprovePrompt(task) {
            return !!task && task.status === 'waiting_for_approve' && this.canApproveTask(task);
        },

        canCancelTask(task) {
            return !!task && task.status !== 'completed' && task.status !== 'canceled' && task.creator?.id === AUTH_USER?.id;
        },

        async saveTaskPriority(priority) {
            if (!this.taskDetail?.id) return;
            try {
                await api('PUT', `/tasks/${this.taskDetail.id}`, { priority });
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Prioritet dəyişmədi', type:'error' } }));
            }
        },

        async saveTaskStatus(status) {
            if (!this.taskDetail?.id) return;
            try {
                if (status === 'completed') {
                    await api('PATCH', `/tasks/${this.taskDetail.id}/approve`);
                } else {
                    await api('PATCH', `/tasks/${this.taskDetail.id}/order`, { status });
                }
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                await this.refreshTaskDetail().catch(() => {});
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Status dəyişmədi', type:'error' } }));
            }
        },

        scrollToApproval() {
            this.$nextTick(() => {
                const panel = this.$refs.approvalPanelVisible || this.$refs.approvalPanel;
                const body = this.$refs.taskModalBody;

                if (!panel) return;

                if (body) {
                    body.scrollTo({
                        top: Math.max(panel.offsetTop - body.offsetTop - 16, 0),
                        behavior: 'smooth',
                    });
                    return;
                }

                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        prepareTaskDates() {
            this.taskDateForm = {
                start_date: this.taskDetail?.start_date || '',
                due_date: this.taskDetail?.due_date || '',
            };
        },

        openTaskAssigneeEditor() {
            this.selectedTaskAssignees = [...(this.taskDetail?.assignees || [])];
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
            this.editingTaskAssignees = true;
        },

        async searchTaskAssignees() {
            if ((this.taskAssigneeSearch || '').length < 1) {
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
                await this.refresh();
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
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
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

        async refreshTaskDetail() {
            if (!this.taskDetail?.id) return;
            this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
        },

        async createInlineSubtask() {
            if (!this.taskDetail?.id || !this.newInlineSubtask.title?.trim()) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/subtasks`, this.newInlineSubtask);
                this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
                window.dispatchEvent(new CustomEvent('reset-employee-picker'));
                this.showInlineSubtaskForm = false;
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async saveSubtask(subtask) {
            if (!subtask?.id || !subtask.edit?.title?.trim()) return;
            try {
                await api('PUT', `/tasks/${subtask.id}`, { title: subtask.edit.title, due_date: subtask.edit.due_date || null });
                await api('PATCH', `/tasks/${subtask.id}/assignees`, { assignee_ids: subtask.edit.assignee_ids || [] });
                subtask.editing = false;
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async completeSubtask(subtask) {
            if (!subtask?.id || !this.canEditSubtask(subtask)) return;
            try {
                await api('PATCH', `/tasks/${subtask.id}/order`, { status: 'completed' });
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async createChecklistItem() {
            if (!this.taskDetail?.id || !this.newChecklistItem.title?.trim()) return;
            try {
                const item = await api('POST', `/tasks/${this.taskDetail.id}/checklists`, { title: this.newChecklistItem.title });
                if (!Array.isArray(this.taskDetail.checklists)) this.taskDetail.checklists = [];
                this.taskDetail.checklists.push(item);
                this.newChecklistItem = { title: '' };
                this.showChecklistForm = false;
                await this.refreshTaskDetail();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async toggleChecklistItem(item) {
            if (!item?.id || !this.canToggleChecklistItem(this.taskDetail)) return;
            try {
                const updated = await api('PATCH', `/checklists/${item.id}/toggle`);
                item.is_done = !!(updated?.is_done ?? updated?.is_completed ?? !item.is_done);
                item.is_completed = item.is_done;
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async deleteChecklistItem(item) {
            if (!item?.id) return;
            try {
                await api('DELETE', `/checklists/${item.id}`);
                this.taskDetail.checklists = (this.taskDetail.checklists || []).filter(x => x.id !== item.id);
                await this.refresh();
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

        async approveTask() {
            if (!this.taskDetail?.id || !this.canShowApprovePrompt(this.taskDetail)) return;
            this.approvingTask = true;
            try {
                await api('PATCH', `/tasks/${this.taskDetail.id}/approve`);
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.approvingTask = false;
            }
        },

        async cancelTask() {
            if (!this.taskDetail?.id || !this.canCancelTask(this.taskDetail)) return;
            this.cancelingTask = true;
            try {
                await api('PATCH', `/tasks/${this.taskDetail.id}/order`, { status: 'canceled' });
                await this.refreshTaskDetail();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.cancelingTask = false;
            }
        },

        async loadTaskComments() {
            if (!this.taskDetail?.id) return;
            this.commentsLoading = true;
            try {
                const data = await api('GET', `/tasks/${this.taskDetail.id}/comments`);
                this.comments = Array.isArray(data) ? data : (data?.data || []);
            } finally {
                this.commentsLoading = false;
            }
        },

        flattenComments(items, depth = 0, output = []) {
            (items || []).forEach(comment => {
                const replyCount = this.replyCount(comment);
                output.push({ ...comment, _depth: depth, _replyCount: replyCount });
                if (replyCount && this.expandedComments[comment.id]) {
                    this.flattenComments(comment.replies || [], depth + 1, output);
                }
            });
            return output;
        },

        replyCount(comment) {
            return (comment.replies || []).reduce((total, reply) => total + 1 + this.replyCount(reply), 0);
        },

        toggleCommentReplies(comment) {
            this.expandedComments = {
                ...this.expandedComments,
                [comment.id]: !this.expandedComments[comment.id],
            };
        },

        startReply(comment) {
            this.replyingTo = comment;
            this.replyText = '';
        },

        cancelReply() {
            this.replyingTo = null;
            this.replyText = '';
        },

        async submitTaskComment() {
            if (!this.quickComment.trim() || !this.taskDetail?.id) return;
            try {
                const comment = await api('POST', `/tasks/${this.taskDetail.id}/comments`, { body: this.quickComment });
                this.comments.push(comment);
                this.quickComment = '';
                await this.loadTaskComments();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async submitReply(comment) {
            if (!this.replyText.trim() || !this.taskDetail?.id || !comment?.id) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/comments`, { body: this.replyText, parent_id: comment.id });
                this.expandedComments = { ...this.expandedComments, [comment.id]: true };
                this.cancelReply();
                await this.loadTaskComments();
                await this.refresh();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        attachmentExt(name) {
            return name?.split('.').pop()?.toUpperCase()?.slice(0, 4) || 'FILE';
        },

        subtaskSummary() {
            const subtasks = this.taskDetail?.subtasks || [];
            const doneSubtasks = subtasks.filter(item => item.status === 'completed').length;
            const checklists = this.taskDetail?.checklists || [];
            const doneChecklists = checklists.filter(item => item.is_done).length;
            const total = subtasks.length + checklists.length;
            const done = doneSubtasks + doneChecklists;
            return `${done}/${total}`;
        },

        statusLabel(s) {
            return {
                todo: 'Görüləcək',
                in_progress: 'İcra olunur',
                waiting_for_approve: 'Təsdiq gözləyir',
                completed: 'Tamamlandı',
                canceled: 'Ləğv olundu',
            }[s] || (s || '—');
        },

        priorityLabel(p) {
            return {
                low: 'Aşağı',
                medium: 'Orta',
                high: 'Yüksək',
                urgent: 'Təcili',
            }[p] || 'Orta';
        },

        formatDate(dt) {
            if (!dt) return '';
            const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return `${day}/${month}/${year}`;
        },

        formatDateTime(dt) {
            if (!dt) return '';
            const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            const hour = String(date.getHours()).padStart(2, '0');
            const minute = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hour}:${minute}`;
        },

        progressPercent(t) {
            if (!t) return 0;
            if (t.progress !== undefined && t.progress !== null && t.progress !== '') {
                const value = parseInt(t.progress, 10);
                return Number.isNaN(value) ? 0 : Math.max(0, Math.min(100, value));
            }
            if (t.status === 'completed') return 100;
            if (t.status === 'waiting_for_approve') return 85;
            if (typeof t.checklist_progress === 'number') return Math.max(0, Math.min(100, Math.round(t.checklist_progress)));

            const subtasks = t.subtasks || [];
            const checklists = t.checklists || [];
            const doneSubtasks = subtasks.filter(item => item.status === 'completed').length;
            const doneChecklists = checklists.filter(item => item.is_done).length;
            const total = subtasks.length + checklists.length;
            if (total > 0) return Math.round(((doneSubtasks + doneChecklists) / total) * 100);
            if (t.status === 'in_progress') return 30;
            if (t.status === 'canceled') return 0;
            return 15;
        },

        progressColor(t) {
            if (!t) return '#50b35d';
            if (t.status === 'completed' || t.status === 'waiting_for_approve') return '#0dd33f';
            if (t.status === 'in_progress') return '#54b84f';
            if (t.status === 'canceled') return '#ef4444';
            return '#a6b82a';
        },

        boardNameForRow(t) {
            return t.board?.name || window.boardNameFallback || '';
        },

        taskStatusDotColor(status, overdue = false) {
            if (overdue) return '#ff1e1e';
            return {
                todo: '#cfd2dc',
                in_progress: '#f7aa14',
                waiting_for_approve: '#955bf7',
                completed: '#0cc53f',
                canceled: '#ef4444',
            }[status] || '#f7aa14';
        },
    }
};

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
            window.addEventListener('reset-employee-picker', () => {
                this.selected = [];
                this.search = '';
                this.results = [];
                this.open = false;
            });
        },

        async searchEmployees(spaceId = null) {
            if ((this.search || '').length < 1) {
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
};
