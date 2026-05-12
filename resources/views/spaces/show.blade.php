@extends('layouts.app')
@section('title', $space->name)
@section('page-title', $space->name)

@section('content')
<div x-data="spaceHub({{ $space->id }})" x-init="init()" class="px-3 sm:px-4 lg:px-6 pt-4 sm:pt-5 space-y-5 text-white">
    @php($spaceMembers = $space->members->sortByDesc(fn($member) => (($member->pivot->is_manager ?? false) || $space->manager_employee_id === $member->id))->values())
    <section class="rounded-[28px] overflow-hidden shadow-tis border border-white/10 bg-[#1d346f]">
        <div class="bg-gradient-to-r from-[#17316f] to-[#1c2354] px-5 sm:px-7 py-4 sm:py-5 flex items-center justify-between gap-4">
            <div class="min-w-0 flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="w-11 h-11 shrink-0 rounded-full border border-white/20 bg-white/5 flex items-center justify-center hover:bg-white/10 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-[42px] font-light tracking-tight truncate">{{ $space->name }}</h1>
                    @if($space->department)
                    <p class="text-sm sm:text-base text-white/55 truncate mt-1">{{ $space->department->name }}</p>
                    @endif
                </div>
            </div>
            <div class="hidden md:block shrink-0">
                <details class="relative group">
                    <summary class="list-none cursor-pointer px-4 py-2 rounded-2xl bg-white/8 border border-white/10 hover:bg-white/12 transition-all flex items-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Üzvlər
                        <span class="text-white/60">({{ $spaceMembers->count() }})</span>
                    </summary>
                    <div class="absolute right-0 mt-3 w-[320px] rounded-[24px] border border-white/10 bg-[#142a5b]/95 backdrop-blur-xl shadow-2xl p-4 z-30">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-semibold text-white">Space üzvləri</h3>
                            <span class="text-xs text-white/55">{{ $spaceMembers->count() }} nəfər</span>
                        </div>
                        <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                            @forelse($spaceMembers as $member)
                                <div class="flex items-center gap-3 rounded-2xl bg-white/5 border border-white/8 px-3 py-3">
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->full_name }}" class="w-11 h-11 rounded-full object-cover ring-2 ring-white/10">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-white truncate">{{ $member->full_name }}</p>
                                        <p class="text-xs text-white/55 truncate">{{ $member->email }}</p>
                                    </div>
                                    @if(($member->pivot->is_manager ?? false) || $space->manager_employee_id === $member->id)
                                        <span class="px-2.5 py-1 rounded-full bg-[#6d44c5] text-white text-[11px] font-medium">Rəhbər</span>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-2xl bg-white/5 border border-white/8 px-3 py-4 text-sm text-white/60">Üzv tapılmadı</div>
                            @endforelse
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="grid grid-cols-12 min-h-[calc(100vh-180px)]">
            <aside class="col-span-12 lg:col-span-3 xl:col-span-3 bg-gradient-to-b from-[#35518d] to-[#2b457d] px-5 sm:px-6 py-6 border-r border-white/8">
                <div class="space-y-8">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-[18px] sm:text-[20px] font-medium">Tapşırıqlarım</h2>
                            <button @click="openCreateTask()" class="w-11 h-11 rounded-2xl bg-[#0d254f] border border-white/10 flex items-center justify-center text-2xl leading-none hover:bg-[#113063] transition-all" title="Yeni tapşırıq">+</button>
                        </div>

                        <div class="space-y-3 max-h-[420px] ">
                            <template x-if="myTasksLoading">
                                <div class="rounded-2xl border border-white/10 bg-[#1a2d63]/60 px-4 py-4 text-sm text-white/60">Tapşırıqlar yüklənir...</div>
                            </template>

                            <template x-if="!myTasksLoading && myTasks.length === 0">
                                <div class="rounded-2xl border border-white/10 bg-[#1a2d63]/60 px-4 py-4 text-sm text-white/60">Tapşırıq yoxdur</div>
                            </template>

                            <template x-for="t in myTasks" :key="t.id">
                                <button type="button" class="w-full text-left rounded-[20px] px-4 py-3 border border-white/8 transition-all shadow-tis-soft"
                                        :class="t.status === 'canceled' ? 'bg-[#cc4a5a] hover:bg-[#d55564]' : 'bg-[#152a60] hover:bg-[#1a316d]'"
                                        draggable="true"
                                        @dragstart="onDragTaskStart(t)"
                                        @click="openTaskModal(t.id)">
                                    <div class="flex items-start gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-[17px] truncate" x-text="t.title"></p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <div class="h-2.5 w-[110px] rounded-full bg-[#0d214d] overflow-hidden">
                                                    <div class="h-2.5 rounded-full" :class="t.status === 'completed' ? 'bg-[#22d34f]' : t.status === 'canceled' ? 'bg-[#22d34f]' : 'bg-[#cb8346]'" :style="`width:${t.progress ?? 30}%`"></div>
                                                </div>
                                                <span class="text-xs text-white/65" x-text="`${t.progress ?? 30}%`"></span>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right text-xs text-white/65">
                                            <p x-text="t.due_date ? formatDate(t.due_date) : ''"></p>
                                            <img :src="(t.assignees && t.assignees[0]?.avatar_url) || '{{ auth()->user()->avatar_url }}'" class="w-11 h-11 rounded-full object-cover mt-2 ring-2 ring-white/10">
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                </div>
            </aside>

            <section class="col-span-12 lg:col-span-9 xl:col-span-9 bg-[#c6c0d2] text-[#16244f] px-4 sm:px-6 py-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 justify-end">
                            <button x-show="canCreateBoard" @click="openCreateBoard()" class="h-11 px-5 rounded-xl bg-[#20356d] text-white text-sm font-medium shadow-lg hover:bg-[#182b5d] transition-all">Layihə əlavə edin</button>
                            <button class="h-11 px-5 rounded-xl bg-[#6d44c5] text-white text-sm font-medium shadow-lg hover:bg-[#613db1] transition-all flex items-center gap-2">
                                Export
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16.8V19a1 1 0 001 1h14a1 1 0 001-1v-2.2"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <template x-if="boardsLoading">
                            <div class="xl:col-span-4 rounded-[24px] bg-gradient-to-br from-[#3059a5] to-[#207d92] p-6 text-white shadow-tis">Boardlar yüklənir...</div>
                        </template>

                        <template x-for="b in boards" :key="b.id">
                            <a :href="`/spaces/${spaceId}/boards/${b.id}`" class="block rounded-[24px] p-6 bg-gradient-to-br from-[#2f67ad] to-[#1f8995] text-white shadow-tis border border-white/10 transition-all hover:-translate-y-1"
                               @dragover.prevent
                               @drop="onDropToBoard(b.id)">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-[19px] leading-tight font-medium truncate" x-text="b.name" :title="b.name"></h3>
                                        <p class="text-white/70 text-sm mt-1" x-text="b.description || ($space?->description ?? 'Departamentin işləri ilə bağlı tapşırıqlar və layihələr')"></p>
                                    </div>
                                    <span class="text-sm text-white/80" x-text="b.due_date ? formatDate(b.due_date) : '29/04'"></span>
                                </div>

                                <div class="mt-6">
                                    <div class="h-3 rounded-full bg-[#1a4a69]/80 overflow-hidden">
                                        <div class="h-3 rounded-full bg-[#56c65b]" :style="`width:${progressPercent(b)}%`"></div>
                                    </div>
                                    <div class="mt-2 text-sm font-medium" x-text="`${progressPercent(b)}%`"></div>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-y-3 text-sm text-white/78">
                                    <div class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-white/20 inline-flex items-center justify-center text-[11px]" x-text="boardStatusCount(b, 'todo')"></span> Görüləcək</div>
                                    <div class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-[#e8aa39] inline-flex items-center justify-center text-[11px] text-[#24335d]" x-text="boardStatusCount(b, 'in_progress')"></span> İcra olunur</div>
                                    <div class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-[#8e64ff] inline-flex items-center justify-center text-[11px]" x-text="boardStatusCount(b, 'waiting_for_approve')"></span> Təsdiq gözləyir</div>
                                    <div class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-[#22d34f] inline-flex items-center justify-center text-[11px] text-[#143b28]" x-text="boardStatusCount(b, 'completed')"></span> Tamamlandı</div>
                                </div>

                                <div class="mt-6 flex items-end justify-between gap-3">
                                    <button type="button" @click.prevent="openCreateTask()" class="w-11 h-11 rounded-2xl bg-[#10325f] border border-white/10 flex items-center justify-center text-2xl hover:bg-[#0d274d] transition-all">+</button>
                                    <div class="flex -space-x-3" x-show="boardAssignees(b).length">
                                  <template x-for="member in boardAssignees(b)" :key="`board-member-${b.id}-${member.id}`">
                                        <img
                                            :src="member.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name || 'User')}&background=2f67ad&color=fff`"
                                            :alt="member.name"
                                            :title="member.name"
                                            class="w-11 h-11 rounded-full object-cover ring-2 ring-[#2f67ad]"
                                        >
                                    </template>
                                    </div>
                                    <div x-show="!boardAssignees(b).length" class="text-xs text-white/65">Məsul şəxs yoxdur</div>
                                </div>
                            </a>
                        </template>

                        <template x-if="!boardsLoading && boards.length === 0">
                            <div class="rounded-[24px] min-h-[250px] bg-white/20 border border-white/30 flex flex-col items-center justify-center text-[#5d6486] shadow-inner">
                                <div class="text-6xl leading-none">+</div>
                                <p class="mt-3 text-xl font-medium">Layihə əlavə edin</p>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-5 pt-2">
                        <template x-for="s in statusSections" :key="s.key">
                            <section class="rounded-[24px] overflow-hidden shadow-tis border border-white/10 bg-gradient-to-b from-[#3a56a1] to-[#314b8c] text-white">
                                <div class="px-6 pt-5 pb-2 flex items-center justify-between">
                                    <div class="flex items-center gap-3 text-[21px] sm:text-[24px] font-medium" :class="{
                                        'text-[#f3ad1e]': s.key === 'in_progress',
                                        'text-[#b178ff]': s.key === 'waiting_for_approve',
                                        'text-[#1dd84c]': s.key === 'completed',
                                        'text-[#dfe3ec]': s.key === 'todo',
                                        'text-[#ff5757]': s.key === 'canceled'
                                    }">
                                        <span class="w-4 h-4 rounded-full" :class="{
                                            'bg-[#f3ad1e]': s.key === 'in_progress',
                                            'bg-[#b178ff]': s.key === 'waiting_for_approve',
                                            'bg-[#1dd84c]': s.key === 'completed',
                                            'bg-[#dfe3ec]': s.key === 'todo',
                                            'bg-[#ff3030]': s.key === 'canceled'
                                        }"></span>
                                        <span x-text="s.label"></span>
                                    </div>
                                    <span class="text-sm text-white/70" x-text="`${(spaceGrouped[s.key] || []).length} tapşırıq`"></span>
                                </div>

                                <div class="px-6 pb-6 overflow-x-auto">
                                    <div class="min-w-[860px]">
                                        <div class="grid grid-cols-[2.2fr_1.6fr_1.1fr_1.2fr_1fr_0.8fr_1fr] gap-4 text-white/45 text-sm px-2 py-2 border-b border-white/12">
                                            <div>Ad</div>
                                            <div>Layihə</div>
                                            <div>Məsul şəxslər</div>
                                            <div>Yaradan</div>
                                            <div>Son tarix</div>
                                            <div>Prioritet</div>
                                            <div>İrəliləyiş</div>
                                        </div>

                                        <template x-if="(spaceGrouped[s.key] || []).length === 0">
                                            <div class="px-2 py-5 text-sm text-white/55">Tapşırıq yoxdur</div>
                                        </template>

                                        <template x-for="t in (spaceGrouped[s.key] || [])" :key="t.id">
                                            <button type="button" @click="openTaskModal(t.id)" class="w-full text-left grid grid-cols-[2.2fr_1.6fr_1.1fr_1.2fr_1fr_0.8fr_1fr] gap-4 px-2 py-3 border-b border-white/10 hover:bg-white/5 transition-all items-center">
                                                <div class="min-w-0 flex items-center gap-3">
                                                    <span class="w-3 h-3 rounded-full shrink-0" :class="{
                                                        'bg-[#f3ad1e]': s.key === 'in_progress',
                                                        'bg-[#b178ff]': s.key === 'waiting_for_approve',
                                                        'bg-[#1dd84c]': s.key === 'completed',
                                                        'bg-[#dfe3ec]': s.key === 'todo',
                                                        'bg-[#ff3030]': s.key === 'canceled'
                                                    }"></span>
                                                    <div class="min-w-0">
                                                        <span class="truncate block" x-text="t.title"></span>
                                                        <span class="text-xs text-white/45" x-show="(t.subtasks || []).length" x-text="`${(t.subtasks || []).length} alt tapşırıq`"></span>
                                                    </div>
                                                </div>
                                                <div class="truncate text-white/85" x-text="boards.find(b => b.id === t.board_id)?.name || '...' "></div>
                                                <div class="flex items-center gap-2 truncate">
                                                    <template x-if="(t.assignees || []).length === 1">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <img :src="t.assignees[0]?.avatar_url || '{{ auth()->user()->avatar_url }}'" class="w-8 h-8 rounded-full object-cover ring-2 ring-white/10">
                                                            <span class="truncate" x-text="t.assignees[0]?.full_name || '—'"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="(t.assignees || []).length > 1">
                                                        <div class="flex -space-x-2">
                                                            <template x-for="person in (t.assignees || [])" :key="`task-assignee-${t.id}-${person.id}`">
                                                                <img :src="person.avatar_url" :title="person.full_name" class="w-8 h-8 rounded-full object-cover ring-2 ring-white/10">
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="(t.assignees || []).length === 0">
                                                        <span class="truncate">—</span>
                                                    </template>
                                                </div>
                                                <div class="flex items-center gap-2 truncate">
                                                    <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-white/10">
                                                    <span class="truncate">{{ auth()->user()->full_name }}</span>
                                                </div>
                                                <div class="text-white/85" x-text="t.due_date ? formatDate(t.due_date) : '—'"></div>
                                                <div class="text-white/85" x-text="priorityLabel(t.priority) || 'Normal'"></div>
                                                <div class="flex items-center gap-3">
                                                    <div class="h-2.5 w-full rounded-full bg-[#17305f] overflow-hidden">
                                                        <div class="h-2.5 rounded-full" :class="taskProgress(t) === 100 ? 'bg-[#22d34f]' : 'bg-[#c79a40]'" :style="`width:${taskProgress(t)}%`"></div>
                                                    </div>
                                                    <span class="text-white/80 text-sm" x-text="`${taskProgress(t)}%`"></span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </section>
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <div x-show="showCreateBoardModal" x-transition.opacity class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="w-full max-w-md rounded-[28px] bg-gradient-to-b from-[#233d82] to-[#182b5d] border border-white/10 shadow-tis text-white overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-semibold text-lg">Yeni Board</h2>
                <button @click="showCreateBoardModal=false" class="text-white/60 hover:text-white">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <input x-model="newBoard.name" placeholder="Board adı..." class="w-full h-12 rounded-xl px-4 tis-input">
                <textarea x-model="newBoard.description" rows="3" placeholder="Təsvir (opsional)..." class="w-full rounded-xl px-4 py-3 tis-input resize-none"></textarea>
                <p x-show="boardError" x-text="boardError" class="text-sm text-red-200 bg-red-500/15 rounded-xl px-3 py-2"></p>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showCreateBoardModal=false" class="px-4 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                <button @click="createBoard()" :disabled="savingBoard || !newBoard.name.trim()" class="px-4 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50"> <span x-text="savingBoard ? 'Yaradılır...' : 'Yarat'"></span></button>
            </div>
        </div>
    </div>

    <div x-show="showCreateModal" x-transition.opacity class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[30px] bg-gradient-to-b from-[#233d82] to-[#182b5d] border border-white/10 shadow-tis text-white">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-semibold text-lg">Yeni Tapşırıq</h2>
                <button @click="showCreateModal = false" class="text-white/60 hover:text-white">✕</button>
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
                                <img :src="emp.avatar_url" class="w-5 h-5 rounded-full object-cover">
                                <span x-text="emp.full_name"></span>
                                <button type="button" @click="remove(emp.id)" class="hover:text-red-300">✕</button>
                            </span>
                        </template>
                    </div>
                    <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-48 overflow-y-auto">
                        <template x-for="emp in results" :key="emp.id">
                            <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 text-left text-sm">
                                <img :src="emp.avatar_url" class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-white" x-text="emp.full_name"></p>
                                    <p class="text-xs text-white/45" x-text="emp.position"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                    <span x-effect="newTask.assignee_ids = selected.map(e => e.id)"></span>
                </div>
                <div x-data="employeePicker(null)" x-init="init([], true)">
                    <label class="block text-sm font-medium text-white/80 mb-1">Kim tərəfindən (istəyə bağlı)</label>

                    <div class="flex gap-2">
                        <input
                            type="text"
                            x-model="search"
                            @input.debounce.300ms="searchEmployees()"
                            @focus="if (!results.length) loadAllEmployees()"
                            placeholder="İşçini axtar..."
                            class="w-full h-12 rounded-xl px-4 tis-input"
                        >

                        <button
                            type="button"
                            @click="loadAllEmployees()"
                            class="px-4 rounded-xl bg-white/10 border border-white/10 hover:bg-white/15 text-sm"
                        >
                            Bax
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3" x-show="selected.length">
                        <template x-for="emp in selected" :key="emp.id">
                            <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-2 rounded-full border border-white/10">
                                <img :src="emp.avatar_url" class="w-5 h-5 rounded-full object-cover">
                                <span x-text="emp.full_name"></span>
                                <button
                                    type="button"
                                    @click="
                                        remove(emp.id);
                                        newTask.assigned_by_id = null;
                                    "
                                    class="hover:text-red-300"
                                >✕</button>
                            </span>
                        </template>
                    </div>

                    <div
                        x-show="open"
                        @click.outside="open = false"
                        class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-48 overflow-y-auto"
                    >
                        <template x-if="!results.length">
                            <div class="px-4 py-3 text-sm text-white/60">Nəticə tapılmadı</div>
                        </template>

                        <template x-for="emp in results" :key="emp.id">
                            <button
                                type="button"
                                @click="
                                    select(emp);
                                    newTask.assigned_by_id = emp.id;
                                "
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 text-left text-sm"
                            >
                                <img :src="emp.avatar_url" class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-white" x-text="emp.full_name"></p>
                                    <p class="text-xs text-white/45" x-text="emp.position"></p>
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
    <div @click.stop
         x-transition.scale
         class="w-full max-w-5xl h-[88vh] overflow-hidden rounded-[24px] bg-gradient-to-b from-[#1f397e] to-[#182d65] border border-white/10 shadow-tis text-white">

        <div class="px-5 py-4 flex items-center justify-between border-b border-white/10">
           <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-[18px] sm:text-[22px] font-semibold truncate" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                    <template x-if="taskDetail?.assigner?.full_name">
                        <span class="text-sm text-white/65">
                            - <span x-text="taskDetail.assigner.full_name"></span> tərəfindən
                        </span>
                    </template>
                </div>
                <p class="text-xs sm:text-sm text-white/55 mt-1" x-text="taskDetail?.space?.name || ''"></p>
            </div>
            <button @click="closeTaskModal()" class="text-white/60 hover:text-white text-lg">✕</button>
        </div>

        <div class="grid grid-cols-12 h-[calc(88vh-72px)] overflow-hidden">
            <div class="col-span-12 lg:col-span-7 p-4 sm:p-5 space-y-4 overflow-y-auto">

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                        <p class="text-white/45 mb-1 text-xs">Status</p>
                        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/8 border border-white/10">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#f3ad1e]"></span>
                            <span x-text="statusLabel(taskDetail?.status)"></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                        <p class="text-white/45 mb-1 text-xs">Prioritet</p>
                        <p class="text-white font-medium" x-text="priorityLabel(taskDetail?.priority) || 'Normal'"></p>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-base font-semibold">Təsvir</h3>
                        <div class="flex items-center gap-3 min-w-[110px]">
                            <div class="h-2.5 flex-1 rounded-full bg-[#0d214d] overflow-hidden">
                                <div class="h-2.5 rounded-full bg-[#c5a13c]" :style="`width:${taskProgress(taskDetail)}%`"></div>
                            </div>
                            <span class="text-xs text-white/70" x-text="`${taskProgress(taskDetail) || 0}%`"></span>
                        </div>
                    </div>
                    <p class="text-sm text-white/72 leading-6 whitespace-pre-wrap"
                       x-text="taskDetail?.description || 'Departamentin işləri ilə bağlı tapşırıq təsviri əlavə edilməyib.'"></p>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3" x-show="taskDetail">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Məsul şəxslər</h3>
                            <button x-show="canEditTask(taskDetail)" @click="openTaskAssigneeEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                        </div>

                        <div class="space-y-2" x-show="!editingTaskAssignees">
                            <template x-if="(taskDetail?.assignees || []).length === 0">
                                <div class="text-sm text-white/55">Məsul şəxs seçilməyib</div>
                            </template>

                            <template x-for="person in (taskDetail?.assignees || [])" :key="`detail-assignee-${person.id}`">
                                <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                    <img :src="person.avatar_url" class="w-9 h-9 rounded-full object-cover">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate" x-text="person.full_name"></p>
                                        <p class="text-[11px] text-white/50 truncate" x-text="person.position || person.email || ''"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="editingTaskAssignees" class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="emp in selectedTaskAssignees" :key="`selected-${emp.id}`">
                                    <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                        <img :src="emp.avatar_url" class="w-4 h-4 rounded-full object-cover">
                                        <span x-text="emp.full_name"></span>
                                        <button type="button" @click="removeTaskAssignee(emp.id)" class="hover:text-red-300">✕</button>
                                    </span>
                                </template>
                            </div>

                            <input type="text" x-model="taskAssigneeSearch" @input.debounce.300ms="searchTaskAssignees()"
                                   placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input">

                            <div class="rounded-2xl bg-[#163067] border border-white/10 max-h-36 overflow-y-auto" x-show="taskAssigneeResults.length">
                                <template x-for="emp in taskAssigneeResults" :key="`result-${emp.id}`">
                                    <button type="button" @click="selectTaskAssignee(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-white/5">
                                        <img :src="emp.avatar_url" class="w-8 h-8 rounded-full object-cover">
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

                        <div x-show="!editingTaskDates" class="space-y-2 text-sm">
                            <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                <p class="text-white/45 mb-1 text-xs">Başlama tarixi</p>
                                <p x-text="taskDetail?.start_date ? formatDate(taskDetail.start_date) : '—'"></p>
                            </div>
                            <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                <p class="text-white/45 mb-1 text-xs">Son tarix</p>
                                <p x-text="taskDetail?.due_date ? formatDate(taskDetail.due_date) : '—'"></p>
                            </div>
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
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold">Alt tapşırıqlar</h3>
                        <button x-show="canEditTask(taskDetail)" @click="showInlineSubtaskForm = !showInlineSubtaskForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Alt tapşırıq</button>
                    </div>

                    <div x-show="showInlineSubtaskForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3">
                        <input type="text" x-model="newInlineSubtask.title" placeholder="Alt tapşırıq adı" class="w-full h-11 rounded-xl px-4 tis-input">
                        <input type="date" x-model="newInlineSubtask.due_date" class="w-full h-11 rounded-xl px-4 tis-input">
                        <div class="flex justify-end gap-2">
                            <button @click="showInlineSubtaskForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                            <button @click="createInlineSubtask()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button>
                        </div>
                    </div>

                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <template x-for="sub in (taskDetail?.subtasks || [])" :key="`sub-${sub.id}`">
                            <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                <span class="w-2.5 h-2.5 rounded-full" :class="sub.status === 'completed' ? 'bg-[#22d34f]' : 'bg-white/50'"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm truncate" x-text="sub.title"></p>
                                    <p class="text-[11px] text-white/45" x-text="sub.due_date ? formatDate(sub.due_date) : ''"></p>
                                </div>
                            </div>
                        </template>
                        <div x-show="!(taskDetail?.subtasks || []).length" class="text-sm text-white/55">Alt tapşırıq yoxdur</div>
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold">Yoxlama Siyahısı</h3>
        <button
            x-show="canEditTask(taskDetail)"
            @click="showChecklistForm = !showChecklistForm"
            class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10"
        >
            + Bənd əlavə et
        </button>
    </div>

    <div x-show="showChecklistForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3">
        <input
            type="text"
            x-model="newChecklistItem.title"
            placeholder="Yoxlama siyahısı bəndi"
            class="w-full h-11 rounded-xl px-4 tis-input"
        >
        <div class="flex justify-end gap-2">
            <button
                @click="showChecklistForm = false"
                class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm"
            >
                Ləğv
            </button>
            <button
                @click="createChecklistItem()"
                class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm"
            >
                Əlavə et
            </button>
        </div>
    </div>

    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
        <template x-for="item in (taskDetail?.checklists || [])" :key="`check-${item.id}`">
            <label class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-3 cursor-pointer">
                <input
                    type="checkbox"
                    class="mt-1 rounded border-white/20 bg-transparent"
                    :checked="!!item.is_completed"
                    @change="toggleChecklistItem(item)"
                >
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm"
                        :class="item.is_completed ? 'line-through text-white/45' : 'text-white'"
                        x-text="item.title"
                    ></p>
                </div>
                <button
                    x-show="canEditTask(taskDetail)"
                    type="button"
                    @click.stop="deleteChecklistItem(item)"
                    class="text-xs text-white/45 hover:text-red-300"
                >
                    Sil
                </button>
            </label>
        </template>

        <div x-show="!(taskDetail?.checklists || []).length" class="text-sm text-white/55">
            Yoxlama siyahısı yoxdur
        </div>
    </div>
</div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-base font-semibold">Əlavələr</h3>
                        <label class="px-3 py-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-sm cursor-pointer">
                            Fayl yüklə
                            <input type="file" class="hidden" @change="uploadTaskFile($event)">
                        </label>
                    </div>

                    <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
                        <template x-for="att in (taskDetail?.attachments || [])" :key="`att-${att.id}`">
                            <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[10px] font-semibold" x-text="attachmentExt(att.original_name)"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm truncate" x-text="att.original_name"></p>
                                    <p class="text-[11px] text-white/45" x-text="att.size_human || ''"></p>
                                </div>
                                <a :href="`/api/attachments/${att.id}/download`" class="text-xs text-white/70 hover:text-white">Yüklə</a>
                            </div>
                        </template>
                        <div x-show="!(taskDetail?.attachments || []).length" class="text-sm text-white/55">Fayl yoxdur</div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-5 p-4 sm:p-5 border-l border-white/10 bg-[#163067]/80 overflow-y-auto">
                <div class="rounded-2xl bg-[#132857] border border-white/10 p-4 space-y-4 h-full flex flex-col">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-base font-semibold">Şərhlər</h3>
                        <button @click="loadTaskComments()" class="text-[11px] text-white/45 hover:text-white">Yenilə</button>
                    </div>

                    <div class="space-y-3 overflow-y-auto flex-1 pr-1">
                        <template x-if="taskCommentsLoading">
                            <div class="text-sm text-white/45">Şərhlər yüklənir...</div>
                        </template>

                        <template x-for="comment in taskComments" :key="`comment-${comment.id}`">
                            <div class="flex gap-3">
                                <img :src="comment.author?.avatar_url || '{{ auth()->user()->avatar_url }}'" class="w-8 h-8 rounded-full object-cover mt-1">
                                <div class="flex-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-sm font-medium" x-text="comment.author?.full_name || 'İstifadəçi'"></span>
                                        <span class="text-[10px] text-white/45" x-text="comment.created_at ? formatDate(comment.created_at) : ''"></span>
                                    </div>
                                    <p class="text-sm text-white/75 whitespace-pre-wrap" x-text="comment.body"></p>
                                </div>
                            </div>
                        </template>

                        <div x-show="!taskCommentsLoading && taskComments.length === 0" class="text-sm text-white/45">Hələ şərh yoxdur</div>
                    </div>

                    <div class="pt-2 space-y-3">
                        <textarea x-model="quickComment" rows="3" placeholder="Şərh yazın"
                                  class="w-full rounded-2xl px-4 py-3 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none resize-none"></textarea>
                        <div class="flex justify-end">
                            <button @click="submitTaskComment()" :disabled="!quickComment.trim()"
                                    class="px-4 py-2.5 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50">
                                Göndər
                            </button>
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
        taskComments: [],
        taskCommentsLoading: false,
        editingTaskAssignees: false,
        selectedTaskAssignees: [],
        taskAssigneeSearch: '',
        taskAssigneeResults: [],
        editingTaskDates: false,
        taskDateForm: { start_date:'', due_date:'' },
        showInlineSubtaskForm: false,
        newInlineSubtask: { title:'', due_date:'' },
        showChecklistForm: false,
newChecklistItem: { title: '' },

        statusSections: [
            { key:'in_progress',         label:'İcra olunur',       headerClass:'bg-blue-600' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir',   headerClass:'bg-purple-600' },
            { key:'completed',           label:'Tamamlandı',        headerClass:'bg-emerald-600' },
            { key:'todo',                label:'Görüləcək',         headerClass:'bg-slate-600' },
            { key:'canceled',            label:'Ləğv olundu',       headerClass:'bg-rose-600' },
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
            this.quickComment = '';
            this.taskComments = [];
            this.editingTaskAssignees = false;
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
            this.taskComments = [];
            this.editingTaskAssignees = false;
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

    const checklist = Array.isArray(task.checklists) ? task.checklists : [];
    const subtasks  = Array.isArray(task.subtasks) ? task.subtasks : [];

    if (task.checklist_progress !== undefined && task.checklist_progress !== null && task.checklist_progress !== '') {
        const value = parseInt(task.checklist_progress, 10);
        return Number.isNaN(value) ? 0 : value;
    }

    const total = checklist.length + subtasks.length;

    if (total > 0) {
        const done =
            checklist.filter(i => i.is_done || i.is_completed).length +
            subtasks.filter(i => i.status === 'completed').length;

        return Math.round((done / total) * 100);
    }

    if (task.progress !== undefined && task.progress !== null && task.progress !== '') {
        const value = parseInt(task.progress, 10);
        return Number.isNaN(value) ? 0 : value;
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
                this.taskComments = Array.isArray(data) ? data : (data?.data || []);
            } catch(e) {
                this.taskComments = [];
            } finally {
                this.taskCommentsLoading = false;
            }
        },

        openTaskAssigneeEditor() {
            this.selectedTaskAssignees = [...(this.taskDetail?.assignees || [])];
            this.taskAssigneeSearch = '';
            this.taskAssigneeResults = [];
            this.editingTaskAssignees = true;
        },

        async searchTaskAssignees() {
            if ((this.taskAssigneeSearch || '').length < 2) { this.taskAssigneeResults = []; return; }
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
                const sub = await api('POST', `/tasks/${this.taskDetail.id}/subtasks`, this.newInlineSubtask);
                if (!Array.isArray(this.taskDetail.subtasks)) this.taskDetail.subtasks = [];
                this.taskDetail.subtasks.push(sub);
                this.newInlineSubtask = { title:'', due_date:'' };
                this.showInlineSubtaskForm = false;
                await Promise.all([this.loadMyTasks(), this.loadSpaceGrouped()]);
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
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

    try {
        const updated = await api('PATCH', `/checklists/${item.id}/toggle`);

        item.is_done = updated?.is_done ?? updated?.is_completed ?? !item.is_done;
        item.is_completed = updated?.is_completed ?? updated?.is_done ?? item.is_done;
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
                this.taskComments.unshift(comment);
                this.quickComment = '';
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

        priorityLabel(p) { return { low:'Aşağı', medium:'Normal', high:'High', urgent:'Təcili' }[p] || (p || ''); },
        formatDate(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleDateString('az-AZ', { day:'2-digit', month:'2-digit', year:'2-digit' });
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
        },

        async searchEmployees() {
            if ((this.search || '').length < 2) {
                this.results = [];
                return;
            }

            try {
                let url = `/employees/search?q=${encodeURIComponent(this.search)}`;
                if (this.spaceId) url += `&space_id=${this.spaceId}`;

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


</script>
@endpush
