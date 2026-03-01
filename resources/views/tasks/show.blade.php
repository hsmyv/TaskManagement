@extends('layouts.app')
@section('title', 'Tapşırıq')
@section('page-title', 'Tapşırıq Detalları')

@section('content')
<div class="p-6 max-w-7xl mx-auto" x-data="taskDetail({{ $task->id }})" x-init="init()">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Sol: Əsas məlumat --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Header --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-1">
                        <div x-show="!editingTitle">
                            <h1 class="text-2xl font-bold text-slate-900 mb-1" x-text="task.title"></h1>
                            <p class="text-sm text-slate-400">
                                <span x-text="task.space?.name"></span> ·
                                <span x-text="'Yaradan: ' + (task.creator?.full_name || '')"></span>
                                <template x-if="task.assigner && task.assigner.id !== task.creator?.id">
                                    <span x-text="' · Təyin edən: ' + task.assigner.full_name"></span>
                                </template>
                            </p>
                        </div>
                        <div x-show="editingTitle" class="flex gap-2">
                            <input type="text" x-model="editTitle"
                                   class="flex-1 text-xl font-bold border-b-2 border-blue-500 outline-none px-1"
                                   @keydown.enter="saveTitle()" @keydown.escape="editingTitle=false">
                            <button @click="saveTitle()" class="text-blue-600 text-sm font-medium">Saxla</button>
                            <button @click="editingTitle=false" class="text-slate-400 text-sm">Ləğv</button>
                        </div>
                    </div>
                    <div x-show="task.can?.update">
                        <button @click="editingTitle=!editingTitle; editTitle=task.title"
                                class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Status + prioritet --}}
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <div class="relative" x-data="{ openSt: false }">
                        <button @click="openSt=!openSt"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
                                :class="{
                                    'bg-slate-100 text-slate-600 border-slate-200':   task.status==='todo',
                                    'bg-blue-100 text-blue-700 border-blue-200':      task.status==='in_progress',
                                    'bg-yellow-100 text-yellow-700 border-yellow-200':task.status==='waiting_for_approve',
                                    'bg-green-100 text-green-700 border-green-200':   task.status==='completed',
                                    'bg-red-100 text-red-700 border-red-200':         task.status==='canceled',
                                }">
                            <span x-text="task.status_label"></span>
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openSt" @click.outside="openSt=false"
                             class="absolute top-10 left-0 bg-white border border-slate-200 rounded-xl shadow-lg z-20 py-1 min-w-[200px]">
                            <template x-for="s in statuses" :key="s.value">
                                <button @click="changeStatus(s.value); openSt=false"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50 text-left">
                                    <span x-text="s.icon"></span><span x-text="s.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <span class="px-3 py-1.5 rounded-full text-sm font-medium"
                          :class="{
                            'bg-slate-100 text-slate-600': task.priority==='low',
                            'bg-blue-100 text-blue-700':   task.priority==='medium',
                            'bg-orange-100 text-orange-700':task.priority==='high',
                            'bg-red-100 text-red-700':     task.priority==='urgent',
                          }" x-text="priorityLabel(task.priority)"></span>

                    <span x-show="task.require_approval"
                          class="px-3 py-1.5 rounded-full text-sm bg-amber-50 text-amber-700 border border-amber-200">
                        ✓ Təsdiq tələb olunur
                    </span>
                    <span x-show="task.deadline_locked"
                          class="px-3 py-1.5 rounded-full text-sm bg-slate-100 text-slate-600">
                        🔒 Deadline kilidli
                    </span>
                </div>

                <div x-show="task.status === 'waiting_for_approve' && task.can?.approve" class="mb-4">
                    <button @click="approveTask()"
                            class="bg-green-600 hover:bg-green-500 text-white font-medium px-6 py-2.5 rounded-xl transition-colors">
                        ✓ Tapşırığı Təsdiqlə
                    </button>
                </div>

                {{-- Description --}}
                <div x-show="task.description && !editingDesc" class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap"
                     x-text="task.description"></div>
                <p x-show="!task.description && !editingDesc" class="text-slate-400 text-sm italic">Təsvir əlavə edilməyib...</p>
                <textarea x-show="editingDesc" x-model="editDesc" rows="5"
                          class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                          placeholder="Tapşırığın ətraflı təsviri..."></textarea>
                <div class="flex gap-2 mt-2" x-show="editingDesc">
                    <button @click="saveDesc()" class="text-sm bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-500">Saxla</button>
                    <button @click="editingDesc=false" class="text-sm text-slate-500 px-4 py-1.5 rounded-lg hover:bg-slate-100">Ləğv</button>
                </div>
                <button x-show="!editingDesc && task.can?.update"
                        @click="editingDesc=true; editDesc=task.description||''"
                        class="text-xs text-blue-500 hover:underline mt-1 block">
                    + Təsvir əlavə et / redaktə et
                </button>
            </div>

            {{-- Checklist --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-slate-800">
                        Yoxlama Siyahısı
                        <span class="text-slate-400 font-normal text-sm ml-1"
                              x-text="task.checklists ? `(${task.checklists.filter(c=>c.is_done).length}/${task.checklists.length})` : ''"></span>
                    </h2>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mb-4" x-show="(task.checklists||[]).length > 0">
                    <div class="bg-blue-500 h-1.5 rounded-full transition-all" :style="`width:${checklistProgress}%`"></div>
                </div>
                <div class="space-y-2">
                    <template x-for="item in (task.checklists||[])" :key="item.id">
                        <div class="flex items-center gap-3 group py-1">
                            <input type="checkbox" :checked="item.is_done" @change="toggleChecklist(item)"
                                   class="w-4 h-4 rounded cursor-pointer accent-blue-600">
                            <span class="flex-1 text-sm"
                                  :class="item.is_done ? 'line-through text-slate-400' : 'text-slate-700'"
                                  x-text="item.title"></span>
                            <button @click="deleteChecklist(item.id)"
                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity text-xs">✕</button>
                        </div>
                    </template>
                </div>
                <div class="flex gap-2 mt-3">
                    <input type="text" x-model="newChecklistItem" placeholder="Yeni element..."
                           @keydown.enter="addChecklist()"
                           class="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="addChecklist()" :disabled="!newChecklistItem.trim()"
                            class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 disabled:opacity-40 transition-colors">
                        Əlavə et
                    </button>
                </div>
            </div>

            {{-- Subtasks --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-800">Alt Tapşırıqlar
                        <span class="text-slate-400 text-sm font-normal" x-text="`(${(task.subtasks||[]).length})`"></span>
                    </h2>
                    <button @click="showSubtaskForm=!showSubtaskForm"
                            class="text-sm text-blue-600 hover:text-blue-500 font-medium">+ Alt tapşırıq</button>
                </div>
                <div x-show="showSubtaskForm" class="mb-4 bg-slate-50 rounded-xl p-4 space-y-2">
                    <input type="text" x-model="newSubtask.title" placeholder="Alt tapşırığın adı..."
                           class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="flex gap-2">
                        <input type="date" x-model="newSubtask.due_date"
                               class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button @click="createSubtask()"
                                class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 transition-colors">Yarat</button>
                        <button @click="showSubtaskForm=false"
                                class="text-sm text-slate-500 px-4 py-2 rounded-lg hover:bg-slate-100">Ləğv</button>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="sub in (task.subtasks||[])" :key="sub.id">
                        <a :href="`/tasks/${sub.id}`"
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 border border-slate-100 group transition-colors">
                            <span class="w-2 h-2 rounded-full shrink-0"
                                  :class="{
                                    'bg-slate-400':  sub.status==='todo',
                                    'bg-blue-500':   sub.status==='in_progress',
                                    'bg-yellow-500': sub.status==='waiting_for_approve',
                                    'bg-green-500':  sub.status==='completed',
                                    'bg-red-500':    sub.status==='canceled',
                                  }"></span>
                            <span class="flex-1 text-sm text-slate-700" x-text="sub.title"
                                  :class="sub.status==='completed' ? 'line-through text-slate-400' : ''"></span>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </template>
                    <p x-show="(task.subtasks||[]).length===0" class="text-sm text-slate-400 italic py-2">Alt tapşırıq yoxdur</p>
                </div>
            </div>

            {{-- Comments --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-800">💬 Şərhlər</h2>
                    <button @click="loadComments()" class="text-xs text-slate-400 hover:text-blue-500 transition-colors">↻ Yenilə</button>
                </div>
                <div class="space-y-4 mb-6">
                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex gap-3">
                            <img :src="comment.author?.avatar_url" class="w-8 h-8 rounded-full shrink-0 mt-1">
                            <div class="flex-1">
                                <div class="bg-slate-50 rounded-xl px-4 py-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-slate-800" x-text="comment.author?.full_name"></span>
                                        <span class="text-xs text-slate-400" x-text="formatDate(comment.created_at)"></span>
                                        <span x-show="comment.is_status_comment"
                                              class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">Status dəyişikliyi</span>
                                    </div>
                                    <p class="text-sm text-slate-700" x-text="comment.body"></p>
                                </div>
                                <button x-show="comment.can?.delete" @click="deleteComment(comment.id)"
                                        class="text-xs text-slate-400 hover:text-red-500 mt-1 ml-1">Sil</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="comments.length===0" class="text-sm text-slate-400 italic">Hələ şərh yoxdur</p>
                </div>
                <div class="flex gap-3">
                    <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full shrink-0">
                    <div class="flex-1">
                        <textarea x-model="newComment" rows="2" placeholder="Şərh yazın..."
                                  class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        <div class="flex justify-end mt-2">
                            <button @click="submitComment()" :disabled="!newComment.trim()"
                                    class="text-sm bg-blue-600 text-white px-5 py-2 rounded-xl hover:bg-blue-500 disabled:opacity-40 transition-colors">
                                Göndər
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attachments --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-semibold text-slate-800 mb-4">📎 Əlavələr</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <template x-for="att in (task.attachments||[])" :key="att.id">
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200 group">
                            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-700 text-xs font-bold"
                                 x-text="getExt(att.original_name)"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate" x-text="att.original_name"></p>
                                <p class="text-xs text-slate-400" x-text="att.size_human"></p>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a :href="`/api/attachments/${att.id}/download`"
                                   class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg text-sm">⬇</a>
                                <button x-show="att.can?.delete" @click="deleteAttachment(att.id)"
                                        class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg text-sm">✕</button>
                            </div>
                        </div>
                    </template>
                </div>
                <label class="flex items-center gap-2 cursor-pointer border-2 border-dashed border-slate-200 rounded-xl p-4 hover:border-blue-400 hover:bg-blue-50 transition-colors">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="text-sm text-slate-500">Fayl yükləyin (max 10MB)</span>
                    <input type="file" class="hidden" @change="uploadFile($event)"
                           accept=".txt,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mpg,.avi,.pdf">
                </label>
            </div>
        </div>

        {{-- Sağ: Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h3 class="font-semibold text-slate-800 mb-4 text-sm">Detallar</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Başlama</span>
                        <span class="font-medium text-slate-700" x-text="task.start_date || '—'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Son tarix</span>
                        <span class="font-medium" :class="task.is_overdue ? 'text-red-600' : 'text-slate-700'"
                              x-text="task.due_date || '—'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Təxmini</span>
                        <span class="font-medium text-slate-700" x-text="task.estimated_hours ? task.estimated_hours + ' saat' : '—'"></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h3 class="font-semibold text-slate-800 mb-4 text-sm">Məsul şəxslər</h3>
                <div class="space-y-2.5">
                    <template x-for="a in (task.assignees||[])" :key="a.id">
                        <div class="flex items-center gap-3">
                            <img :src="a.avatar_url" class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-medium text-slate-800" x-text="a.full_name"></p>
                                <p class="text-xs text-slate-400" x-text="a.position"></p>
                            </div>
                        </div>
                    </template>
                    <p x-show="(task.assignees||[]).length===0" class="text-sm text-slate-400 italic">Məsul şəxs yoxdur</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h3 class="font-semibold text-slate-800 mb-4 text-sm">Status Tarixçəsi</h3>
                <div class="space-y-3 max-h-64 overflow-y-auto scrollbar-thin">
                    <template x-for="h in (task.status_history||[])" :key="h.id">
                        <div class="flex gap-2.5 text-xs">
                            <div class="shrink-0 w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5"></div>
                            <div>
                                <p class="text-slate-600">
                                    <span class="font-medium" x-text="h.changed_by?.full_name"></span>
                                    <template x-if="h.from_label">
                                        <span> · <span x-text="h.from_label"></span> → </span>
                                    </template>
                                    <span class="font-medium text-blue-600" x-text="h.to_label"></span>
                                </p>
                                <p class="text-slate-400" x-text="formatDate(h.changed_at)"></p>
                                <p x-show="h.comment" class="text-slate-500 italic mt-0.5" x-text="`"${h.comment}"`"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status comment modal --}}
<div x-data="{ showStatusModal: false, pendingStatus: null, statusComment: '' }"
     @open-status-modal.window="showStatusModal=true; pendingStatus=$event.detail.status; statusComment=''"
     @confirm-status.window="confirmStatus($event.detail)">
    <div x-show="showStatusModal" x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Status dəyişikliyi üçün şərh</h3>
            <textarea x-model="statusComment" rows="3" placeholder="Şərh əlavə edin (opsional)..."
                      class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none mb-4"></textarea>
            <div class="flex gap-3 justify-end">
                <button @click="showStatusModal=false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-xl">Ləğv</button>
                <button @click="window.dispatchEvent(new CustomEvent('confirm-status-change', { detail: { status: pendingStatus, comment: statusComment } })); showStatusModal=false"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-xl hover:bg-blue-500">Dəyiş</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function taskDetail(taskId) {
    return {
        taskId,
        task: {},
        comments: [],
        newComment: '',
        newChecklistItem: '',
        editingTitle: false, editTitle: '',
        editingDesc: false, editDesc: '',
        showSubtaskForm: false,
        newSubtask: { title:'', due_date:'' },
        _pollTimer: null,

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

            // Hər 20 saniyə yenilə
            this._pollTimer = setInterval(() => {
                this.loadTask();
                this.loadComments();
            }, 20_000);

            // Status dəyişikliyi təsdiqi
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
                const res  = await api('GET', `/tasks/${this.taskId}/comments`);
                this.comments = res;
            } catch(e) {}
        },

        changeStatus(status) {
            window.dispatchEvent(new CustomEvent('open-status-modal', { detail: { status } }));
        },

        async doChangeStatus(status, comment) {
            try {
                const res       = await api('PATCH', `/tasks/${this.taskId}/status`, { status, comment });
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
                this.editingDesc = false;
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
                this.newSubtask = { title:'', due_date:'' };
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

        getExt(name) { return name?.split('.').pop().toUpperCase().slice(0,4) || 'FILE'; },
        priorityLabel(p) { return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[p] || p; },
        formatDate(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleDateString('az-AZ', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
        }
    }
}
</script>
@endpush
