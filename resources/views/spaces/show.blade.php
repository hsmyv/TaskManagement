@extends('layouts.app')
@section('title', $space->name)
@section('page-title', $space->name)

@section('content')
<div x-data="kanban({{ $space->id }})" x-init="init()" class="flex flex-col h-full">

    {{-- Toolbar --}}
    <div class="bg-white border-b border-slate-200 px-6 py-3 flex flex-wrap items-center gap-3 shrink-0">
        <select x-model="filters.status" @change="loadTasks()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün statuslar</option>
            <option value="todo">Görüləcək</option>
            <option value="in_progress">İcra olunur</option>
            <option value="waiting_for_approve">Təsdiq gözləyir</option>
            <option value="completed">Tamamlandı</option>
            <option value="canceled">Ləğv olundu</option>
        </select>

        <select x-model="filters.priority" @change="loadTasks()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün prioritetlər</option>
            <option value="urgent">Təcili</option>
            <option value="high">Yüksək</option>
            <option value="medium">Orta</option>
            <option value="low">Aşağı</option>
        </select>

        <label class="flex items-center gap-1.5 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" x-model="filters.due_soon" @change="loadTasks()" class="rounded">
            Son 7 gün
        </label>
        <label class="flex items-center gap-1.5 text-sm text-red-600 cursor-pointer">
            <input type="checkbox" x-model="filters.overdue" @change="loadTasks()" class="rounded">
            Gecikmiş
        </label>

        {{-- Auto-refresh göstəricisi --}}
        <div class="flex items-center gap-1.5 text-xs text-slate-400 ml-1">
            <span class="w-1.5 h-1.5 rounded-full bg-green-400 pulse-dot"></span>
            <span x-text="'Yenilənmə: ' + countdown + 's'"></span>
        </div>

        <div class="ml-auto">
            <button @click="openCreateTask()"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tapşırıq əlavə et
            </button>
        </div>
    </div>

    {{-- Kanban --}}
    <div class="flex-1 overflow-x-auto">
        <div class="flex gap-4 p-6 h-full min-w-max">
            <template x-for="col in columns" :key="col.status">
                <div class="w-80 shrink-0 flex flex-col h-full">
                    <div class="rounded-t-xl px-4 py-3 flex items-center justify-between mb-2"
                         :class="`status-${col.status}`">
                        <div class="flex items-center gap-2">
                            <span x-text="col.icon"></span>
                            <h3 class="font-semibold text-slate-700 text-sm" x-text="col.label"></h3>
                            <span class="bg-white/70 text-slate-600 text-xs font-bold px-2 py-0.5 rounded-full"
                                  x-text="(groupedTasks[col.status] || []).length"></span>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto scrollbar-thin space-y-3 pb-4 min-h-[200px] kanban-column rounded-b-xl px-1"
                         :id="`col-${col.status}`"
                         :data-status="col.status">

                        <template x-for="task in (groupedTasks[col.status] || [])" :key="task.id">
                            <div class="kanban-card bg-white rounded-xl border border-slate-200 shadow-sm p-4 cursor-pointer select-none"
                                 :data-task-id="task.id"
                                 @click="openTask(task.id)">

                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                          :class="{
                                            'bg-slate-100 text-slate-500': task.priority==='low',
                                            'bg-blue-100 text-blue-700': task.priority==='medium',
                                            'bg-orange-100 text-orange-700': task.priority==='high',
                                            'bg-red-100 text-red-700': task.priority==='urgent'
                                          }"
                                          x-text="priorityLabel(task.priority)"></span>
                                    <span x-show="task.require_approval"
                                          class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full shrink-0">✓</span>
                                </div>

                                <h4 class="text-sm font-semibold text-slate-800 mb-2 leading-snug" x-text="task.title"></h4>

                                <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                                    <span x-show="task.due_date"
                                          :class="task.is_overdue ? 'text-red-500 font-medium' : ''"
                                          x-text="task.due_date ? '📅 ' + formatDate(task.due_date) : ''"></span>
                                    <span x-show="task.subtasks_count > 0" x-text="`⊂ ${task.subtasks_count}`"></span>
                                    <span x-show="task.comments_count > 0"  x-text="`💬 ${task.comments_count}`"></span>
                                    <span x-show="task.attachments_count > 0" x-text="`📎 ${task.attachments_count}`"></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex -space-x-2">
                                        <template x-for="a in (task.assignees||[]).slice(0,3)" :key="a.id">
                                            <img :src="a.avatar_url" :title="a.full_name"
                                                 class="w-6 h-6 rounded-full border-2 border-white">
                                        </template>
                                        <span x-show="(task.assignees||[]).length > 3"
                                              class="w-6 h-6 rounded-full bg-slate-200 border-2 border-white text-xs text-slate-500 flex items-center justify-center"
                                              x-text="`+${task.assignees.length - 3}`"></span>
                                    </div>
                                    <button x-show="task.status === 'waiting_for_approve' && task.can?.approve"
                                            @click.stop="approveTask(task)"
                                            class="text-xs bg-green-500 hover:bg-green-400 text-white px-2 py-1 rounded-lg font-medium transition-colors">
                                        Təsdiqlə ✓
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="(groupedTasks[col.status]||[]).length === 0"
                             class="flex flex-col items-center justify-center py-10 text-slate-300">
                            <span class="text-4xl mb-2" x-text="col.icon"></span>
                            <p class="text-sm">Tapşırıq yoxdur</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreateModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale
             class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-lg text-slate-800">Yeni Tapşırıq</h2>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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

                {{-- Assignees --}}
                <div x-data="employeePicker()" x-init="init()">
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
                    {{-- selected id-ləri parent x-data-ya ötür --}}
                    <span x-effect="newTask.assignee_ids = selected.map(e => e.id)"></span>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" x-model="newTask.require_approval" class="rounded">
                        Təsdiq tələb olunur
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" x-model="newTask.deadline_locked" class="rounded">
                        Deadline kilidli
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal=false"
                            class="px-5 py-2.5 text-sm text-slate-600 hover:bg-slate-100 rounded-xl">Ləğv et</button>
                    <button type="submit" :disabled="creating"
                            class="px-5 py-2.5 text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white rounded-xl disabled:opacity-60">
                        <span x-show="!creating">Yarat</span>
                        <span x-show="creating">Yaradılır...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function kanban(spaceId) {
    return {
        spaceId,
        groupedTasks: {},
        filters: { status:'', priority:'', due_soon:false, overdue:false },
        columns: [
            { status:'todo',                label:'Görüləcək',       icon:'📋' },
            { status:'in_progress',         label:'İcra olunur',     icon:'🔄' },
            { status:'waiting_for_approve', label:'Təsdiq gözləyir', icon:'⏳' },
            { status:'completed',           label:'Tamamlandı',      icon:'✅' },
            { status:'canceled',            label:'Ləğv olundu',     icon:'❌' },
        ],
        showCreateModal: false,
        creating: false,
        newTask: { title:'', description:'', priority:'medium', visibility:'all_members', start_date:'', due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false },
        sortables: [],
        countdown: 30,
        _pollTimer: null,
        _countTimer: null,

        async init() {
            await this.loadTasks();
            this.initDragDrop();
            this.startPolling();
        },

        // ── Polling: hər 30 saniyə board-u yenilə ──────────────────────
        startPolling() {
            this._pollTimer = setInterval(async () => {
                await this.loadTasks();
                this.$nextTick(() => this.initDragDrop());
                this.countdown = 30;
            }, 30_000);

            // Geri sayım
            this._countTimer = setInterval(() => {
                this.countdown = Math.max(0, this.countdown - 1);
            }, 1_000);
        },

        async loadTasks() {
            try {
                const params = new URLSearchParams({ grouped: true });
                if (this.filters.status)   params.set('status',   this.filters.status);
                if (this.filters.priority) params.set('priority', this.filters.priority);
                if (this.filters.due_soon) params.set('due_soon', 1);
                if (this.filters.overdue)  params.set('overdue',  1);

                const data = await api('GET', `/spaces/${this.spaceId}/tasks?${params}`);
                this.columns.forEach(c => {
                    this.groupedTasks[c.status] = data[c.status] || [];
                });
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        initDragDrop() {
            this.sortables.forEach(s => s.destroy());
            this.sortables = [];

            this.columns.forEach(col => {
                const el = document.getElementById(`col-${col.status}`);
                if (!el) return;

                const s = Sortable.create(el, {
                    group: 'kanban',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    handle: '.kanban-card',
                    onEnd: (evt) => {
                        const taskId    = parseInt(evt.item.dataset.taskId);
                        const newStatus = evt.to.dataset.status;
                        const oldStatus = evt.from.dataset.status;
                        if (newStatus !== oldStatus) {
                            this.moveTask(taskId, newStatus);
                        }
                    }
                });
                this.sortables.push(s);
            });
        },

        async moveTask(taskId, newStatus) {
            try {
                await api('PATCH', `/tasks/${taskId}/order`, { status: newStatus });
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Status dəyişdirildi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
                await this.loadTasks();
                this.$nextTick(() => this.initDragDrop());
            }
        },

        openTask(id) { window.location.href = `/tasks/${id}`; },

        openCreateTask() {
            this.newTask = { title:'', description:'', priority:'medium', visibility:'all_members', start_date:'', due_date:'', assignee_ids:[], require_approval:false, deadline_locked:false };
            this.showCreateModal = true;
        },

        async createTask() {
            if (!this.newTask.title.trim()) return;
            this.creating = true;
            try {
                await api('POST', `/spaces/${this.spaceId}/tasks`, this.newTask);
                this.showCreateModal = false;
                await this.loadTasks();
                this.$nextTick(() => this.initDragDrop());
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq yaradıldı!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            } finally { this.creating = false; }
        },

        async approveTask(task) {
            try {
                await api('PATCH', `/tasks/${task.id}/approve`);
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq təsdiqləndi!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message, type:'error' } }));
            }
        },

        priorityLabel(p) { return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[p] || p; },
        formatDate(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleDateString('az-AZ', { day:'numeric', month:'short' });
        }
    }
}

// Employee axtarış komponenti
function employeePicker() {
    return {
        search: '', results: [], selected: [], open: false,
        init() {},
        async searchEmployees() {
            if (this.search.length < 2) { this.results = []; return; }
            try {
                const data   = await api('GET', `/employees/search?q=${encodeURIComponent(this.search)}`);
                this.results = data.filter(e => !this.selected.find(s => s.id === e.id));
            } catch(e) {}
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
