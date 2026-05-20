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
            background: linear-gradient(180deg, rgba(52,80,144,.95) 0%, rgba(38,63,121,.95) 100%);
            color: #eef3ff;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 14px;
            min-height: 44px;
            font-size: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 12px 24px rgba(24, 38, 86, .12);
            backdrop-filter: blur(10px);
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .tis-select:hover,
        .tis-select:focus {
            border-color: rgba(255,255,255,.46);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 16px 28px rgba(24, 38, 86, .18);
        }
        .tis-select-light {
            background: rgba(255,255,255,.28);
            color: #f4f7ff;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 14px;
            min-height: 44px;
            font-size: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.16), 0 12px 24px rgba(24, 38, 86, .1);
            backdrop-filter: blur(10px);
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
        .tis-modal-scroll { scrollbar-width: thin; scrollbar-color: transparent transparent; }
        .tis-modal-scroll:hover, .tis-modal-scroll:focus, .tis-modal-scroll:focus-within { scrollbar-color: rgba(255,255,255,.28) transparent; }
        .tis-modal-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
        .tis-modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .tis-modal-scroll::-webkit-scrollbar-thumb { background: transparent; border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
        .tis-modal-scroll:hover::-webkit-scrollbar-thumb, .tis-modal-scroll:focus::-webkit-scrollbar-thumb, .tis-modal-scroll:focus-within::-webkit-scrollbar-thumb { background-color: rgba(255,255,255,.28); }
        .tis-filter-menu {
            background: rgba(24, 43, 93, .96);
            border: 1px solid rgba(255,255,255,.16);
            box-shadow: 0 24px 50px rgba(13, 25, 62, .28);
            backdrop-filter: blur(16px);
        }
        .tis-board-scroll {
            max-height: calc(100vh - 190px);
            overflow-y: auto;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.24) transparent;
        }
        .tis-board-scroll::-webkit-scrollbar { width: 8px; }
        .tis-board-scroll::-webkit-scrollbar-track { background: transparent; }
        .tis-board-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.24);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        .tis-modern-select {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 12px;
            min-height: 38px;
            padding: 0 34px 0 12px;
            color: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
        }
    </style>

    <div class="grid grid-cols-12 gap-0">
        <aside class="col-span-12 lg:col-span-2 xl:col-span-2 bg-[#345089] min-h-[calc(100vh-74px)] px-5 py-7 text-white">
            <div class="max-w-[250px] space-y-8">
                <a href="{{ route('spaces.detail', $space) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/25 bg-white/5 hover:bg-white/10 transition-colors"
                   title="Space-ə qayıt">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div>
                    <h2 class="text-[19px] font-medium tracking-[0.01em] mb-5">{{ $board->name ?? '' }}</h2>
                    @if($board->deadline)
                        <h2 class="text-[19px] font-medium tracking-[0.01em] mb-5">
                            Son tarix: {{ \Carbon\Carbon::parse($board->deadline)->format('d.m.Y') }}
                        </h2>
                    @endif
                  <h3 class="text-[19px] font-medium tracking-[0.01em] mt-5 mb-5">Təsvir</h3>
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

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4 tis-board-scroll max-h-[360px]" x-show="!membersLoading">
                        <template x-for="m in displayMembers" :key="m.id">
                            <div class="text-center">
                                <img :src="m.avatar_url || defaultAvatar"
                                     :alt="m.full_name"
                                     class="w-16 h-16 rounded-full mx-auto object-cover border border-white/20 shadow-lg shadow-black/10">
                                <div class="mt-2 text-[11px] leading-4 text-white/85" x-text="m.full_name"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!membersLoading && displayMembers.length === 0" class="text-sm text-white/55">
                        Hələ məsul şəxs təyin olunmayıb.
                    </div>
                </div>
            </div>
        </aside>

        <section class="col-span-12 lg:col-span-10 xl:col-span-10 px-6 md:px-8 py-7">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <template x-for="filter in filterMenus" :key="filter.key">
                        <div class="relative" x-data="{ open:false }">
                            <button type="button" @click="open=!open" class="tis-select min-w-[160px] px-4 pr-10 inline-flex items-center justify-between gap-3 focus:outline-none">
                                <span x-text="filterLabel(filter.key)"></span>
                                <svg class="w-4 h-4 text-white/80 absolute right-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition @click.outside="open=false" class="tis-filter-menu absolute left-0 top-[calc(100%+8px)] z-40 w-56 rounded-2xl p-2 text-white">
                                <template x-for="option in filter.options" :key="`${filter.key}-${option.value}`">
                                    <button type="button" @click="setFilter(filter.key, option.value); open=false" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm hover:bg-white/10 transition-colors" :class="isFilterSelected(filter.key, option.value) ? 'bg-white/12 text-white' : 'text-white/75'">
                                        <span x-text="option.label"></span>
                                        <span x-show="isFilterSelected(filter.key, option.value)" class="w-2 h-2 rounded-full bg-[#7ee787]"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div class="flex items-center gap-2 rounded-[14px] bg-white/25 border border-white/25 px-3 py-2 text-white">
                        <span class="text-xs text-white/75">Tarix</span>
                        <input type="date" x-model="filters.dateFrom" @change="refresh()" class="h-8 rounded-lg bg-white/90 text-slate-700 px-2 text-xs focus:outline-none">
                        <span class="text-white/50">-</span>
                        <input type="date" x-model="filters.dateTo" @change="refresh()" class="h-8 rounded-lg bg-white/90 text-slate-700 px-2 text-xs focus:outline-none">
                    </div>
                    <div class="hidden relative">
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

                    <div class="hidden relative">
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

                    <div class="hidden relative">
                        <select x-model="filters.dueSoon" @change="refresh()" class="tis-select-light pl-4 pr-10 w-[150px] appearance-none focus:outline-none">
                            <option :value="false">Bütün tarixlər</option>
                            <option :value="true">Son 7 gün</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-white/80">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <div class="hidden relative">
                        <select x-model="filters.overdue" @change="refresh()" class="tis-select-light pl-4 pr-10 w-[145px] appearance-none focus:outline-none">
                            <option :value="false">Hamısı</option>
                            <option :value="true">Gecikmiş</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-white/80">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <label class="hidden tis-select-light px-4 items-center gap-3 text-white/85 select-none cursor-pointer">
                        <span>Son 7 gün</span>
                        <input type="checkbox" x-model="filters.dueSoon" @change="refresh()" class="w-4 h-4 rounded border-white/40 bg-white/80 text-slate-700 focus:ring-0 focus:ring-offset-0">
                    </label>

                    <label class="hidden tis-select-light px-4 items-center gap-3 text-[#d84054] font-medium select-none cursor-pointer">
                        <span>Gecikmiş</span>
                        <input type="checkbox" x-model="filters.overdue" @change="refresh()" class="w-4 h-4 rounded border-white/40 bg-white/80 text-red-500 focus:ring-0 focus:ring-offset-0">
                    </label>
                </div>

                <label class="tis-select-light px-4 inline-flex items-center gap-3 text-white/85 select-none cursor-pointer">
                    <span>Only me</span>
                    <input type="checkbox" x-model="filters.onlyMe" @change="localStorage.setItem(`board:${boardId}:onlyMe`, filters.onlyMe ? '1' : '0'); refresh()" class="w-4 h-4 rounded border-white/40 bg-white/80 text-slate-700 focus:ring-0 focus:ring-offset-0">
                </label>

                <div class="flex items-center gap-3 self-start xl:self-auto">
                    @can('update', $board)
                    <button type="button" @click="archiveBoard()" class="inline-flex items-center gap-3 px-5 py-2.5 min-h-11 rounded-[14px] bg-white/10 hover:bg-white/15 border border-white/20 text-white text-sm font-medium shadow-lg transition-all">
                        Arşivlə
                    </button>
                    @endcan
                    <button type="button" @click="openCreateTask()" class="inline-flex items-center gap-3 px-5 py-2.5 min-h-11 rounded-[14px] bg-[#102a52] hover:bg-[#153462] border border-white/40 text-white text-sm font-medium shadow-lg transition-all">
                        <span class="text-2xl leading-none -mt-0.5">+</span>
                        <span>Tapşırıq</span>
                    </button>
                    <button type="button" @click="exportTasks()" class="tis-export inline-flex items-center gap-3 px-5 py-2.5 min-h-11 text-sm font-medium">
                        <span>Export</span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 16a1 1 0 0 1-1-1V7.41L8.7 9.7a1 1 0 1 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1-1.4 1.4L13 7.4V15a1 1 0 0 1-1 1Zm-7 3a2 2 0 0 1-2-2v-3a1 1 0 1 1 2 0v3h14v-3a1 1 0 1 1 2 0v3a2 2 0 0 1-2 2H5Z"/></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-5 tis-board-scroll">
                <template x-for="s in statusSections" :key="s.key">
                    <section class="tis-panel rounded-[20px] px-6 py-4 text-white overflow-hidden"
                             @dragover.prevent
                             @drop.prevent="onDropToStatus(s.key)">
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
                                        <tr class="border-b tis-table-line last:border-b-0 hover:bg-white/5 cursor-pointer transition-colors"
                                            draggable="true"
                                            @dragstart.stop="onDragTaskStart(t, $event)"
                                            @dragend="draggedTask = null"
                                            @click="openTaskModal(t.id)">
                                            <td class="py-3.5 pr-4 align-middle">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="w-3 h-3 rounded-full shrink-0" :style="`background:${taskStatusDotColor(t.status, t.is_overdue)}`"></span>
                                                    <div class="min-w-0">
                                                        <span class="truncate block text-[15px]" :title="t.title" :class="t.is_overdue ? 'text-red-400' : 'text-white/92'" x-text="t.title"></span>
                                                        <div class="mt-1 flex items-center gap-3 text-[11px] text-white/45">
                                                            <span x-text="`Şərh ${t.comments_count ?? 0}`"></span>
                                                            <span x-text="`Fayl ${t.attachments_count ?? 0}`"></span>
                                                            <span x-text="`Alt ${t.completed_subtasks_count ?? 0}/${t.subtasks_count ?? ((t.subtasks || []).length)}`"></span>
                                                        </div>
                                                    </div>
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
                                            <td class="py-3.5 pr-4 align-middle text-[15px] text-white/92" x-text="priorityLabel(t.priority) || 'Orta'"></td>
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

    <div x-show="showCreateModal" x-transition.opacity class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="w-full max-w-2xl max-h-[90vh] overflow-y-auto tis-modal-scroll rounded-[30px] bg-gradient-to-b from-[#233d82] to-[#182b5d] border border-white/10 shadow-2xl text-white">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-lg">Yeni Tapşırıq</h2>
                    <p class="text-xs text-white/50 mt-1">{{ $board->name }} boarduna əlavə olunacaq</p>
                </div>
                <button @click="showCreateModal = false" class="text-white/60 hover:text-white">x</button>
            </div>
            <form @submit.prevent="createTask()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Başlıq *</label>
                    <input type="text" x-model="newTask.title" required placeholder="Tapşırığın adı..." class="w-full h-12 rounded-xl px-4 tis-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Təsvir</label>
                    <textarea x-model="newTask.description" rows="3" placeholder="Ətraflı təsvir..." class="w-full rounded-xl px-4 py-3 tis-input resize-none"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-1">Başlama tarixi</label>
                        <input type="date" x-model="newTask.start_date" class="w-full h-12 rounded-xl px-4 tis-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-1">Son icra tarixi</label>
                        <input type="date" x-model="newTask.due_date" class="w-full h-12 rounded-xl px-4 tis-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Prioritet</label>
                    <select x-model="newTask.priority" class="w-full h-12 rounded-xl px-4 tis-input">
                        <option value="low">Aşağı</option>
                        <option value="medium">Orta</option>
                        <option value="high">Yüksək</option>
                        <option value="urgent">Təcili</option>
                    </select>
                </div>
                <div x-data="employeePicker(spaceId)" x-init="init()">
                    <label class="block text-sm font-medium text-white/80 mb-1">Məsul şəxslər</label>
                    <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Ad ilə axtarın..." class="w-full h-12 rounded-xl px-4 tis-input">
                    <div class="flex flex-wrap gap-2 mt-3">
                        <template x-for="emp in selected" :key="emp.id">
                            <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-2 rounded-full border border-white/10">
                                <img :src="emp.avatar_url || defaultAvatar" class="w-5 h-5 rounded-full object-cover">
                                <span x-text="emp.full_name"></span>
                                <button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button>
                            </span>
                        </template>
                    </div>
                    <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-48 overflow-y-auto tis-modal-scroll">
                        <template x-for="emp in results" :key="emp.id">
                            <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 text-left text-sm">
                                <img :src="emp.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-white" x-text="emp.full_name"></p>
                                    <p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                    <span x-effect="newTask.assignee_ids = selected.map(e => e.id)"></span>
                </div>
                <div x-data="employeePicker(null)" x-init="init([], true)">
                    <label class="block text-sm font-medium text-white/80 mb-1">Kim tərəfindən (istəyə bağlı)</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="if (!results.length) loadAllEmployees()" placeholder="İşçini axtar..." class="w-full h-12 rounded-xl px-4 tis-input">
                        <button type="button" @click="loadAllEmployees()" class="px-4 rounded-xl bg-white/10 border border-white/10 hover:bg-white/15 text-sm">Bax</button>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3" x-show="selected.length">
                        <template x-for="emp in selected" :key="emp.id">
                            <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-2 rounded-full border border-white/10">
                                <img :src="emp.avatar_url || defaultAvatar" class="w-5 h-5 rounded-full object-cover">
                                <span x-text="emp.full_name"></span>
                                <button type="button" @click="remove(emp.id); newTask.assigned_by_id = null;" class="hover:text-red-300">x</button>
                            </span>
                        </template>
                    </div>
                    <div x-show="open" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-48 overflow-y-auto tis-modal-scroll">
                        <template x-if="!results.length">
                            <div class="px-4 py-3 text-sm text-white/60">Nəticə tapılmadı</div>
                        </template>
                        <template x-for="emp in results" :key="emp.id">
                            <button type="button" @click="select(emp); newTask.assigned_by_id = emp.id" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 text-left text-sm">
                                <img :src="emp.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-white" x-text="emp.full_name"></p>
                                    <p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal=false" class="px-5 py-2.5 text-sm bg-white/8 hover:bg-white/12 rounded-xl">Ləğv et</button>
                    <button type="submit" :disabled="creating" class="px-5 py-2.5 text-sm font-medium bg-[#6d44c5] hover:bg-[#613db1] rounded-xl disabled:opacity-60">
                        <span x-text="creating ? 'Yaradılır...' : 'Yarat'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="taskModalOpen" x-transition.opacity class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-3 sm:p-4">
        <div @click.stop x-transition.scale class="w-full max-w-5xl h-[88vh] overflow-hidden rounded-[24px] bg-gradient-to-b from-[#1f397e] to-[#182d65] border border-white/10 shadow-2xl text-white">
            <div class="px-5 py-4 flex items-center justify-between border-b border-white/10">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-[18px] sm:text-[22px] font-semibold whitespace-normal break-words leading-snug" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                        <template x-if="taskDetail?.assigner?.full_name">
                            <span class="text-sm text-white/65">- <span x-text="taskDetail.assigner.full_name"></span> tərəfindən</span>
                        </template>
                        <button x-show="canShowApprovePrompt(taskDetail)" @click="scrollToApproval()" class="text-xs sm:text-sm px-3 py-1.5 rounded-full bg-[#f3ad1e]/18 border border-[#f3ad1e]/50 text-[#ffd37a] hover:bg-[#f3ad1e]/25 transition-all">- təsdiqlə</button>
                    </div>
                    <p class="text-xs sm:text-sm text-white/55 mt-1" x-text="taskDetail?.board?.name || @js($board->name)"></p>
                </div>
                <button @click="closeTaskModal()" class="text-white/60 hover:text-white text-lg">x</button>
            </div>
            <div class="grid grid-cols-12 h-[calc(88vh-72px)] overflow-hidden">
                <div x-ref="taskModalBody" class="col-span-12 lg:col-span-7 p-4 sm:p-5 space-y-4 overflow-y-auto tis-modal-scroll">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <p class="text-white/45 mb-1 text-xs">Status</p>
                            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/8 border border-white/10">
                                <span class="w-2.5 h-2.5 rounded-full" :style="`background:${taskStatusDotColor(taskDetail?.status, taskDetail?.is_overdue)}`"></span>
                                <select x-show="canEditTask(taskDetail)" x-model="taskDetail.status" @change="saveTaskStatus(taskDetail.status)" class="tis-modern-select text-sm focus:outline-none">
                                    <template x-for="s in statusSections" :key="`modal-status-${s.key}`">
                                        <option class="text-slate-900" :value="s.key" x-text="s.label"></option>
                                    </template>
                                </select>
                                <span x-show="!canEditTask(taskDetail)" x-text="statusLabel(taskDetail?.status)"></span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <p class="text-white/45 mb-1 text-xs">Prioritet</p>
                            <select x-show="canEditTask(taskDetail)" x-model="taskDetail.priority" @change="saveTaskPriority(taskDetail.priority)" class="tis-modern-select w-full font-medium focus:outline-none">
                                <option class="text-slate-900" value="low">Aşağı</option>
                                <option class="text-slate-900" value="medium">Orta</option>
                                <option class="text-slate-900" value="high">Yüksək</option>
                                <option class="text-slate-900" value="urgent">Təcili</option>
                            </select>
                            <p x-show="!canEditTask(taskDetail)" class="text-white font-medium" x-text="priorityLabel(taskDetail?.priority) || 'Orta'"></p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Təsvir</h3>
                            <button x-show="canEditTask(taskDetail)" @click="openTaskMainEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                            <div class="flex items-center gap-3 min-w-[110px]">
                                <div class="h-2.5 flex-1 rounded-full bg-[#0d214d] overflow-hidden">
                                    <div class="h-2.5 rounded-full bg-[#c5a13c]" :style="`width:${progressPercent(taskDetail)}%`"></div>
                                </div>
                                <span class="text-xs text-white/70" x-text="`${progressPercent(taskDetail) || 0}%`"></span>
                            </div>
                        </div>
                        <div x-show="!editingTaskMain" class="space-y-2">
                            <p class="text-sm text-white/72 leading-6 whitespace-pre-wrap" x-text="taskDetail?.description || 'Departamentin işləri ilə bağlı tapşırıq təsviri əlavə edilməyib.'"></p>
                        </div>
                        <div x-show="editingTaskMain" class="space-y-3">
                            <input type="text" x-model="taskMainForm.title" class="w-full h-11 rounded-xl px-4 tis-input" placeholder="Başlıq">
                            <textarea x-model="taskMainForm.description" rows="4" class="w-full rounded-xl px-4 py-3 tis-input resize-none" placeholder="Təsvir"></textarea>
                            <div class="flex justify-end gap-2"><button @click="editingTaskMain=false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="saveTaskMain()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3" x-show="taskDetail">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-base font-semibold">Məsul şəxslər</h3>
                                <button x-show="canEditTask(taskDetail)" @click="openTaskAssigneeEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                            </div>
                            <div class="space-y-2" x-show="!editingTaskAssignees">
                                <template x-if="(taskDetail?.assignees || []).length === 0"><div class="text-sm text-white/55">Məsul şəxs seçilməyib</div></template>
                                <template x-for="person in (taskDetail?.assignees || [])" :key="`detail-assignee-new-${person.id}`">
                                    <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                        <img :src="person.avatar_url || defaultAvatar" class="w-9 h-9 rounded-full object-cover">
                                        <div class="min-w-0"><p class="text-sm font-medium truncate" x-text="person.full_name"></p><p class="text-[11px] text-white/50 truncate" x-text="person.position || person.email || ''"></p></div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="editingTaskAssignees" class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="emp in selectedTaskAssignees" :key="`selected-new-${emp.id}`">
                                        <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10"><img :src="emp.avatar_url || defaultAvatar" class="w-4 h-4 rounded-full object-cover"><span x-text="emp.full_name"></span><button type="button" @click="removeTaskAssignee(emp.id)" class="hover:text-red-300">x</button></span>
                                    </template>
                                </div>
                                <input type="text" x-model="taskAssigneeSearch" @input.debounce.300ms="searchTaskAssignees()" placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input">
                                <div class="rounded-2xl bg-[#163067] border border-white/10 max-h-36 overflow-y-auto tis-modal-scroll" x-show="taskAssigneeResults.length">
                                    <template x-for="emp in taskAssigneeResults" :key="`result-new-${emp.id}`">
                                        <button type="button" @click="selectTaskAssignee(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-white/5"><img :src="emp.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover"><div><p class="text-sm font-medium text-white" x-text="emp.full_name"></p><p class="text-[11px] text-white/45" x-text="emp.position || emp.email || ''"></p></div></button>
                                    </template>
                                </div>
                                <div class="flex justify-end gap-2"><button @click="editingTaskAssignees = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="saveTaskAssignees()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button></div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-4"><h3 class="text-base font-semibold">Tarix</h3><button x-show="canEditTask(taskDetail)" @click="editingTaskDates = !editingTaskDates; prepareTaskDates()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button></div>
                            <div x-show="!editingTaskDates" class="space-y-2 text-sm"><div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5"><p class="text-white/45 mb-1 text-xs">Başlama tarixi</p><p x-text="taskDetail?.start_date ? formatDate(taskDetail.start_date) : '-'"></p></div><div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5"><p class="text-white/45 mb-1 text-xs">Son tarix</p><p x-text="taskDetail?.due_date ? formatDate(taskDetail.due_date) : '-'"></p></div></div>
                            <div x-show="editingTaskDates" class="space-y-3"><input type="date" x-model="taskDateForm.start_date" class="w-full h-11 rounded-xl px-4 tis-input"><input type="date" x-model="taskDateForm.due_date" class="w-full h-11 rounded-xl px-4 tis-input"><div class="flex justify-end gap-2"><button @click="editingTaskDates = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="saveTaskDates()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button></div></div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3"><div class="flex items-center justify-between"><h3 class="text-base font-semibold">Alt tapşırıqlar</h3><button x-show="canEditTask(taskDetail)" @click="showInlineSubtaskForm = !showInlineSubtaskForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Alt tapşırıq</button></div>
                        <div x-show="showInlineSubtaskForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3"><input type="text" x-model="newInlineSubtask.title" placeholder="Alt tapşırıq adı" class="w-full h-11 rounded-xl px-4 tis-input"><input type="date" x-model="newInlineSubtask.due_date" class="w-full h-11 rounded-xl px-4 tis-input"><div x-data="employeePicker(spaceId)" x-init="init()"><input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input"><div class="flex flex-wrap gap-2 mt-2"><template x-for="emp in selected" :key="`new-sub-assignee-${emp.id}`"><span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10"><img :src="emp.avatar_url || defaultAvatar" class="w-4 h-4 rounded-full object-cover"><span x-text="emp.full_name"></span><button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button></span></template></div><div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-40 overflow-y-auto tis-modal-scroll"><template x-for="emp in results" :key="`new-sub-result-${emp.id}`"><button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 text-left text-sm"><img :src="emp.avatar_url || defaultAvatar" class="w-7 h-7 rounded-full object-cover"><div><p class="font-medium text-white" x-text="emp.full_name"></p><p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p></div></button></template></div><span x-effect="newInlineSubtask.assignee_ids = selected.map(e => e.id)"></span></div><div class="flex justify-end gap-2"><button @click="showInlineSubtaskForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="createInlineSubtask()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button></div></div>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1 tis-modal-scroll"><template x-for="sub in (taskDetail?.subtasks || [])" :key="`sub-new-${sub.id}`"><div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 space-y-3" x-init="prepareSubtaskEdit(sub)"><div class="flex items-center gap-3"><span class="w-2.5 h-2.5 rounded-full shrink-0" :class="sub.status === 'completed' ? 'bg-[#22d34f]' : 'bg-white/50'"></span><div class="flex-1 min-w-0"><p class="text-sm truncate" x-text="sub.title"></p><p class="text-[11px] text-white/45" x-text="sub.due_date ? formatDate(sub.due_date) : ''"></p></div><div class="flex -space-x-2 shrink-0" x-show="(sub.assignees || []).length"><template x-for="person in (sub.assignees || [])" :key="`sub-new-assignee-${sub.id}-${person.id}`"><img :src="person.avatar_url || defaultAvatar" :title="person.full_name" class="w-7 h-7 rounded-full object-cover ring-2 ring-[#163067]"></template></div><button x-show="canEditSubtask(sub) && sub.status !== 'completed'" @click="completeSubtask(sub)" class="px-3 py-1.5 rounded-lg bg-[#22d34f]/20 text-[#8effa9] border border-[#22d34f]/30 text-xs">Təsdiqlə</button><button x-show="canEditSubtask(sub)" @click="sub.editing = !sub.editing; prepareSubtaskEdit(sub)" class="px-3 py-1.5 rounded-lg bg-white/8 hover:bg-white/12 text-xs">Redaktə et</button></div><div x-show="sub.editing" class="space-y-3 rounded-xl bg-[#10285a] border border-white/10 p-3"><input type="text" x-model="sub.edit.title" class="w-full h-10 rounded-xl px-4 tis-input"><input type="date" x-model="sub.edit.due_date" class="w-full h-10 rounded-xl px-4 tis-input"><div x-data="employeePicker(spaceId)" x-init="init(sub.assignees || [])"><input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-10 rounded-xl px-4 tis-input"><span x-effect="if (sub.edit) sub.edit.assignee_ids = selected.map(e => e.id)"></span></div><div class="flex justify-end gap-2"><button @click="sub.editing = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="saveSubtask(sub)" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button></div></div></div></template><div x-show="!(taskDetail?.subtasks || []).length" class="text-sm text-white/55">Alt tapşırıq yoxdur</div></div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3"><div class="flex items-center justify-between"><h3 class="text-base font-semibold">Yoxlama Siyahısı</h3><button x-show="canEditTask(taskDetail)" @click="showChecklistForm = !showChecklistForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Bənd əlavə et</button></div><div x-show="showChecklistForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3"><input type="text" x-model="newChecklistItem.title" placeholder="Yoxlama siyahısı bəndi" class="w-full h-11 rounded-xl px-4 tis-input"><div class="flex justify-end gap-2"><button @click="showChecklistForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="createChecklistItem()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button></div></div><div class="space-y-2 max-h-48 overflow-y-auto pr-1 tis-modal-scroll"><template x-for="item in (taskDetail?.checklists || [])" :key="`check-new-${item.id}`"><label class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-3 cursor-pointer"><input type="checkbox" class="mt-1 rounded border-white/20 bg-transparent" :checked="!!(item.is_done || item.is_completed)" :disabled="!canToggleChecklistItem(taskDetail)" @change="toggleChecklistItem(item)"><div class="flex-1 min-w-0"><p class="text-sm" :class="(item.is_done || item.is_completed) ? 'line-through text-white/45' : 'text-white'" x-text="item.title"></p></div><button x-show="canEditTask(taskDetail)" type="button" @click.stop="deleteChecklistItem(item)" class="text-xs text-white/45 hover:text-red-300">Sil</button></label></template><div x-show="!(taskDetail?.checklists || []).length" class="text-sm text-white/55">Yoxlama siyahısı yoxdur</div></div></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3"><div class="flex items-center justify-between gap-4"><h3 class="text-base font-semibold">Əlavələr</h3><label class="px-3 py-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-sm cursor-pointer">Fayl yüklə<input type="file" class="hidden" @change="uploadTaskFile($event)"></label></div><div class="space-y-2 max-h-44 overflow-y-auto pr-1 tis-modal-scroll"><template x-for="att in (taskDetail?.attachments || [])" :key="`att-new-${att.id}`"><div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5"><div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[10px] font-semibold" x-text="attachmentExt(att.original_name)"></div><div class="flex-1 min-w-0"><p class="text-sm truncate" x-text="att.original_name"></p><p class="text-[11px] text-white/45" x-text="att.uploader?.full_name || ''"></p></div><a :href="`/api/attachments/${att.id}/download`" class="text-xs text-white/70 hover:text-white">Yüklə</a></div></template><div x-show="!(taskDetail?.attachments || []).length" class="text-sm text-white/55">Fayl yoxdur</div></div></div>
                    <div x-show="canShowApprovePrompt(taskDetail)" x-ref="approvalPanelVisible" class="rounded-2xl border border-[#f3ad1e]/35 bg-[#f3ad1e]/10 p-4 space-y-3"><div><h3 class="text-base font-semibold text-[#ffd37a]">Təsdiq gözləyir</h3><p class="text-sm text-white/65 mt-1">Bu tapşırığı tamamlandı kimi təsdiqləmək yalnız taskı yaradan şəxsə açıqdır.</p></div><button @click="approveTask()" :disabled="approvingTask" class="px-4 py-2.5 rounded-xl bg-[#f3ad1e] hover:bg-[#e9a114] text-[#182d65] text-sm font-semibold disabled:opacity-60"><span x-text="approvingTask ? 'Təsdiqlənir...' : 'Təsdiqlə'"></span></button></div>
                    <div x-show="canCancelTask(taskDetail)" class="pt-2 flex justify-end"><button @click="cancelTask()" :disabled="cancelingTask" class="px-5 py-2.5 rounded-xl bg-[#d9364f] hover:bg-[#c92d45] text-white text-sm font-semibold disabled:opacity-60"><span x-text="cancelingTask ? 'Ləğv edilir...' : 'Ləğv et'"></span></button></div>
                </div>
                <div class="col-span-12 lg:col-span-5 p-4 sm:p-5 border-l border-white/10 bg-[#163067]/80 overflow-y-auto tis-modal-scroll">
                    <div class="rounded-2xl bg-[#132857] border border-white/10 p-4 space-y-4 h-full flex flex-col">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Şərhlər</h3>
                            <button @click="loadTaskComments()" class="text-[11px] text-white/45 hover:text-white">Yenilə</button>
                        </div>
                        <div class="space-y-3 overflow-y-auto flex-1 pr-1 tis-modal-scroll">
                            <template x-if="commentsLoading"><div class="text-sm text-white/45">Şərhlər yüklənir...</div></template>
                            <template x-for="comment in flattenComments(comments)" :key="`comment-new-${comment.id}`">
                                <div class="flex gap-3" :style="`margin-left: ${comment._depth * 18}px`">
                                    <img :src="comment.author?.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover mt-1">
                                    <div class="flex-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-sm font-medium" x-text="comment.author?.full_name || 'İstifadəçi'"></span>
                                            <span class="text-[10px] text-white/45" x-text="comment.created_at ? formatDateTime(comment.created_at) : ''"></span>
                                        </div>
                                        <p class="text-sm text-white/75 whitespace-pre-wrap" x-text="comment.body || comment.content || ''"></p>
                                        <div class="mt-2 flex items-center gap-3">
                                            <button type="button" @click="startReply(comment)" class="text-[11px] text-white/45 hover:text-white">Cavabla</button>
                                            <span x-show="comment._depth >= 3 && (comment.replies || []).length" class="text-[11px] text-white/35">Daha çox cavab var</span>
                                        </div>
                                        <div x-show="replyingTo?.id === comment.id" class="mt-3 space-y-2">
                                            <textarea x-model="replyText" rows="2" class="w-full rounded-xl px-3 py-2 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none resize-none" placeholder="Cavab yazın"></textarea>
                                            <div class="flex justify-end gap-2">
                                                <button @click="cancelReply()" class="px-3 py-1.5 rounded-lg bg-white/8 hover:bg-white/12 text-xs">Ləğv</button>
                                                <button @click="submitReply(comment)" :disabled="!replyText.trim()" class="px-3 py-1.5 rounded-lg bg-[#6d44c5] hover:bg-[#613db1] text-xs disabled:opacity-50">Göndər</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!commentsLoading && comments.length === 0" class="text-sm text-white/45">Hələ şərh yoxdur</div>
                        </div>
                        <div class="pt-2 space-y-3">
                            <textarea x-model="quickComment" rows="3" placeholder="Şərh yazın" class="w-full rounded-2xl px-4 py-3 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none resize-none"></textarea>
                            <div class="flex justify-end"><button @click="submitTaskComment()" :disabled="!quickComment.trim()" class="px-4 py-2.5 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50">Göndər</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="false && taskModalOpen" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="tis-modal-card text-white rounded-[26px] shadow-2xl w-full max-w-6xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-7 py-5 border-b border-white/10 flex items-center justify-between">
                <div class="min-w-0">
                    <h2 class="text-[18px] font-semibold truncate" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                    <p class="text-xs text-white/55 mt-1" x-text="taskDetail?.space?.name || @js($space->name)"></p>
                </div>
                <button @click="closeTaskModal()" class="text-white/60 hover:text-white text-xl">×</button>
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
                                    <input type="checkbox" class="rounded accent-emerald-500" :checked="!!(item.is_done || item.is_completed)" :disabled="!canToggleChecklistItem(taskDetail)" @change="toggleChecklistItem(item)">
                                    <span :class="item.is_done ? 'line-through text-white/45' : ''" x-text="item.title"></span>
                                </label>
                            </template>

                            <div x-show="(taskDetail?.subtasks || []).length === 0 && (taskDetail?.checklists || []).length === 0" class="text-sm text-white/45">Alt tapşırıq yoxdur</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Məsul şəxslər</h3>
                            <button x-show="canEditTask(taskDetail)" @click="openTaskAssigneeEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                        </div>
                        <div x-show="editingTaskAssignees" class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="emp in selectedTaskAssignees" :key="`selected-${emp.id}`">
                                    <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                        <img :src="emp.avatar_url || defaultAvatar" class="w-4 h-4 rounded-full object-cover">
                                        <span x-text="emp.full_name"></span>
                                        <button type="button" @click="removeTaskAssignee(emp.id)" class="hover:text-red-300">x</button>
                                    </span>
                                </template>
                            </div>
                            <input type="text" x-model="taskAssigneeSearch" @input.debounce.300ms="searchTaskAssignees()" placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input">
                            <div class="rounded-2xl bg-[#163067] border border-white/10 max-h-36 overflow-y-auto tis-modal-scroll" x-show="taskAssigneeResults.length">
                                <template x-for="emp in taskAssigneeResults" :key="`result-${emp.id}`">
                                    <button type="button" @click="selectTaskAssignee(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-white/5">
                                        <img :src="emp.avatar_url || defaultAvatar" class="w-8 h-8 rounded-full object-cover">
                                        <div>
                                            <p class="text-sm font-medium text-white" x-text="emp.full_name"></p>
                                            <p class="text-[11px] text-white/45" x-text="emp.position || emp.email || ''"></p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="editingTaskAssignees = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                <button @click="saveTaskAssignees()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Tarix</h3>
                            <button x-show="canEditTask(taskDetail)" @click="editingTaskDates = !editingTaskDates; prepareTaskDates()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                        </div>
                        <div x-show="editingTaskDates" class="space-y-3">
                            <input type="date" x-model="taskDateForm.start_date" class="w-full h-11 rounded-xl px-4 tis-input">
                            <input type="date" x-model="taskDateForm.due_date" class="w-full h-11 rounded-xl px-4 tis-input">
                            <div class="flex justify-end gap-2">
                                <button @click="editingTaskDates = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                <button @click="saveTaskDates()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold">Alt tapşırıq əlavə et</h3>
                            <button x-show="canEditTask(taskDetail)" @click="showInlineSubtaskForm = !showInlineSubtaskForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Alt tapşırıq</button>
                        </div>
                        <div x-show="showInlineSubtaskForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3">
                            <input type="text" x-model="newInlineSubtask.title" placeholder="Alt tapşırıq adı" class="w-full h-11 rounded-xl px-4 tis-input">
                            <input type="date" x-model="newInlineSubtask.due_date" class="w-full h-11 rounded-xl px-4 tis-input">
                            <div x-data="employeePicker(spaceId)" x-init="init()">
                                <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input">
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <template x-for="emp in selected" :key="`new-sub-assignee-${emp.id}`">
                                        <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                            <img :src="emp.avatar_url || defaultAvatar" class="w-4 h-4 rounded-full object-cover">
                                            <span x-text="emp.full_name"></span>
                                            <button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button>
                                        </span>
                                    </template>
                                </div>
                                <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-40 overflow-y-auto tis-modal-scroll">
                                    <template x-for="emp in results" :key="`new-sub-result-${emp.id}`">
                                        <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 text-left text-sm">
                                            <img :src="emp.avatar_url || defaultAvatar" class="w-7 h-7 rounded-full object-cover">
                                            <div><p class="font-medium text-white" x-text="emp.full_name"></p><p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p></div>
                                        </button>
                                    </template>
                                </div>
                                <span x-effect="newInlineSubtask.assignee_ids = selected.map(e => e.id)"></span>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showInlineSubtaskForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                <button @click="createInlineSubtask()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button>
                            </div>
                        </div>
                        <template x-for="sub in (taskDetail?.subtasks || [])" :key="`sub-edit-${sub.id}`">
                            <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 space-y-3" x-init="prepareSubtaskEdit(sub)">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="sub.status === 'completed' ? 'bg-[#22d34f]' : 'bg-white/50'"></span>
                                    <div class="flex-1 min-w-0"><p class="text-sm truncate" x-text="sub.title"></p><p class="text-[11px] text-white/45" x-text="sub.due_date ? formatDate(sub.due_date) : ''"></p></div>
                                    <button x-show="canEditSubtask(sub) && sub.status !== 'completed'" @click="completeSubtask(sub)" class="px-3 py-1.5 rounded-lg bg-[#22d34f]/20 text-[#8effa9] border border-[#22d34f]/30 text-xs">Təsdiqlə</button>
                                    <button x-show="canEditSubtask(sub)" @click="sub.editing = !sub.editing; prepareSubtaskEdit(sub)" class="px-3 py-1.5 rounded-lg bg-white/8 hover:bg-white/12 text-xs">Redaktə et</button>
                                </div>
                                <div x-show="sub.editing" class="space-y-3 rounded-xl bg-[#10285a] border border-white/10 p-3">
                                    <input type="text" x-model="sub.edit.title" class="w-full h-10 rounded-xl px-4 tis-input">
                                    <input type="date" x-model="sub.edit.due_date" class="w-full h-10 rounded-xl px-4 tis-input">
                                    <div x-data="employeePicker(spaceId)" x-init="init(sub.assignees || [])">
                                        <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-10 rounded-xl px-4 tis-input">
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <template x-for="emp in selected" :key="`edit-sub-assignee-${sub.id}-${emp.id}`">
                                                <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                                    <img :src="emp.avatar_url || defaultAvatar" class="w-4 h-4 rounded-full object-cover">
                                                    <span x-text="emp.full_name"></span>
                                                    <button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button>
                                                </span>
                                            </template>
                                        </div>
                                        <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-40 overflow-y-auto tis-modal-scroll">
                                            <template x-for="emp in results" :key="`edit-sub-result-${sub.id}-${emp.id}`">
                                                <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 text-left text-sm">
                                                    <img :src="emp.avatar_url || defaultAvatar" class="w-7 h-7 rounded-full object-cover">
                                                    <div><p class="font-medium text-white" x-text="emp.full_name"></p><p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p></div>
                                                </button>
                                            </template>
                                        </div>
                                        <span x-effect="if (sub.edit) sub.edit.assignee_ids = selected.map(e => e.id)"></span>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button @click="sub.editing = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                        <button @click="saveSubtask(sub)" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold">Yoxlama Siyahısı</h3>
                            <button x-show="canEditTask(taskDetail)" @click="showChecklistForm = !showChecklistForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Bənd əlavə et</button>
                        </div>
                        <div x-show="showChecklistForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3">
                            <input type="text" x-model="newChecklistItem.title" placeholder="Yoxlama siyahısı bəndi" class="w-full h-11 rounded-xl px-4 tis-input">
                            <div class="flex justify-end gap-2"><button @click="showChecklistForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button><button @click="createChecklistItem()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button></div>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1 tis-modal-scroll">
                            <template x-for="item in (taskDetail?.checklists || [])" :key="`check-edit-${item.id}`">
                                <label class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-3 cursor-pointer">
                                    <input type="checkbox" class="mt-1 rounded border-white/20 bg-transparent" :checked="!!(item.is_done || item.is_completed)" :disabled="!canToggleChecklistItem(taskDetail)" @change="toggleChecklistItem(item)">
                                    <div class="flex-1 min-w-0"><p class="text-sm" :class="(item.is_done || item.is_completed) ? 'line-through text-white/45' : 'text-white'" x-text="item.title"></p></div>
                                    <button x-show="canEditTask(taskDetail)" type="button" @click.stop="deleteChecklistItem(item)" class="text-xs text-white/45 hover:text-red-300">Sil</button>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Əlavələr</h3>
                            <label class="px-3 py-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-sm cursor-pointer">Fayl yüklə<input type="file" class="hidden" @change="uploadTaskFile($event)"></label>
                        </div>
                        <template x-for="att in (taskDetail?.attachments || [])" :key="`att-${att.id}`">
                            <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[10px] font-semibold" x-text="attachmentExt(att.original_name)"></div>
                                <div class="flex-1 min-w-0"><p class="text-sm truncate" x-text="att.original_name"></p><p class="text-[11px] text-white/45" x-text="att.uploader?.full_name || ''"></p></div>
                                <a :href="`/api/attachments/${att.id}/download`" class="text-xs text-white/70 hover:text-white">Yüklə</a>
                            </div>
                        </template>
                    </div>

                    <div x-show="canShowApprovePrompt(taskDetail)" x-ref="approvalPanel" class="rounded-2xl border border-[#f3ad1e]/35 bg-[#f3ad1e]/10 p-4 space-y-3">
                        <h3 class="text-base font-semibold text-[#ffd37a]">Təsdiq gözləyir</h3>
                        <button @click="approveTask()" :disabled="approvingTask" class="px-4 py-2.5 rounded-xl bg-[#f3ad1e] hover:bg-[#e9a114] text-[#182d65] text-sm font-semibold disabled:opacity-60"><span x-text="approvingTask ? 'Təsdiqlənir...' : 'Təsdiqlə'"></span></button>
                    </div>

                    <div x-show="canCancelTask(taskDetail)" class="pt-2 flex justify-end">
                        <button @click="cancelTask()" :disabled="cancelingTask" class="px-5 py-2.5 rounded-xl bg-[#d9364f] hover:bg-[#c92d45] text-white text-sm font-semibold disabled:opacity-60"><span x-text="cancelingTask ? 'Ləğv edilir...' : 'Ləğv et'"></span></button>
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
                                <textarea x-model="quickComment" rows="3" placeholder="Şərh yazın..." class="w-full rounded-2xl px-4 py-3 tis-input resize-none"></textarea>
                                <div class="flex justify-end mt-2">
                                    <button @click="submitTaskComment()" :disabled="!quickComment.trim()" class="px-4 py-2.5 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50">Göndər</button>
                                </div>
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
        quickComment: '',
        replyingTo: null,
        replyText: '',
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
            this.filters.onlyMe = localStorage.getItem(`board:${this.boardId}:onlyMe`) === '1';
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
            this.showCreateModal = true;
        },

        async createTask() {
            if (!this.newTask.title?.trim()) return;
            this.creating = true;
            try {
                await api('POST', `/boards/${this.boardId}/tasks`, this.newTask);
                this.showCreateModal = false;
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
                if (this.spaceId) url += `&space_id=${this.spaceId}`;
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
                output.push({ ...comment, _depth: Math.min(depth, 3) });
                this.flattenComments(comment.replies || [], depth + 1, output);
            });
            return output;
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
                const currentSpaceId = spaceId || this.spaceId;
                if (currentSpaceId) url += `&space_id=${currentSpaceId}`;
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
</script>
@endpush
