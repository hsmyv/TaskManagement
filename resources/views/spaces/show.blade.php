@extends('layouts.app')
@section('title', $space->name)
@section('page-title', $space->name)

@section('content')
<div x-data="spaceHub({{ $space->id }})" x-init="init()" class="p-6 space-y-6">
    {{-- Top area: tasks (left) + boards (right) --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Left: My created tasks --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-slate-900 rounded-2xl p-4 text-white shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Tapşırıqlar</h2>
                    <button @click="openCreateTask()"
                            class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                            title="Yeni tapşırıq">
                        +
                    </button>
                </div>

                <div class="space-y-2 max-h-[520px] overflow-y-auto scrollbar-thin pr-1">
                    <template x-if="myTasksLoading">
                        <div class="text-sm text-white/60">Yüklənir...</div>
                    </template>

                    <template x-if="!myTasksLoading && myTasks.length === 0">
                        <div class="text-sm text-white/60 py-6 text-center">Tapşırıq yoxdur</div>
                    </template>

                    <template x-for="t in myTasks" :key="t.id">
                        <div class="bg-white/10 hover:bg-white/15 rounded-xl p-3 cursor-grab select-none"
                             draggable="true"
                             @dragstart="onDragTaskStart(t)"
                             @click="openTaskModal(t.id)">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold truncate" x-text="t.title"></p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-white/80"
                                      x-text="statusLabel(t.status)"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-white/60">
                                <span x-text="t.due_date ? ('⏰ ' + formatDate(t.due_date)) : ''"></span>
                                <span x-show="t.board_id" class="text-white/70" x-text="'📌 Board'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-3 text-xs text-white/50">
                    Tapşırığı sağdakı board-un üzərinə sürükləyib buraxın.
                </div>
            </div>
        </div>

        {{-- Right: Boards grid --}}
        <div class="col-span-12 lg:col-span-9">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <select x-model="boardFilters.days" @change="loadBoards()"
                            class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 bg-white">
                        <option value="7">Son 7 gün</option>
                        <option value="30">Son 30 gün</option>
                        <option value="365">Son 1 il</option>
                    </select>
                    <label class="text-sm text-slate-600 flex items-center gap-2">
                        <input type="checkbox" x-model="boardFilters.overdue" @change="loadSpaceGrouped()" class="rounded">
                        Gecikmiş
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <button x-show="canCreateBoard" @click="openCreateBoard()"
                            class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        + Board əlavə edin
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-if="boardsLoading">
                    <div class="text-sm text-slate-400">Boardlar yüklənir...</div>
                </template>

                <template x-for="b in boards" :key="b.id">
                    <a :href="`/spaces/${spaceId}/boards/${b.id}`"
                       class="block rounded-2xl p-4 shadow-sm border border-slate-200 bg-gradient-to-br from-slate-800 to-slate-700 text-white relative overflow-hidden"
                       @dragover.prevent
                       @drop="onDropToBoard(b.id)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold truncate" x-text="b.name"></p>
                                <p class="text-xs text-white/70 mt-0.5"
                                   x-text="`${b.tasks_count ?? 0} tapşırıq`"></p>
                            </div>
                            <span class="text-[10px] bg-white/10 px-2 py-1 rounded-full">Board</span>
                        </div>

                        <div class="mt-4">
                            <div class="h-2 bg-white/15 rounded-full overflow-hidden">
                                <div class="h-2 bg-emerald-400 rounded-full"
                                     :style="`width:${progressPercent(b)}%`"></div>
                            </div>
                            <div class="mt-2 text-xs text-white/70 flex justify-between">
                                <span x-text="'İrəliləyiş'"></span>
                                <span x-text="`${progressPercent(b)}%`"></span>
                            </div>
                        </div>
                    </a>
                </template>

                <template x-if="!boardsLoading && boards.length === 0">
                    <div class="text-sm text-slate-400">Bu space-də hələ board yoxdur.</div>
                </template>
            </div>
        </div>
    </div>

    {{-- Bottom: Space-wide grouped lists by status --}}
    <div class="space-y-4">
        <template x-for="s in statusSections" :key="s.key">
            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 font-semibold text-white"
                     :class="s.headerClass">
                    <div class="flex items-center justify-between">
                        <span x-text="s.label"></span>
                        <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full"
                              x-text="(spaceGrouped[s.key] || []).length"></span>
                    </div>
                </div>
                <div class="bg-white">
                    <template x-if="(spaceGrouped[s.key] || []).length === 0">
                        <div class="px-5 py-6 text-sm text-slate-400">Tapşırıq yoxdur</div>
                    </template>

                    <template x-if="(spaceGrouped[s.key] || []).length > 0">
                        <div class="divide-y divide-slate-100">
                            <template x-for="t in (spaceGrouped[s.key] || [])" :key="t.id">
                                <div class="px-5 py-3 hover:bg-slate-50 cursor-pointer"
                                     @click="openTaskModal(t.id)">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 truncate" x-text="t.title"></p>
                                            <p class="text-xs text-slate-400 mt-0.5"
                                               x-text="t.board_id ? 'Board-da' : 'Board-suz'"></p>
                                        </div>
                                        <div class="text-xs text-slate-500 shrink-0">
                                            <span x-text="t.due_date ? formatDate(t.due_date) : ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Create Board Modal --}}
    <div x-show="showCreateBoardModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Yeni Board</h2>
                <button @click="showCreateBoardModal=false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
                <input x-model="newBoard.name" placeholder="Board adı..."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <textarea x-model="newBoard.description" rows="2" placeholder="Təsvir (opsional)..."
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
                <p x-show="boardError" x-text="boardError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showCreateBoardModal=false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Ləğv</button>
                <button @click="createBoard()"
                        :disabled="savingBoard || !newBoard.name.trim()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg disabled:opacity-50">
                    <span x-text="savingBoard ? 'Yaradılır...' : 'Yarat'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Create Task Modal (reuse previous form) --}}
    <div x-show="showCreateModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale
             class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-lg text-slate-800">Yeni Tapşırıq</h2>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    ✕
                </button>
            </div>
            <form @submit.prevent="createTask()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Başlıq *</label>
                    <input type="text" x-model="newTask.title" required placeholder="Tapşırığın adı..."
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Təsvir</label>
                    <textarea x-model="newTask.description" rows="3" placeholder="Ətraflı təsvir..."
                              class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Prioritet</label>
                        <select x-model="newTask.priority"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="low">🟢 Aşağı</option>
                            <option value="medium">🔵 Orta</option>
                            <option value="high">🟠 Yüksək</option>
                            <option value="urgent">🔴 Təcili</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Görünürlük</label>
                        <select x-model="newTask.visibility"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="all_members">Bütün üzvlər</option>
                            <option value="managers_only">Yalnız menecerlər</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Başlama tarixi</label>
                        <input type="date" x-model="newTask.start_date"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Son icra tarixi</label>
                        <input type="date" x-model="newTask.due_date"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <div x-data="employeePicker(spaceId)" x-init="init()">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Məsul şəxslər</label>
                    <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true"
                           placeholder="Ad ilə axtarın..."
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <div class="flex flex-wrap gap-2 mt-2">
                        <template x-for="emp in selected" :key="emp.id">
                            <span class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs px-2.5 py-1.5 rounded-full">
                                <img :src="emp.avatar_url" class="w-4 h-4 rounded-full">
                                <span x-text="emp.full_name"></span>
                                <button type="button" @click="remove(emp.id)" class="hover:text-red-500">✕</button>
                            </span>
                        </template>
                    </div>
                    <div x-show="open && results.length > 0" @click.outside="open=false"
                         class="relative z-10 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="emp in results" :key="emp.id">
                            <button type="button" @click="select(emp)"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-left text-sm">
                                <img :src="emp.avatar_url" class="w-7 h-7 rounded-full">
                                <div>
                                    <p class="font-medium text-slate-800" x-text="emp.full_name"></p>
                                    <p class="text-xs text-slate-400" x-text="emp.position"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                    <span x-effect="newTask.assignee_ids = selected.map(e => e.id)"></span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal=false"
                            class="px-5 py-2.5 text-sm text-slate-600 hover:bg-slate-100 rounded-xl">Ləğv et</button>
                    <button type="submit" :disabled="creating"
                            class="px-5 py-2.5 text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white rounded-xl disabled:opacity-60">
                        <span x-text="creating ? 'Yaradılır...' : 'Yarat'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Task modal (detail) --}}
    <div x-show="taskModalOpen" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale
             class="bg-slate-900 text-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <div class="min-w-0">
                    <h2 class="font-semibold truncate" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                    <p class="text-xs text-white/60" x-text="taskDetail?.space?.name || ''"></p>
                </div>
                <button @click="closeTaskModal()" class="text-white/60 hover:text-white">✕</button>
            </div>
            <div class="grid grid-cols-12 gap-0">
                <div class="col-span-12 lg:col-span-8 p-6 space-y-4">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2 py-1 rounded-full bg-white/10" x-text="statusLabel(taskDetail?.status)"></span>
                        <span class="px-2 py-1 rounded-full bg-white/10" x-text="priorityLabel(taskDetail?.priority)"></span>
                        <span class="px-2 py-1 rounded-full bg-white/10" x-text="taskDetail?.due_date ? ('⏰ ' + formatDate(taskDetail?.due_date)) : ''"></span>
                    </div>
                    <div class="text-sm text-white/80 whitespace-pre-wrap" x-text="taskDetail?.description || '—'"></div>
                </div>
                <div class="col-span-12 lg:col-span-4 bg-white/5 p-6">
                    <p class="text-xs text-white/60 mb-2">Şərhlər</p>
                    <div class="h-56 rounded-xl bg-white/5"></div>
                    <div class="mt-3">
                        <input x-model="quickComment" placeholder="Şərh yazın"
                               class="w-full rounded-xl bg-white/10 border border-white/10 px-4 py-3 text-sm placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function spaceHub(spaceId) {
    return {
        spaceId,
        canCreateBoard: false,
        boardsLoading: false,
        boards: [],
        showCreateBoardModal: false,
        savingBoard: false,
        boardError: '',
        newBoard: { name: '', description: '' },

        showCreateModal: false,
        creating: false,
        newTask: { title:'', description:'', priority:'medium', visibility:'all_members', start_date: new Date().toISOString().split('T')[0], due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false, assigned_by_id: null },
        myTasksLoading: false,
        myTasks: [],
        spaceGrouped: {},
        boardFilters: { days: '7', overdue: false },
        draggedTask: null,

        taskModalOpen: false,
        taskDetail: null,
        taskLoading: false,
        quickComment: '',
        statusSections: [
            { key:'in_progress',         label:'İcra olunur',       headerClass:'bg-blue-600' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir',   headerClass:'bg-purple-600' },
            { key:'completed',           label:'Tamamlandı',        headerClass:'bg-emerald-600' },
            { key:'todo',                label:'Görüləcək',         headerClass:'bg-slate-600' },
            { key:'canceled',            label:'Ləğv olundu',        headerClass:'bg-rose-600' },
        ],

        async init() {
            await this.loadSpacePermissions();
            await Promise.all([this.loadBoards(), this.loadMyTasks(), this.loadSpaceGrouped()]);
        },

        async loadSpacePermissions() {
            try {
                const res = await api('GET', `/spaces/${this.spaceId}`);
                this.canCreateBoard = !!res?.can?.create_board;
            } catch (e) {
                this.canCreateBoard = false;
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
                const res = await api('GET', `/spaces/${this.spaceId}/tasks?created_by=${AUTH_USER.id}`);
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
                if (this.boardFilters.overdue) params.set('overdue', 1);
                const data = await api('GET', `/spaces/${this.spaceId}/tasks?${params}`);
                this.spaceGrouped = data || {};
            } catch(e) {
                this.spaceGrouped = {};
            }
        },

        openCreateBoard() {
            this.boardError = '';
            this.newBoard = { name: '', description: '' };
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

        onDragTaskStart(task) {
            this.draggedTask = task;
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

        openCreateTask() {
            this.newTask = { title:'', description:'', priority:'medium', visibility:'all_members', start_date: new Date().toISOString().split('T')[0], due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false };
            this.showCreateModal = true;
        },

        async createTask() {
            if (!this.newTask.title.trim()) return;
            this.creating = true;
            try {
                await api('POST', `/spaces/${this.spaceId}/tasks`, this.newTask);
                this.showCreateModal = false;
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
            try {
                this.taskDetail = await api('GET', `/tasks/${id}`);
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
        },

        progressPercent(board) {
            const total = Number(board?.tasks_count ?? 0);
            const done  = Number(board?.completed_tasks_count ?? 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
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
            return new Date(dt).toLocaleDateString('az-AZ', { day:'numeric', month:'short' });
        },
    }
}

// Employee axtarış komponenti (space filter dəstəyi ilə)
function employeePicker(spaceId = null) {
    return {
        search: '', results: [], selected: [], open: false, spaceId,
        init() {},
        async searchEmployees() {
            if ((this.search || '').length < 2) { this.results = []; return; }
            try {
                let url = `/employees/search?q=${encodeURIComponent(this.search)}`;
                if (this.spaceId) url += `&space_id=${this.spaceId}`;
                const data = await api('GET', url);
                const arr  = Array.isArray(data) ? data : (data?.data || []);
                this.results = arr.filter(e => !this.selected.find(s => s.id === e.id));
            } catch(e) { this.results = []; }
        },
        select(emp) {
            if (!this.selected.find(s => s.id === emp.id)) this.selected.push(emp);
            this.search = ''; this.results = []; this.open = false;
        },
        remove(id) { this.selected = this.selected.filter(e => e.id !== id); }
    }
}
</script>
@endpush
