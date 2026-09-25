function dashboardStatistics() {
    return {
        loading: false,
        spaceStats: [],
        tasks: [],
        assignedByTasks: [],
        taskModalOpen: false,
        taskLoading: false,
        taskDetail: null,
        commentsLoading: false,
        comments: [],
        quickComment: '',
        replyingTo: null,
        replyText: '',
        expandedComments: {},
        editingTaskMain: false,
        editingTaskAssignees: false,
        editingTaskDates: false,
        taskMainForm: { title: '', description: '' },
        taskDateForm: { start_date: '', due_date: '' },
        selectedTaskAssignees: [],
        taskAssigneeSearch: '',
        taskAssigneeResults: [],
        selectedStatus: '',
        openSpaces: {},
        statusSections: [
            { key:'todo', label:'Görüləcək' },
            { key:'in_progress', label:'İcra olunur' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir' },
            { key:'completed', label:'Tamamlandı' },
            { key:'canceled', label:'Ləğv olundu' },
        ],

        async init() {
            await this.loadStatistics();
        },

        async loadStatistics() {
            this.loading = true;
            try {
                const data = await api('GET', '/dashboard?scope=all');
                this.spaceStats = data.space_stats || [];
                this.tasks = data.executive_tasks || data.assigned_by_tasks || [];
                this.assignedByTasks = data.executive_tasks || data.assigned_by_tasks || [];
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Xəta', type: 'error' } }));
            } finally {
                this.loading = false;
            }
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

        selectStatus(status) {
            this.selectedStatus = this.selectedStatus === status ? '' : status;
        },

        statusLabel(status) {
            return this.statusSections.find(section => section.key === status)?.label || status || '-';
        },

        selectedStatusLabel() {
            return this.selectedStatus ? this.statusLabel(this.selectedStatus) : 'Bütün statuslar';
        },

        async openTaskModal(taskId) {
            this.taskModalOpen = true;
            this.taskLoading = true;
            this.taskDetail = null;
            this.comments = [];
            this.quickComment = '';
            this.replyingTo = null;
            this.replyText = '';
            this.expandedComments = {};
            this.editingTaskMain = false;
            this.editingTaskAssignees = false;
            this.editingTaskDates = false;

            try {
                const response = await api('GET', `/tasks/${taskId}`);
                this.taskDetail = response.data || response;
                await this.loadTaskComments();
            } catch (e) {
                this.closeTaskModal();
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Xəta', type: 'error' } }));
            } finally {
                this.taskLoading = false;
            }
        },

        closeTaskModal() {
            this.taskModalOpen = false;
            this.taskLoading = false;
            this.taskDetail = null;
            this.comments = [];
            this.quickComment = '';
            this.replyingTo = null;
            this.replyText = '';
            this.expandedComments = {};
            this.editingTaskMain = false;
            this.editingTaskAssignees = false;
            this.editingTaskDates = false;
            this.selectedTaskAssignees = [];
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
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
                await this.loadStatistics();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Tapşırıq yenilənmədi', type: 'error' } }));
            }
        },

        async saveTaskPriority(priority) {
            if (!this.taskDetail?.id) return;
            try {
                await api('PUT', `/tasks/${this.taskDetail.id}`, { priority });
                await this.refreshTaskDetail();
                await this.loadStatistics();
            } catch (e) {
                await this.refreshTaskDetail().catch(() => {});
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Prioritet dəyişmədi', type: 'error' } }));
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
                await this.loadStatistics();
            } catch (e) {
                await this.refreshTaskDetail().catch(() => {});
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Status dəyişmədi', type: 'error' } }));
            }
        },

        prepareTaskDates() {
            this.taskDateForm = {
                start_date: this.taskDetail?.start_date || '',
                due_date: this.taskDetail?.due_date || '',
            };
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
                await this.loadStatistics();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Tarix dəyişmədi', type: 'error' } }));
            }
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
                const employees = Array.isArray(data) ? data : (data?.data || []);
                const selectedIds = this.selectedTaskAssignees.map(employee => employee.id);
                this.taskAssigneeResults = employees.filter(employee => !selectedIds.includes(employee.id));
            } catch (e) {
                this.taskAssigneeResults = [];
            }
        },

        selectTaskAssignee(employee) {
            if (!this.selectedTaskAssignees.find(item => item.id === employee.id)) {
                this.selectedTaskAssignees.push(employee);
            }
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
        },

        removeTaskAssignee(id) {
            this.selectedTaskAssignees = this.selectedTaskAssignees.filter(employee => employee.id !== id);
        },

        async saveTaskAssignees() {
            if (!this.taskDetail?.id) return;
            try {
                const updated = await api('PATCH', `/tasks/${this.taskDetail.id}/assignees`, {
                    assignee_ids: this.selectedTaskAssignees.map(employee => employee.id),
                });
                this.taskDetail.assignees = updated.assignees ?? this.selectedTaskAssignees;
                this.editingTaskAssignees = false;
                await this.loadStatistics();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Təyinatçılar dəyişmədi', type: 'error' } }));
            }
        },

        async refreshTaskDetail() {
            if (!this.taskDetail?.id) return;
            const response = await api('GET', `/tasks/${this.taskDetail.id}`);
            this.taskDetail = response.data || response;
        },

        async loadTaskComments() {
            if (!this.taskDetail?.id) return;
            this.commentsLoading = true;
            try {
                const response = await api('GET', `/tasks/${this.taskDetail.id}/comments`);
                this.comments = Array.isArray(response) ? response : (response?.data || []);
            } catch (e) {
                this.comments = [];
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
                await api('POST', `/tasks/${this.taskDetail.id}/comments`, { body: this.quickComment });
                this.quickComment = '';
                await this.loadTaskComments();
                await this.refreshTaskDetail();
                await this.loadStatistics();
                this.$nextTick(() => {
                    if (this.$refs.commentsList) this.$refs.commentsList.scrollTop = this.$refs.commentsList.scrollHeight;
                });
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Şərh əlavə olunmadı', type: 'error' } }));
            }
        },

        async submitReply(comment) {
            if (!this.replyText.trim() || !this.taskDetail?.id || !comment?.id) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/comments`, { body: this.replyText, parent_id: comment.id });
                this.expandedComments = { ...this.expandedComments, [comment.id]: true };
                this.cancelReply();
                await this.loadTaskComments();
                await this.refreshTaskDetail();
                await this.loadStatistics();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Cavab əlavə olunmadı', type: 'error' } }));
            }
        },

        priorityLabel(priority) {
            return {
                low: 'Aşağı',
                medium: 'Orta',
                high: 'Yüksək',
                urgent: 'Təcili',
            }[priority] || priority || '-';
        },

        taskProgress(task) {
            if (!task) return 0;
            if (task.progress !== undefined && task.progress !== null) {
                return Math.max(0, Math.min(100, Math.round(Number(task.progress) || 0)));
            }
            if (task.status === 'completed') return 100;
            if (task.status === 'waiting_for_approve') return 90;
            if (task.status === 'in_progress') return 70;
            return 0;
        },

        spaceTasksByStatus(spaceId) {
            return this.tasks.filter(task => {
                if (Number(task.space_id) !== Number(spaceId)) return false;
                return this.selectedStatus ? task.status === this.selectedStatus : true;
            });
        },

        spaceStatusPart(spaceId, status) {
            const tasks = this.tasks.filter(task => Number(task.space_id) === Number(spaceId));
            if (!tasks.length) return 0;
            const count = tasks.filter(task => task.status === status).length;
            return this.statPart(count, tasks.length);
        },

        toggleSpace(spaceId) {
            this.openSpaces = {
                ...this.openSpaces,
                [spaceId]: !this.openSpaces[spaceId],
            };
        },

        isSpaceOpen(spaceId) {
            return !!this.openSpaces[spaceId];
        },

        formatDate(dt) {
            if (!dt) return '';
            const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return day + '/' + month + '/' + year;
        },

        statusTotal(status) {
            return this.tasks.filter(task => task.status === status).length;
        },

        overallTotal() {
            return this.tasks.length;
        },

        overallBoards() {
            return new Set(this.tasks.map(task => task.board_id).filter(Boolean)).size;
        },

        overallCompleted() {
            return this.statusTotal('completed');
        },

        overallOverdue() {
            return this.tasks.filter(task => task.is_overdue).length;
        },

        activeTotal() {
            return this.statusTotal('todo') + this.statusTotal('in_progress') + this.statusTotal('waiting_for_approve');
        },

        statPart(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
        },

        statPercent(value, total) {
            return this.statPart(value, total);
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

            return segments.length ? `background: conic-gradient(${segments.join(', ')})` : 'background: rgba(255,255,255,.12)';
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
            return [...this.spaceStats].sort((a, b) => this.spaceTaskCount(b.id) - this.spaceTaskCount(a.id));
        },

        riskySpaces() {
            return [...this.spaceStats].sort((a, b) => this.spaceOverdueCount(b.id) - this.spaceOverdueCount(a.id));
        },

        spaceCompletion(space) {
            if (!space) return 0;
            return this.statPercent(this.spaceCompletedCount(space.id), this.spaceTaskCount(space.id));
        },

        spaceTaskCount(spaceId) {
            return this.tasks.filter(task => Number(task.space_id) === Number(spaceId)).length;
        },

        spaceCompletedCount(spaceId) {
            return this.tasks.filter(task => Number(task.space_id) === Number(spaceId) && task.status === 'completed').length;
        },

        spaceOverdueCount(spaceId) {
            return this.tasks.filter(task => Number(task.space_id) === Number(spaceId) && task.is_overdue).length;
        },

        sortedByCompletion() {
            return [...this.spaceStats].sort((a, b) => {
                const diff = this.spaceCompletion(b) - this.spaceCompletion(a);
                if (diff !== 0) return diff;
                return this.spaceTaskCount(b.id) - this.spaceTaskCount(a.id);
            });
        },
    };
}
