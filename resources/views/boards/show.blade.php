@extends('layouts.app')
@section('title', $board->name)
@section('page-title', $board->name . ' — Board')

@section('content')
<div class="h-full flex" x-data="trelloBoard({{ $space->id }}, {{ $board->id }})" x-init="init()">

    {{-- Main board --}}
    <div class="flex-1 overflow-x-auto">
        <div class="flex gap-4 p-6 min-w-max">
            <template x-for="list in lists" :key="list.id">
                <div class="w-80 shrink-0">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col max-h-[calc(100vh-160px)]">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 text-sm truncate" x-text="list.title"></p>
                                <p class="text-xs text-slate-400" x-text="`${(list.tasks||[]).length} task`"></p>
                            </div>

                            <div class="flex items-center gap-1">
                                <button @click="openCreateTask(list)"
                                        class="text-xs px-2 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100">
                                    + Task
                                </button>
                                <button @click="deleteList(list)"
                                        class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                    🗑
                                </button>
                            </div>
                        </div>

                        <div class="p-3 overflow-y-auto scrollbar-thin space-y-3 board-list-drop"
                             :id="`board-list-${list.id}`"
                             :data-list-id="list.id">
                            <template x-for="t in (list.tasks || [])" :key="t.id">
                                <div class="kanban-card bg-white rounded-xl border border-slate-200 shadow-sm p-3 cursor-grab select-none"
                                     :data-task-id="t.id">
                                    <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="t.title"></p>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex -space-x-2">
                                            <template x-for="a in (t.assignees||[]).slice(0,3)" :key="a.id">
                                                <img :src="a.avatar_url" :title="a.full_name"
                                                     class="w-6 h-6 rounded-full border-2 border-white">
                                            </template>
                                        </div>
                                        <a :href="`/tasks/${t.id}`" class="text-xs text-slate-400 hover:text-blue-600">Aç</a>
                                    </div>
                                </div>
                            </template>

                            <div x-show="(list.tasks||[]).length === 0" class="text-sm text-slate-300 text-center py-6">
                                Boş list
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Add list --}}
            <div class="w-80 shrink-0">
                <button @click="openCreateList()"
                        class="w-full h-14 bg-white/60 hover:bg-white rounded-2xl border border-dashed border-slate-300 text-slate-600 font-medium transition-colors">
                    + Yeni List
                </button>
            </div>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div class="w-80 border-l border-slate-200 bg-white shrink-0"
         x-show="sidebarOpen" x-transition
         @click.outside="sidebarOpen = false">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Activity</h3>
            <button @click="sidebarOpen=false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div class="p-5 overflow-y-auto max-h-[calc(100vh-120px)] scrollbar-thin">
            <template x-if="!canViewActivity">
                <div class="text-sm text-slate-400">
                    Aktivlik tarixçəsi yalnız Board yaradan və Space manager üçün görünür.
                </div>
            </template>

            <template x-if="canViewActivity">
                <div class="space-y-3">
                    <template x-for="log in activity" :key="log.id">
                        <div class="border border-slate-100 rounded-xl p-3">
                            <div class="flex items-center gap-2">
                                <img :src="log.employee?.avatar_url" class="w-7 h-7 rounded-full">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate" x-text="log.employee?.full_name"></p>
                                    <p class="text-xs text-slate-400" x-text="formatDateTime(log.created_at)"></p>
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 mt-2" x-text="activityText(log)"></p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Floating toggle button --}}
    <button @click="toggleSidebar()"
            class="fixed right-6 bottom-6 bg-slate-900 text-white rounded-full shadow-xl w-12 h-12 flex items-center justify-center hover:bg-slate-800 transition-colors">
        ☰
    </button>

    {{-- Create List Modal --}}
    <div x-show="showCreateListModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Yeni List</h2>
                <button @click="showCreateListModal=false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
                <input x-model="newList.title" placeholder="List adı..."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <select x-model="newList.type"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="custom">Custom</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Done</option>
                    <option value="rejected">Rejected</option>
                </select>
                <p x-show="listError" x-text="listError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showCreateListModal=false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Ləğv</button>
                <button @click="createList()"
                        :disabled="savingList || !newList.title.trim()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg disabled:opacity-50">
                    <span x-text="savingList ? 'Yaradılır...' : 'Yarat'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Create Task Modal --}}
    <div x-show="showCreateTaskModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Yeni Task</h2>
                <button @click="showCreateTaskModal=false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
                <input x-model="newTask.title" placeholder="Task başlıq..."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <textarea x-model="newTask.description" rows="3" placeholder="Təsvir..."
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
                <p x-show="taskError" x-text="taskError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showCreateTaskModal=false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Ləğv</button>
                <button @click="createTask()"
                        :disabled="savingTask || !newTask.title.trim()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg disabled:opacity-50">
                    <span x-text="savingTask ? 'Yaradılır...' : 'Yarat'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function trelloBoard(spaceId, boardId) {
    return {
        spaceId,
        boardId,
        lists: [],
        activity: [],
        sidebarOpen: false,
        canViewActivity: false,
        sortables: [],

        showCreateListModal: false,
        savingList: false,
        listError: '',
        newList: { title: '', type: 'custom' },

        showCreateTaskModal: false,
        savingTask: false,
        taskError: '',
        newTask: { title: '', description: '' },
        _targetList: null,

        async init() {
            await this.loadBoard();
            this.initDragDrop();
        },

        async loadBoard() {
            const res = await api('GET', `/boards/${this.boardId}`);
            const board = res.data;
            this.lists = board.lists || [];
            this.canViewActivity = !!board.can?.view_activity;
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            if (this.sidebarOpen) this.loadActivity();
        },

        async loadActivity() {
            if (!this.canViewActivity) return;
            const res = await api('GET', `/boards/${this.boardId}/activity`);
            this.activity = (res.data || []);
        },

        openCreateList() {
            this.listError = '';
            this.newList = { title: '', type: 'custom' };
            this.showCreateListModal = true;
        },

        async createList() {
            this.listError = '';
            this.savingList = true;
            try {
                await api('POST', `/boards/${this.boardId}/lists`, this.newList);
                this.showCreateListModal = false;
                await this.loadBoard();
                this.$nextTick(() => this.initDragDrop());
                if (this.sidebarOpen) await this.loadActivity();
            } catch (e) {
                this.listError = e.message || 'Xəta';
            } finally {
                this.savingList = false;
            }
        },

        async deleteList(list) {
            if (!confirm(`"${list.title}" list-i silinsin?`)) return;
            await api('DELETE', `/board-lists/${list.id}`);
            await this.loadBoard();
            this.$nextTick(() => this.initDragDrop());
            if (this.sidebarOpen) await this.loadActivity();
        },

        openCreateTask(list) {
            this.taskError = '';
            this.newTask = { title: '', description: '' };
            this._targetList = list;
            this.showCreateTaskModal = true;
        },

        async createTask() {
            if (!this._targetList) return;
            this.taskError = '';
            this.savingTask = true;
            try {
                await api('POST', `/board-lists/${this._targetList.id}/tasks`, this.newTask);
                this.showCreateTaskModal = false;
                await this.loadBoard();
                this.$nextTick(() => this.initDragDrop());
                if (this.sidebarOpen) await this.loadActivity();
            } catch (e) {
                this.taskError = e.message || 'Xəta';
            } finally {
                this.savingTask = false;
            }
        },

        initDragDrop() {
            this.$nextTick(() => {
                this.sortables.forEach(s => { try { s.destroy(); } catch(e) {} });
                this.sortables = [];

                (this.lists || []).forEach(list => {
                    const el = document.getElementById(`board-list-${list.id}`);
                    if (!el) return;
                    if (el._sortable) { try { el._sortable.destroy(); } catch(e) {} }

                    const s = Sortable.create(el, {
                        group: 'trello',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onEnd: async (evt) => {
                            const taskId = parseInt(evt.item.dataset.taskId);
                            const newListId = parseInt(evt.to.dataset.listId);
                            const newPos = evt.newIndex ?? null;
                            try {
                                await api('PATCH', `/tasks/${taskId}/move`, {
                                    board_list_id: newListId,
                                    board_position: newPos,
                                });
                            } catch(e) {
                                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
                            } finally {
                                await this.loadBoard();
                                this.$nextTick(() => this.initDragDrop());
                                if (this.sidebarOpen) await this.loadActivity();
                            }
                        }
                    });

                    el._sortable = s;
                    this.sortables.push(s);
                });
            });
        },

        formatDateTime(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleString('az-AZ', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
        },

        activityText(log) {
            const m = log.meta || {};
            if (log.entity_type === 'board_list') {
                if (log.action === 'create') return `List yaradıldı: ${m.title ?? ''}`;
                if (log.action === 'update') return `List yeniləndi: ${m.after?.title ?? ''}`;
                if (log.action === 'delete') return `List silindi: ${m.title ?? ''}`;
            }
            if (log.entity_type === 'task') {
                if (log.action === 'create') return `Task yaradıldı: ${m.title ?? ''}`;
                if (log.action === 'move') return `Task köçürüldü`;
                if (log.action === 'update') return `Task yeniləndi`;
                if (log.action === 'delete') return `Task silindi`;
            }
            if (log.entity_type === 'board' && log.action === 'create') {
                return `Board yaradıldı: ${m.name ?? ''}`;
            }
            return `${log.action} ${log.entity_type}`;
        },
    }
}
</script>
@endpush

