@extends('layouts.app')
@section('title', $board->name)
@section('page-title', $space->name)

@section('content')
<div class="min-h-screen bg-[#c9c7d2] px-0 pb-10" x-data="boardHub({{ $space->id }}, {{ $board->id }})" x-init="init()">
    <style>
        .tis-panel {
            background: linear-gradient(180deg, #345090 0%, #314b88 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 14px 30px rgba(19, 30, 73, .12);
        }
        .tis-table-line { border-color: rgba(255,255,255,.13); }
        .tis-dot { width: 13px; height: 13px; border-radius: 9999px; display: inline-block; }
        .tis-avatar-stack img { margin-left: -10px; border: 2px solid #345090; }
        .tis-avatar-stack img:first-child { margin-left: 0; }
        .tis-progress-track { background: rgba(19,34,78,.72); }
        .tis-progress-bar { border-radius: 9999px; height: 100%; }
        .tis-select {
            background: linear-gradient(180deg, #345090 0%, #263f79 100%);
            color: #eef3ff;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 10px;
            min-height: 38px;
            font-size: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15);
        }
        .tis-select-light {
            background: rgba(255,255,255,.28);
            color: #f4f7ff;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 10px;
            min-height: 38px;
            font-size: 14px;
        }
        .tis-export {
            background: linear-gradient(180deg, #6e41c7 0%, #5b33b8 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 10px 18px rgba(82, 44, 173, .18);
        }
        .tis-modal-card {
            background: linear-gradient(180deg, #28447d 0%, #213a72 100%);
        }
    </style>

    <div class="grid grid-cols-12 gap-0">
        <aside class="col-span-12 lg:col-span-3 xl:col-span-3 bg-[#345089] min-h-[calc(100vh-74px)] px-8 py-8 text-white">
            <div class="max-w-[260px] space-y-10">
                <a href="{{ route('spaces.show', $space) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/25 bg-white/5 hover:bg-white/10 transition-colors"
                   title="Space-ə qayıt">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div>
                    <h3 class="text-[19px] font-medium tracking-[0.01em] mb-5">Təsvir</h3>
                    <p class="text-[15px] leading-7 text-white/70">
                        {{ $board->description ?: 'Departamentin işləri ilə bağlı cari tapşırıqlar, prioritetlər və icra vəziyyəti bu lövhədə izlənilir.' }}
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-[19px] font-medium tracking-[0.01em]">Məsul şəxslər</h3>
                        <span class="text-sm text-white/60" x-text="displayMembers.length"></span>
                    </div>

                    <template x-if="membersLoading">
                        <div class="text-sm text-white/60">Yüklənir...</div>
                    </template>

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4" x-show="!membersLoading">
                        <template x-for="m in displayMembers" :key="m.id">
                            <div class="text-center">
                                <img :src="m.avatar_url || defaultAvatar"
                                     :alt="m.full_name"
                                     class="w-[88px] h-[88px] rounded-full mx-auto object-cover border border-white/20 shadow-lg shadow-black/10">
                                <div class="mt-3 text-[13px] leading-5 text-white/85" x-text="m.full_name"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!membersLoading && displayMembers.length === 0" class="text-sm text-white/55">
                        Hələ məsul şəxs təyin olunmayıb.
                    </div>
                </div>
            </div>
        </aside>

        <section class="col-span-12 lg:col-span-9 xl:col-span-9 px-6 md:px-8 py-7">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <select x-model="filters.priority" @change="refresh()" class="tis-select pl-4 pr-10 w-[190px] appearance-none focus:outline-none">
                            <option value="">Bütün prioritetlər</option>
                            <option value="low">Aşağı</option>
                            <option value="medium">Orta</option>
                            <option value="high">Yüksək</option>
                            <option value="urgent">Təcili</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-white/80">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <div class="relative">
                        <select x-model="filters.status" @change="refresh()" class="tis-select pl-4 pr-10 w-[170px] appearance-none focus:outline-none">
                            <option value="">Bütün statuslar</option>
                            <option value="todo">Görüləcək</option>
                            <option value="in_progress">İcra olunur</option>
                            <option value="waiting_for_approve">Təsdiq gözləyir</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="canceled">Ləğv olundu</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-white/80">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <label class="tis-select-light px-4 inline-flex items-center gap-3 text-white/85 select-none cursor-pointer">
                        <span>Son 7 gün</span>
                        <input type="checkbox" x-model="filters.dueSoon" @change="refresh()" class="w-4 h-4 rounded border-white/40 bg-white/80 text-slate-700 focus:ring-0 focus:ring-offset-0">
                    </label>

                    <label class="tis-select-light px-4 inline-flex items-center gap-3 text-[#d84054] font-medium select-none cursor-pointer">
                        <span>Gecikmiş</span>
                        <input type="checkbox" x-model="filters.overdue" @change="refresh()" class="w-4 h-4 rounded border-white/40 bg-white/80 text-red-500 focus:ring-0 focus:ring-offset-0">
                    </label>
                </div>

                <button type="button" class="tis-export inline-flex items-center gap-3 px-5 py-2.5 text-sm font-medium self-start xl:self-auto">
                    <span>Export</span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 16a1 1 0 0 1-1-1V7.41L8.7 9.7a1 1 0 1 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1-1.4 1.4L13 7.4V15a1 1 0 0 1-1 1Zm-7 3a2 2 0 0 1-2-2v-3a1 1 0 1 1 2 0v3h14v-3a1 1 0 1 1 2 0v3a2 2 0 0 1-2 2H5Z"/></svg>
                </button>
            </div>

            <div class="space-y-5">
                <template x-for="s in statusSections" :key="s.key">
                    <section class="tis-panel rounded-[20px] px-6 py-4 text-white overflow-hidden">
                        <div class="flex items-center gap-3 text-[22px] font-medium tracking-[0.01em] mb-4" :style="`color:${s.color}`">
                            <span class="tis-dot" :style="`background:${s.color}`"></span>
                            <span x-text="s.label"></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[880px] text-left table-fixed">
                                <thead>
                                    <tr class="text-white/40 text-[14px] border-b tis-table-line">
                                        <th class="pb-3 font-medium w-[24%]">Ad</th>
                                        <th class="pb-3 font-medium w-[18%]">Layihə</th>
                                        <th class="pb-3 font-medium w-[14%]">Məsul şəxslər</th>
                                        <th class="pb-3 font-medium w-[14%]">Təyin edən</th>
                                        <th class="pb-3 font-medium w-[10%]">Son tarix</th>
                                        <th class="pb-3 font-medium w-[10%]">Prioritet</th>
                                        <th class="pb-3 font-medium w-[10%]">İrəliləyiş</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="(grouped[s.key] || []).length === 0">
                                        <tr>
                                            <td colspan="7" class="py-5 text-sm text-white/55">Tapşırıq yoxdur</td>
                                        </tr>
                                    </template>

                                    <template x-for="t in (grouped[s.key] || [])" :key="t.id">
                                        <tr class="border-b tis-table-line last:border-b-0 hover:bg-white/5 cursor-pointer transition-colors" @click="openTaskModal(t.id)">
                                            <td class="py-3.5 pr-4 align-middle">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="w-3 h-3 rounded-full shrink-0" :style="`background:${taskStatusDotColor(t.status, t.is_overdue)}`"></span>
                                                    <span class="truncate text-[15px]" :class="t.is_overdue ? 'text-red-400' : 'text-white/92'" x-text="t.title"></span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 pr-4 align-middle text-[15px] text-white/92">
                                                <span class="truncate block" x-text="boardNameForRow(t)"></span>
                                            </td>
                                            <td class="py-3.5 pr-4 align-middle">
                                                <template x-if="(t.assignees || []).length === 1">
                                                    <div class="flex items-center gap-2.5 text-[15px] text-white/92">
                                                        <img :src="t.assignees[0]?.avatar_url || defaultAvatar" class="w-7 h-7 rounded-full object-cover border border-white/15">
                                                        <span class="truncate" x-text="t.assignees[0]?.full_name || '—'"></span>
                                                    </div>
                                                </template>
                                                <template x-if="(t.assignees || []).length > 1">
                                                    <div class="flex items-center tis-avatar-stack">
                                                        <template x-for="a in (t.assignees || []).slice(0,3)" :key="a.id">
                                                            <img :src="a.avatar_url || defaultAvatar" :title="a.full_name" class="w-8 h-8 rounded-full object-cover">
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="(t.assignees || []).length === 0">
                                                    <span class="text-white/45 text-sm">—</span>
                                                </template>
                                            </td>
                                            <td class="py-3.5 pr-4 align-middle">
                                                <div class="flex items-center gap-2.5 text-[15px] text-white/92">
                                                    <img :src="t.assigner?.avatar_url || t.creator?.avatar_url || defaultAvatar" class="w-7 h-7 rounded-full object-cover border border-white/15">
                                                    <span class="truncate" x-text="t.assigner?.full_name || t.creator?.full_name || '—'"></span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 pr-4 align-middle text-[15px] text-white/92" x-text="t.due_date ? formatDate(t.due_date) : '—'"></td>
                                            <td class="py-3.5 pr-4 align-middle text-[15px] text-white/92" x-text="priorityLabel(t.priority) || 'Normal'"></td>
                                            <td class="py-3.5 align-middle">
                                                <div class="flex items-center gap-3">
                                                    <div class="tis-progress-track w-[84px] h-[9px] rounded-full overflow-hidden shrink-0">
                                                        <div class="tis-progress-bar" :style="`width:${progressPercent(t)}%; background:${progressColor(t)}`"></div>
                                                    </div>
                                                    <span class="text-[15px] text-white/92 min-w-[38px]" x-text="`${progressPercent(t)}%`"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>
            </div>
        </section>
    </div>

    <div x-show="taskModalOpen" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="tis-modal-card text-white rounded-[26px] shadow-2xl w-full max-w-6xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-7 py-5 border-b border-white/10 flex items-center justify-between">
                <div class="min-w-0">
                    <h2 class="text-[18px] font-semibold truncate" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                    <p class="text-xs text-white/55 mt-1" x-text="taskDetail?.space?.name || @js($space->name)"></p>
                </div>
                <button @click="closeTaskModal()" class="text-white/60 hover:text-white text-xl">✕</button>
            </div>

            <div class="grid grid-cols-12 gap-0 overflow-y-auto">
                <div class="col-span-12 lg:col-span-7 p-7 space-y-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-white/55 mb-1">Status</div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5">
                                <span class="w-2.5 h-2.5 rounded-full" :style="`background:${taskStatusDotColor(taskDetail?.status, taskDetail?.is_overdue)}`"></span>
                                <span x-text="statusLabel(taskDetail?.status)"></span>
                            </div>
                        </div>
                        <div>
                            <div class="text-white/55 mb-1">Prioritet</div>
                            <div class="text-white/90" x-text="priorityLabel(taskDetail?.priority)"></div>
                        </div>
                        <div>
                            <div class="text-white/55 mb-1">Tarix</div>
                            <div class="text-white/90" x-text="taskDetail?.start_date ? `${formatDate(taskDetail?.start_date)} - ${formatDate(taskDetail?.due_date)}` : (taskDetail?.due_date ? formatDate(taskDetail?.due_date) : '—')"></div>
                        </div>
                        <div>
                            <div class="text-white/55 mb-1">İrəliləyiş</div>
                            <div class="flex items-center gap-3">
                                <div class="tis-progress-track w-[90px] h-[10px] rounded-full overflow-hidden">
                                    <div class="tis-progress-bar" :style="`width:${progressPercent(taskDetail)}%; background:${progressColor(taskDetail)}`"></div>
                                </div>
                                <span x-text="`${progressPercent(taskDetail)}%`"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-white/55 text-sm mb-2">Təsvir</div>
                        <div class="text-white/85 leading-7 whitespace-pre-wrap" x-text="taskDetail?.description || 'Təsvir əlavə edilməyib.'"></div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-white/90 font-medium">Alt tapşırıqlar</div>
                            <div class="text-white/55 text-sm" x-text="subtaskSummary()"></div>
                        </div>
                        <div class="space-y-2.5">
                            <template x-for="item in (taskDetail?.subtasks || [])" :key="item.id">
                                <div class="flex items-center justify-between gap-3 text-sm text-white/90 border-b border-white/10 pb-2.5">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="`background:${taskStatusDotColor(item.status, item.is_overdue)}`"></span>
                                        <span class="truncate" x-text="item.title"></span>
                                    </div>
                                    <span class="text-white/50 text-xs whitespace-nowrap" x-text="statusLabel(item.status)"></span>
                                </div>
                            </template>

                            <template x-for="item in (taskDetail?.checklists || [])" :key="`check-${item.id}`">
                                <label class="flex items-center gap-3 text-sm text-white/90 border-b border-white/10 pb-2.5">
                                    <input type="checkbox" class="rounded accent-emerald-500" :checked="item.is_done" disabled>
                                    <span :class="item.is_done ? 'line-through text-white/45' : ''" x-text="item.title"></span>
                                </label>
                            </template>

                            <div x-show="(taskDetail?.subtasks || []).length === 0 && (taskDetail?.checklists || []).length === 0" class="text-sm text-white/45">Alt tapşırıq yoxdur</div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-5 bg-black/10 p-7 space-y-6">
                    <div>
                        <div class="text-white/55 text-sm mb-3">Məsul şəxslər</div>
                        <div class="space-y-3">
                            <template x-for="a in (taskDetail?.assignees || [])" :key="a.id">
                                <div class="flex items-center gap-3 rounded-2xl bg-white/5 px-4 py-3">
                                    <img :src="a.avatar_url || defaultAvatar" class="w-10 h-10 rounded-full object-cover">
                                    <div class="min-w-0">
                                        <div class="text-sm text-white/90 truncate" x-text="a.full_name"></div>
                                        <div class="text-xs text-white/50 truncate" x-text="a.position || ''"></div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="(taskDetail?.assignees || []).length === 0" class="text-sm text-white/45">Məsul şəxs yoxdur</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-white/55 text-sm mb-3">Şərhlər</div>
                        <div class="rounded-2xl bg-[#183062] min-h-[280px] p-4 flex flex-col gap-3">
                            <div class="flex-1 space-y-3 overflow-y-auto max-h-[320px] pr-1">
                                <template x-for="comment in comments" :key="comment.id">
                                    <div class="rounded-2xl bg-white/5 px-4 py-3">
                                        <div class="flex items-center gap-3 mb-2">
                                            <img :src="comment.author?.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover">
                                            <div class="min-w-0">
                                                <div class="text-sm text-white/90 truncate" x-text="comment.author?.full_name || 'İstifadəçi'"></div>
                                                <div class="text-[11px] text-white/45" x-text="formatDateTime(comment.created_at)"></div>
                                            </div>
                                        </div>
                                        <div class="text-sm text-white/85 whitespace-pre-wrap leading-6" x-text="comment.body || comment.content || '—'"></div>
                                    </div>
                                </template>

                                <div x-show="commentsLoading" class="text-sm text-white/55">Şərhlər yüklənir...</div>
                                <div x-show="!commentsLoading && comments.length === 0" class="text-sm text-white/45">Şərh yoxdur</div>
                            </div>

                            <div class="pt-2 border-t border-white/10">
                                <div class="text-xs text-white/45 mb-2">Şərh yazın</div>
                                <div class="rounded-full bg-white text-slate-700 px-4 py-3 text-sm">Şərh yazın</div>
                            </div>
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
        commentsLoading: false,
        comments: [],
        defaultAvatar: 'https://ui-avatars.com/api/?name=User&background=1f3b75&color=fff',
        filters: {
            priority: '',
            status: '',
            dueSoon: false,
            overdue: false,
        },
        statusSections: [
            { key:'in_progress',         label:'İcra olunur',     color:'#f7aa14' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir', color:'#955bf7' },
            { key:'completed',           label:'Tamamlandı',      color:'#0cc53f' },
            { key:'todo',                label:'Görüləcək',       color:'#c4c8d6' },
            { key:'canceled',            label:'Ləğv olundu',     color:'#ef4444' },
        ],

        taskModalOpen: false,
        taskDetail: null,

        get displayMembers() {
            return this.members;
        },

        async init() {
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

        async refresh() {
            this.membersLoading = true;
            const params = new URLSearchParams();
            params.set('board_id', this.boardId);
            params.set('grouped', '1');
            if (this.filters.priority) params.set('priority', this.filters.priority);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.dueSoon) params.set('due_soon', '1');
            if (this.filters.overdue) params.set('overdue', '1');

            const data = await api('GET', `/spaces/${this.spaceId}/tasks?${params.toString()}`);
            this.grouped = data || {};

            if (this.filters.status && !this.grouped[this.filters.status]) {
                this.grouped[this.filters.status] = [];
            }

            this.collectMembersFromTasks();
            this.membersLoading = false;
        },

        async openTaskModal(id) {
            this.taskModalOpen = true;
            this.taskDetail = null;
            this.comments = [];
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
                medium: 'Normal',
                high: 'High',
                urgent: 'Təcili',
            }[p] || 'Normal';
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
            if (t.status === 'completed' || t.status === 'waiting_for_approve') return 100;
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
            return t.board?.name || @js($board->name);
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
}
</script>
@endpush
