function spaceHub(spaceId) {
    return {
        spaceId,
        canCreateBoard: false,
        canManageMembers: false,
        memberSearch: '',
        memberRole: 'employee',
        memberResults: [],
        membersLoading: false,
        members: [],
        profileModalOpen: false,
        profileLoading: false,
        profileDetail: null,
        boardsLoading: false,
        boards: [],
        showCreateBoardModal: false,
        showArchivedBoardsModal: false,
        archivedBoards: [],
        savingBoard: false,
        boardError: '',
        newBoard: { name: '', description: '', deadline: '' },

        showCreateModal: false,
        creating: false,
        createBoardId: null,
        newTask: { title:'', description:'', priority:'medium', visibility:'all_members', start_date: new Date().toISOString().split('T')[0], due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false, assigned_by_id: null },
        newTaskSubtaskDraft: { title:'', due_date:'' },
        newTaskSubtasks: [],
        newTaskSubtaskAssigneeSearch: '',
        newTaskSubtaskAssigneeResults: [],
        newTaskSubtaskAssignees: [],
        myTasksLoading: false,
        myTasks: [],
        spaceGrouped: {},
        boardFilters: { priority: '', status: '', dueSoon: false, overdue: false, dateFrom: '', dateTo: '', onlyMe: true },
        draggedTask: null,
        dragOverStatus: null,
        statusUpdating: false,
        approvingTask: false,
        cancelingTask: false,

        taskModalOpen: false,
        taskDetail: null,
        taskLoading: false,
        quickComment: '',
        replyingTo: null,
        replyText: '',
        taskComments: [],
        taskCommentsLoading: false,
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

        statusSections: [
            { key:'todo',                label:'Görüləcək',         headerClass:'bg-slate-600' },
            { key:'in_progress',         label:'İcra olunur',       headerClass:'bg-blue-600' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir',   headerClass:'bg-purple-600' },
            { key:'completed',           label:'Tamamlandı',        headerClass:'bg-emerald-600' },
            { key:'canceled',            label:'Ləğv olundu',       headerClass:'bg-rose-600' },
        ],

        async init() {
            window.addEventListener('open-task-modal', event => {
                const taskId = event.detail?.taskId;
                if (taskId) this.openTaskModal(taskId);
            });
            await this.loadSpacePermissions();
            await Promise.all([this.loadMembers(), this.loadBoards(), this.loadMyTasks(), this.loadSpaceGrouped()]);
        },

        async loadSpacePermissions() {
            try {
                const res = await api('GET', `/spaces/${this.spaceId}`);
                this.canCreateBoard = !!res?.can?.create_board;
                this.canManageMembers = !!res?.can?.manage_members;
            } catch (e) {
                this.canCreateBoard = false;
                this.canManageMembers = false;
            }
        },

        async searchSpaceMembers() {
            if (!this.memberSearch?.trim()) {
                this.memberResults = [];
                return;
            }
            try {
                const data = await api('GET', `/employees/search?q=${encodeURIComponent(this.memberSearch)}`);
                this.memberResults = Array.isArray(data) ? data : (data?.data || []);
            } catch(e) {
                this.memberResults = [];
            }
        },

        async addSpaceMember(emp) {
            if (!emp?.id) return;
            try {
                await api('POST', `/spaces/${this.spaceId}/members`, {
                    employee_id: emp.id,
                    space_role: this.memberRole || 'employee',
                    is_manager: this.memberRole === 'senior_manager',
                    can_create_boards: this.memberRole !== 'employee',
                });
                this.memberSearch = '';
                this.memberResults = [];
                await this.loadMembers();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv əlavə edildi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Üzv əlavə olunmadı', type:'error' } }));
            }
        },

        async loadMembers() {
            this.membersLoading = true;
            try {
                const res = await api('GET', `/spaces/${this.spaceId}/members`);
                this.members = res.data || [];
            } catch(e) {
                this.members = [];
            } finally {
                this.membersLoading = false;
            }
        },

        async openProfileModal(employeeId) {
            this.profileModalOpen = true;
            this.profileLoading = true;
            this.profileDetail = null;
            try {
                this.profileDetail = await api('GET', `/employees/${employeeId}/profile`);
            } catch(e) {
                this.profileModalOpen = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Profil açılmadı', type:'error' } }));
            } finally {
                this.profileLoading = false;
            }
        },

        closeProfileModal() {
            this.profileModalOpen = false;
            this.profileDetail = null;
        },

        async removeSpaceMember(employeeId) {
            if (!employeeId || !this.canManageMembers) return;
            try {
                await api('DELETE', `/spaces/${this.spaceId}/members/${employeeId}`);
                await this.loadMembers();
                this.closeProfileModal();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv space-dən çıxarıldı', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Üzv çıxarılmadı', type:'error' } }));
            }
        },

        async loadBoards() {
            this.boardsLoading = true;
            try {
                const res = await api('GET', `/spaces/${this.spaceId}/boards`);
                this.boards = res.data || [];
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            } finally {
                this.boardsLoading = false;
            }
        },

        async loadMyTasks() {
            this.myTasksLoading = true;
            try {
                const res = await api('GET', `/spaces/${this.spaceId}/tasks?created_by=${AUTH_USER.id}&unassigned_board=1`);
                this.myTasks = Array.isArray(res) ? res : (res?.data || []);
            } catch(e) {
                this.myTasks = [];
            } finally {
                this.myTasksLoading = false;
            }
        },

        async loadSpaceGrouped() {
            try {
                const params = new URLSearchParams({ grouped: true });
                if (this.boardFilters.priority) params.set('priority', this.boardFilters.priority);
                if (this.boardFilters.status) params.set('status', this.boardFilters.status);
                if (this.boardFilters.dueSoon) params.set('due_soon', 1);
                if (this.boardFilters.overdue) params.set('overdue', 1);
                if (this.boardFilters.dateFrom) params.set('due_date_from', this.boardFilters.dateFrom);
                if (this.boardFilters.dateTo) params.set('due_date_to', this.boardFilters.dateTo);
                if (this.boardFilters.onlyMe && AUTH_USER?.id) params.set('assignee_id', AUTH_USER.id);
                const data = await api('GET', `/spaces/${this.spaceId}/tasks?${params}`);
                this.spaceGrouped = data || {};
            } catch(e) {
                this.spaceGrouped = {};
            }
        },

        exportTasks() {
            const params = new URLSearchParams();
            if (this.boardFilters.priority) params.set('priority', this.boardFilters.priority);
            if (this.boardFilters.status) params.set('status', this.boardFilters.status);
            if (this.boardFilters.dueSoon) params.set('due_soon', 1);
            if (this.boardFilters.overdue) params.set('overdue', 1);
            if (this.boardFilters.dateFrom) params.set('due_date_from', this.boardFilters.dateFrom);
            if (this.boardFilters.dateTo) params.set('due_date_to', this.boardFilters.dateTo);
            if (this.boardFilters.onlyMe && AUTH_USER?.id) params.set('assignee_id', AUTH_USER.id);
            window.location.href = `/api/spaces/${this.spaceId}/tasks/export?${params.toString()}`;
        },

        openCreateBoard() {
            this.boardError = '';
            this.newBoard = { name: '', description: '', deadline: '' };
            this.showCreateBoardModal = true;
        },

        async createBoard() {
            this.boardError = '';
            this.savingBoard = true;
            try {
                await api('POST', `/spaces/${this.spaceId}/boards`, this.newBoard);
                this.showCreateBoardModal = false;
                await this.loadBoards();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Board yaradıldı!', type:'success' } }));
            } catch(e) {
                this.boardError = e.message || 'Xəta';
            } finally {
                this.savingBoard = false;
            }
        },

        async openArchivedBoards() {
            this.showArchivedBoardsModal = true;
            try {
                const res = await api('GET', `/spaces/${this.spaceId}/boards?archived=1`);
                this.archivedBoards = res.data || [];
            } catch(e) {
                this.archivedBoards = [];
            }
        },

        async unarchiveBoard(board) {
            try {
                await api('PATCH', `/boards/${board.id}/unarchive`);
                this.archivedBoards = this.archivedBoards.filter(item => item.id !== board.id);
                await this.loadBoards();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Layihə arxivdən çıxmadı', type:'error' } }));
            }
        },

        onDragTaskStart(task) {
            this.draggedTask = task;
        },

        onDragStatusTaskStart(task, event) {
            this.draggedTask = task;
            this.dragOverStatus = null;
            if (event?.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(task.id));
            }
        },

        onDragTaskEnd() {
            this.dragOverStatus = null;
        },

        canApproveTask(task) {
            if (!task) return false;
            return !!(task.can?.approve || task.creator?.id === AUTH_USER?.id);
        },

        canShowApprovePrompt(task) {
            return !!task && task.status === 'waiting_for_approve' && this.canApproveTask(task);
        },

        canCancelTask(task) {
            return !!task
                && task.status !== 'completed'
                && task.status !== 'canceled'
                && task.creator?.id === AUTH_USER?.id;
        },

        async saveTaskPriority(priority) {
            if (!this.taskDetail?.id) return;
            try {
                await api('PUT', `/tasks/${this.taskDetail.id}`, { priority });
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
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
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
            } catch(e) {
                await this.refreshTaskDetail().catch(() => {});
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Status dəyişmədi', type:'error' } }));
            }
        },

        canToggleChecklistItem(task) {
            return !!task && !!(task.can?.toggle_checklist || task.creator?.id === AUTH_USER?.id || (task.assignees || []).some(person => person.id === AUTH_USER?.id));
        },

        scrollToApproval() {
            this.$nextTick(() => this.$refs.approvalPanel?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        },

        async approveTask() {
            if (!this.taskDetail?.id || !this.canShowApprovePrompt(this.taskDetail)) return;
            this.approvingTask = true;
            try {
                await api('PATCH', `/tasks/${this.taskDetail.id}/approve`);
                this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq təsdiqləndi', type:'success' } }));
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
                this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq ləğv edildi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.cancelingTask = false;
            }
        },

        allowedNextStatuses(task) {
            if (!task) return [];
            const flow = {
                todo: ['in_progress', 'canceled'],
                in_progress: ['waiting_for_approve', 'canceled'],
                waiting_for_approve: ['completed', 'in_progress', 'canceled'],
                completed: [],
                canceled: ['todo'],
            };
            return flow[task.status] || [];
        },

        canDropOnStatus(status) {
            if (!this.draggedTask || this.statusUpdating) return false;
            if (this.draggedTask.status === status) return false;
            if (!this.allowedNextStatuses(this.draggedTask).includes(status)) return false;
            if (status === 'completed') return this.canApproveTask(this.draggedTask);
            if (status === 'canceled') return this.canCancelTask(this.draggedTask);
            return true;
        },

        async onDropToStatus(status) {
            const task = this.draggedTask;
            this.dragOverStatus = null;

            if (!task?.id) return;

            if (!this.canDropOnStatus(status)) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: status === 'completed'
                            ? 'Tapşırığı yalnız yaradan şəxs tamamlandı kimi təsdiqləyə bilər.'
                            : status === 'canceled'
                            ? 'Tapşırığı yalnız yaradan şəxs ləğv edə bilər.'
                            : 'Bu status keçidi mümkün deyil.',
                        type: 'error'
                    }
                }));
                this.draggedTask = null;
                return;
            }

            this.statusUpdating = true;
            try {
                if (status === 'completed') {
                    await api('PATCH', `/tasks/${task.id}/approve`);
                    window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq təsdiqləndi', type:'success' } }));
                } else {
                    await api('PATCH', `/tasks/${task.id}/order`, { status });
                    window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Status yeniləndi', type:'success' } }));
                }

                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.statusUpdating = false;
                this.draggedTask = null;
            }
        },

        async onDropToBoard(boardId) {
            if (!this.draggedTask?.id) return;
            try {
                await api('PATCH', `/tasks/${this.draggedTask.id}/move`, { board_id: boardId });
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq board-a əlavə olundu', type:'success' } }));
                await Promise.all([this.loadMyTasks(), this.loadBoards(), this.loadSpaceGrouped()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.draggedTask = null;
            }
        },

        openCreateTask(boardId = null) {
            this.createBoardId = boardId;
            this.newTask = { title:'', description:'', priority:'medium', visibility:'all_members', start_date: new Date().toISOString().split('T')[0], due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false, assigned_by_id: null };
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
            if (!this.newTask.title.trim()) return;
            this.creating = true;
            try {
                this.addNewTaskSubtask();
                const subtasks = [...this.newTaskSubtasks];
                let created;
                if (this.createBoardId) {
                    const response = await api('POST', `/boards/${this.createBoardId}/tasks`, this.newTask);
                    created = response?.data || response;
                } else {
                    created = await api('POST', `/spaces/${this.spaceId}/tasks`, this.newTask);
                }
                if (created?.id && subtasks.length) await this.createDraftSubtasks(created.id, subtasks);
                this.showCreateModal = false;
                this.createBoardId = null;
                this.newTaskSubtaskDraft = { title:'', due_date:'' };
                this.newTaskSubtasks = [];
                this.newTaskSubtaskAssigneeSearch = '';
                this.newTaskSubtaskAssigneeResults = [];
                this.newTaskSubtaskAssignees = [];
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq yaradıldı!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            } finally { this.creating = false; }
        },

        async openTaskModal(id) {
            this.taskModalOpen = true;
            this.taskDetail = null;
            this.taskLoading = true;
            this.quickComment = '';
            this.replyingTo = null;
            this.replyText = '';
            this.taskComments = [];
            this.expandedComments = {};
            this.editingTaskAssignees = false;
            this.editingTaskMain = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
            this.showChecklistForm = false;
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
            this.replyingTo = null;
            this.replyText = '';
            this.taskComments = [];
            this.expandedComments = {};
            this.editingTaskAssignees = false;
            this.editingTaskMain = false;
            this.editingTaskDates = false;
            this.showInlineSubtaskForm = false;
                        this.showChecklistForm = false;
            this.newChecklistItem = { title: '' };

        },

        progressPercent(board) {
            const total = Number(board?.tasks_count ?? 0);
            const done  = Number(board?.completed_tasks_count ?? 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
        },

boardStatusCount(board, status) {
    if (!board) return 0;

    const map = {
        todo: 'todo_tasks_count',
        in_progress: 'in_progress_tasks_count',
        waiting_for_approve: 'waiting_for_approve_tasks_count',
        completed: 'completed_tasks_count',
        canceled: 'canceled_tasks_count',
    };

    const key = map[status];
    const direct = Number(board?.[key] ?? board?.status_counts?.[status] ?? board?.stats?.[status] ?? 0);

    if (direct) return direct;

    const tasks = Array.isArray(board?.tasks) ? board.tasks : [];
    if (tasks.length) {
        return tasks.filter(t => t.status === status).length;
    }

    return 0;
},

boardAssignees(board) {
    const pool = [];
    const tasks = Array.isArray(board?.tasks) ? board.tasks : [];

    tasks.forEach(task => {
        const assignees = Array.isArray(task?.assignees) ? task.assignees : [];
        assignees.forEach(person => {
            if (person && !pool.find(p => p.id === person.id)) {
                pool.push(person);
            }
        });
    });

    return pool.slice(0, 5);
},

taskProgress(task) {
    if (!task) return 0;

    if (task.progress !== undefined && task.progress !== null && task.progress !== '') {
        const value = parseInt(task.progress, 10);
        return Number.isNaN(value) ? 0 : Math.max(0, Math.min(100, value));
    }

    const checklist = Array.isArray(task.checklists) ? task.checklists : [];
    const subtasks  = Array.isArray(task.subtasks) ? task.subtasks : [];

    if (task.checklist_progress !== undefined && task.checklist_progress !== null && task.checklist_progress !== '') {
        const value = typeof task.checklist_progress === 'object'
            ? parseInt(task.checklist_progress.percentage ?? 0, 10)
            : parseInt(task.checklist_progress, 10);
        return Number.isNaN(value) ? 0 : value;
    }

    const total = checklist.length + subtasks.length;

    if (total > 0) {
        const done =
            checklist.filter(i => i.is_done || i.is_completed).length +
            subtasks.filter(i => i.status === 'completed').length;

        return Math.round((done / total) * 100);
    }

    if (task.status === 'completed') return 100;
    if (task.status === 'in_progress') return 50;
    if (task.status === 'waiting_for_approve') return 85;

    return 0;
},

        canEditTask(task) {
            const authId = AUTH_USER?.id;
            return !!task && (task.can?.update || task.creator?.id === authId || task.assigned_by_id === authId || task.assigner?.id === authId);
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
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Tapşırıq yenilənmədi', type:'error' } }));
            }
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
                this.taskComments = (Array.isArray(data) ? data : (data?.data || [])).sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
                this.$nextTick(() => {
                    if (this.$refs.commentsList) this.$refs.commentsList.scrollTop = this.$refs.commentsList.scrollHeight;
                });
            } catch(e) {
                this.taskComments = [];
            } finally {
                this.taskCommentsLoading = false;
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

        openTaskAssigneeEditor() {
            this.selectedTaskAssignees = [...(this.taskDetail?.assignees || [])];
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
            this.editingTaskAssignees = true;
        },

        async searchTaskAssignees() {
            if ((this.taskAssigneeSearch || '').length < 1) { this.taskAssigneeResults = []; return; }
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
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
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
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async createInlineSubtask() {
            if (!this.taskDetail?.id || !this.newInlineSubtask.title?.trim()) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/subtasks`, this.newInlineSubtask);
                this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
                window.dispatchEvent(new CustomEvent('reset-employee-picker'));
                this.showInlineSubtaskForm = false;
                await this.refreshTaskDetail();
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped()]);
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
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped()]);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq yeniləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async completeSubtask(subtask) {
            if (!subtask?.id || !this.canEditSubtask(subtask)) return;
            try {
                await api('PATCH', `/tasks/${subtask.id}/order`, { status: 'completed' });
                await this.refreshTaskDetail();
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped(), this.loadBoards()]);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq təsdiqləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async refreshTaskDetail() {
            if (!this.taskDetail?.id) return;
            this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
        },

async createChecklistItem() {
    if (!this.taskDetail?.id || !this.newChecklistItem.title?.trim()) return;

    try {
        const item = await api('POST', `/tasks/${this.taskDetail.id}/checklists`, {
            title: this.newChecklistItem.title
        });

        if (!Array.isArray(this.taskDetail.checklists)) this.taskDetail.checklists = [];
        this.taskDetail.checklists.push(item);

        this.newChecklistItem = { title: '' };
        this.showChecklistForm = false;
    } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: e.message || 'Xəta', type: 'error' }
        }));
    }
},

async toggleChecklistItem(item) {
    if (!item?.id) return;
    if (!this.canToggleChecklistItem(this.taskDetail)) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: 'Bu bəndi yalnız taskı yaradan və ya məsul şəxs işarələyə bilər.', type: 'error' }
        }));
        return;
    }

    try {
        const updated = await api('PATCH', `/checklists/${item.id}/toggle`);

        item.is_done = !!(updated?.is_done ?? updated?.is_completed ?? !item.is_done);
        item.is_completed = item.is_done;
    } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: e.message || 'Xəta', type: 'error' }
        }));
    }
},

async deleteChecklistItem(item) {
    if (!item?.id) return;

    try {
        await api('DELETE', `/checklists/${item.id}`);
        this.taskDetail.checklists = (this.taskDetail.checklists || []).filter(x => x.id !== item.id);
    } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: e.message || 'Xəta', type: 'error' }
        }));
    }
},
async updateChecklistItem(item) {
    if (!item?.id || !item.title?.trim()) return;

    try {
        const updated = await api('PUT', `/checklists/${item.id}`, {
            title: item.title
        });

        item.title = updated?.title ?? item.title;
    } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: e.message || 'Xəta', type: 'error' }
        }));
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
                this.taskComments.push(comment);
                this.taskComments.sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
                this.quickComment = '';
                await Promise.all([this.loadTaskComments(), this.loadMyTasks(), this.loadSpaceGrouped()]);
                this.$nextTick(() => {
                    if (this.$refs.commentsList) this.$refs.commentsList.scrollTop = this.$refs.commentsList.scrollHeight;
                });
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
                await Promise.all([this.loadTaskComments(), this.loadMyTasks(), this.loadSpaceGrouped()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        attachmentExt(name) {
            return name?.split('.').pop()?.toUpperCase()?.slice(0, 4) || 'FILE';
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

        priorityLabel(p) { return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[p] || (p || ''); },
        formatDate(dt) {
            if (!dt) return '';
                const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear());
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
            window.addEventListener('reset-employee-picker', () => {
                this.selected = [];
                this.search = '';
                this.results = [];
                this.open = false;
            });
        },

        async searchEmployees() {
            if ((this.search || '').length < 1) {
                this.results = [];
                return;
            }

            try {
                let url = `/employees/search?q=${encodeURIComponent(this.search)}`;
                const data = await api('GET', url);
                const arr  = Array.isArray(data) ? data : (data?.data || []);

                if (this.single) {
                    this.results = arr;
                } else {
                    this.results = arr.filter(e => !this.selected.find(s => s.id === e.id));
                }

                this.open = true;
            } catch (e) {
                this.results = [];
            }
        },

        async loadAllEmployees() {
            try {
                const data = await api('GET', '/employees');
                const arr  = Array.isArray(data) ? data : (data?.data || []);

                if (this.single) {
                    this.results = arr;
                } else {
                    this.results = arr.filter(e => !this.selected.find(s => s.id === e.id));
                }

                this.open = true;
            } catch (e) {
                this.results = [];
            }
        },

        select(emp) {
            if (this.single) {
                this.selected = [emp];
            } else {
                if (!this.selected.find(s => s.id === emp.id)) {
                    this.selected.push(emp);
                }
            }

            this.search = '';
            this.results = [];
            this.open = false;
        },

        remove(id) {
            this.selected = this.selected.filter(e => e.id !== id);
        }
    }
}
