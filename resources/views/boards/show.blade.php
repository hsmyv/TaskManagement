@extends('layouts.app')
@section('title', $board->name)
@section('page-title', $board->name . ' — Board')

@section('content')
<div class="p-6" x-data="boardHub({{ $space->id }}, {{ $board->id }})" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-slate-500">
            <a href="{{ route('spaces.show', $space) }}" class="hover:text-blue-600">← Space</a>
            <span class="mx-2">/</span>
            <span class="text-slate-700 font-medium">{{ $board->name }}</span>
        </div>
        <button @click="refresh()"
                class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
            Yenilə
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: Board members (məsul şəxslər) --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-slate-900 rounded-2xl p-4 text-white shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Məsul şəxslər</h3>
                    <span class="text-xs text-white/60" x-text="`${(members||[]).length}`"></span>
                </div>
                <div class="space-y-2 max-h-[620px] overflow-y-auto scrollbar-thin pr-1">
                    <template x-if="membersLoading">
                        <div class="text-sm text-white/60">Yüklənir...</div>
                    </template>
                    <template x-for="m in (members||[])" :key="m.id">
                        <div class="flex items-center gap-3 bg-white/10 rounded-xl p-3">
                            <img :src="m.avatar_url" class="w-9 h-9 rounded-full">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate" x-text="m.full_name"></p>
                                <p class="text-xs text-white/60 truncate" x-text="m.position || ''"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right: status groups --}}
        <div class="col-span-12 lg:col-span-9 space-y-4">
            <template x-for="s in statusSections" :key="s.key">
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 font-semibold text-white" :class="s.headerClass">
                        <div class="flex items-center justify-between">
                            <span x-text="s.label"></span>
                            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full"
                                  x-text="(grouped[s.key] || []).length"></span>
                        </div>
                    </div>
                    <div class="bg-white">
                        <template x-if="(grouped[s.key] || []).length === 0">
                            <div class="px-5 py-6 text-sm text-slate-400">Tapşırıq yoxdur</div>
                        </template>
                        <template x-if="(grouped[s.key] || []).length > 0">
                            <div class="divide-y divide-slate-100">
                                <template x-for="t in (grouped[s.key] || [])" :key="t.id">
                                    <div class="px-5 py-3 hover:bg-slate-50 cursor-pointer"
                                         @click="openTaskModal(t.id)">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 truncate" x-text="t.title"></p>
                                                <div class="mt-1 flex items-center gap-2 text-xs text-slate-400">
                                                    <span x-text="t.creator?.full_name || ''"></span>
                                                    <span x-show="t.assignees?.length">·</span>
                                                    <span x-show="t.assignees?.length" x-text="`${t.assignees.length} məsul`"></span>
                                                </div>
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
    </div>

    {{-- Task modal --}}
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
                        <span class="px-2 py-1 rounded-full bg-white/10" x-text="taskDetail?.start_date ? ('🗓 ' + formatDate(taskDetail?.start_date)) : ''"></span>
                        <span class="px-2 py-1 rounded-full bg-white/10" x-text="taskDetail?.due_date ? ('⏰ ' + formatDate(taskDetail?.due_date)) : ''"></span>
                    </div>

                    <div class="text-sm text-white/80 whitespace-pre-wrap" x-text="taskDetail?.description || '—'"></div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold">Yoxlama siyahısı</p>
                            <p class="text-xs text-white/60"
                               x-text="taskDetail?.checklists ? `${taskDetail.checklists.filter(i=>i.is_done).length}/${taskDetail.checklists.length}` : '0/0'"></p>
                        </div>
                        <div class="space-y-2">
                            <template x-for="i in (taskDetail?.checklists || [])" :key="i.id">
                                <label class="flex items-center gap-3 text-sm">
                                    <input type="checkbox" class="rounded accent-blue-500"
                                           :checked="i.is_done" disabled>
                                    <span :class="i.is_done ? 'line-through text-white/50' : 'text-white/85'"
                                          x-text="i.title"></span>
                                </label>
                            </template>
                            <div x-show="(taskDetail?.checklists||[]).length===0" class="text-sm text-white/40">
                                Checklist yoxdur
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4 bg-white/5 p-6 space-y-4">
                    <div>
                        <p class="text-xs text-white/60 mb-2">Məsul şəxslər</p>
                        <div class="space-y-2">
                            <template x-for="a in (taskDetail?.assignees || [])" :key="a.id">
                                <div class="flex items-center gap-3 bg-white/5 rounded-xl p-3">
                                    <img :src="a.avatar_url" class="w-9 h-9 rounded-full">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold truncate" x-text="a.full_name"></p>
                                        <p class="text-xs text-white/60 truncate" x-text="a.position || ''"></p>
                                    </div>
                                </div>
                            </template>
                            <div x-show="(taskDetail?.assignees||[]).length===0" class="text-sm text-white/40">
                                Məsul şəxs yoxdur
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white/5 p-4">
                        <p class="text-xs text-white/60">Əlavələr / Şərhlər</p>
                        <div class="mt-2 text-sm text-white/80">
                            <span x-text="`📎 ${(taskDetail?.attachments_count ?? 0)}`"></span>
                            <span class="mx-2 text-white/30">·</span>
                            <span x-text="`💬 ${(taskDetail?.comments_count ?? 0)}`"></span>
                            <span class="mx-2 text-white/30">·</span>
                            <span x-text="`⊂ ${(taskDetail?.subtasks_count ?? 0)}`"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function boardHub(spaceId, boardId) {
    return {
        spaceId,
        boardId,
        grouped: {},
        membersLoading: false,
        members: [],
        statusSections: [
            { key:'in_progress',         label:'İcra olunur',       headerClass:'bg-blue-600' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir',   headerClass:'bg-purple-600' },
            { key:'completed',           label:'Tamamlandı',        headerClass:'bg-emerald-600' },
            { key:'todo',                label:'Görüləcək',         headerClass:'bg-slate-600' },
            { key:'canceled',            label:'Ləğv olundu',        headerClass:'bg-rose-600' },
        ],

        taskModalOpen: false,
        taskDetail: null,

        async init() {
            await Promise.all([this.refresh(), this.loadMembers()]);
        },

        async refresh() {
            const data = await api('GET', `/spaces/${this.spaceId}/tasks?board_id=${this.boardId}&grouped=1`);
            this.grouped = data || {};
        },

        async loadMembers() {
            this.membersLoading = true;
            try {
                const res = await api('GET', `/boards/${this.boardId}/members`);
                this.members = res.data || res || [];
            } catch(e) {
                this.members = [];
            } finally {
                this.membersLoading = false;
            }
        },

        async openTaskModal(id) {
            this.taskModalOpen = true;
            this.taskDetail = null;
            try {
                this.taskDetail = await api('GET', `/tasks/${id}`);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
                this.taskModalOpen = false;
            }
        },

        closeTaskModal() {
            this.taskModalOpen = false;
            this.taskDetail = null;
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
</script>
@endpush

