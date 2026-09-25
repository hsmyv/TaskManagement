@extends('layouts.app')
@section('title', 'Statistika')
@section('page-title', 'Statistika')

@section('content')
<div class="min-h-[calc(100vh-74px)] bg-gradient-to-br from-[#132e69] via-[#1d2f67] to-[#39245f] px-3 sm:px-5 lg:px-8 py-5 text-white" x-data="dashboardStatistics()" x-init="init()">
    <section class="max-w-[1560px] mx-auto space-y-6">
        <link rel="stylesheet" href="{{ asset('css/dashboard_statistics.css') }}">


        <div class="rounded-[18px] bg-[#102756]/90 border border-white/10 shadow-[0_24px_80px_rgba(5,14,45,0.34)] px-5 sm:px-7 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-semibold break-words">Statistika</h1>
                    <p class="text-sm text-white/55 mt-1 max-w-2xl break-words">Departamentlər üzrə iş yükü, status bölgüsü, gecikmə riski və tamamlanma göstəriciləri.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="h-10 px-4 rounded-[7px] border border-white/25 bg-white/10 hover:bg-white/15 text-sm font-medium inline-flex items-center justify-center shrink-0">
                    Dashboard
                </a>
            </div>
        </div>

        <template x-if="loading">
            <div class="rounded-[18px] bg-[#142d64]/95 border border-white/10 px-5 py-8 text-white/65">Statistika yüklənir...</div>
        </template>

        <div class="space-y-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-[14px] bg-white/8 border border-white/10 px-4 py-4 min-w-0">
                    <p class="text-3xl font-semibold" x-text="overallTotal()"></p>
                    <p class="text-xs text-white/50 mt-1 break-words">Ümumi tapşırıq</p>
                </div>
                <div class="rounded-[14px] bg-white/8 border border-white/10 px-4 py-4 min-w-0">
                    <p class="text-3xl font-semibold" x-text="overallBoards()"></p>
                    <p class="text-xs text-white/50 mt-1 break-words">Ümumi layihə</p>
                </div>
                <div class="rounded-[14px] bg-white/8 border border-white/10 px-4 py-4 min-w-0">
                    <p class="text-3xl font-semibold text-[#4ee27b]" x-text="completionRate() + '%'"></p>
                    <p class="text-xs text-white/50 mt-1 break-words">Tamamlanma</p>
                </div>
                <div class="rounded-[14px] bg-white/8 border border-white/10 px-4 py-4 min-w-0">
                    <p class="text-3xl font-semibold text-[#ff7979]" x-text="overallOverdue()"></p>
                    <p class="text-xs text-white/50 mt-1 break-words">Gecikmiş tapşırıq</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <h2 class="font-semibold mb-4 break-words">Tapşırıqların statusu</h2>
                    <div class="flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative w-40 h-40 rounded-full shrink-0" :style="statusDonutStyle()">
                            <div class="absolute inset-6 rounded-full bg-[#142d64] flex flex-col items-center justify-center">
                                <span class="text-3xl font-semibold" x-text="overallTotal()"></span>
                                <span class="text-[11px] text-white/45">ümumi</span>
                            </div>
                        </div>
                        <div class="space-y-2 flex-1 w-full min-w-0">
                            <template x-for="s in statusSections" :key="'stat-status-' + s.key">
                                <button type="button" @click="selectStatus(s.key)" class="w-full flex items-center justify-between gap-3 text-sm min-w-0 rounded-[9px] px-2 py-1.5 transition" :class="selectedStatus === s.key ? 'bg-white/14 text-white' : 'hover:bg-white/7'">
                                    <span class="flex items-center gap-2 text-white/72 min-w-0 text-left">
                                        <i class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background:' + statusColor(s.key)"></i>
                                        <span class="truncate" x-text="s.label"></span>
                                    </span>
                                    <b class="shrink-0" x-text="statusTotal(s.key)"></b>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <h2 class="font-semibold mb-4 break-words">Tamamlanma göstəricisi</h2>
                    <div class="flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative w-40 h-40 rounded-full shrink-0" :style="completionDonutStyle()">
                            <div class="absolute inset-6 rounded-full bg-[#142d64] flex items-center justify-center text-3xl font-semibold" x-text="completionRate() + '%'"></div>
                        </div>
                        <div class="space-y-3 flex-1 w-full text-sm min-w-0">
                            <div class="flex justify-between gap-3 text-white/70"><span class="truncate">Tamamlandı</span><b class="shrink-0" x-text="overallCompleted()"></b></div>
                            <div class="flex justify-between gap-3 text-white/70"><span class="truncate">Aktiv işlər</span><b class="shrink-0" x-text="activeTotal()"></b></div>
                            <div class="flex justify-between gap-3 text-white/70"><span class="truncate">Ləğv edildi</span><b class="shrink-0" x-text="statusTotal('canceled')"></b></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <h2 class="font-semibold mb-4 break-words">Gecikmə riski</h2>
                    <div class="flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative w-40 h-40 rounded-full shrink-0" :style="overdueDonutStyle()">
                            <div class="absolute inset-6 rounded-full bg-[#142d64] flex flex-col items-center justify-center">
                                <span class="text-3xl font-semibold text-[#ff7979]" x-text="overdueRate() + '%'"></span>
                                <span class="text-[11px] text-white/45">risk</span>
                            </div>
                        </div>
                        <div class="space-y-2 flex-1 w-full min-w-0">
                            <template x-for="space in riskySpaces().slice(0, 5)" :key="'stat-risk-' + space.id">
                                <div class="relative group flex items-center justify-between gap-3 text-sm min-w-0">
                                    <span class="truncate text-white/72" :title="space.name" x-text="space.name"></span>
                                    <span class="pointer-events-none absolute left-0 top-full z-30 mt-2 hidden max-w-[min(420px,80vw)] rounded-[10px] border border-white/10 bg-[#0d244f] px-3 py-2 text-xs leading-5 text-white shadow-2xl group-hover:block break-words" x-text="space.name"></span>
                                    <b class="text-[#ffaaa0] shrink-0" x-text="spaceOverdueCount(space.id)"></b>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="font-semibold break-words">Departament yükü</h2>
                        <span class="rounded-full bg-white/10 border border-white/10 px-3 py-1 text-xs text-white/70" x-text="selectedStatusLabel()"></span>
                    </div>
                    <div class="statistics-scroll max-h-[520px] overflow-y-auto pr-1 space-y-4">
                        <template x-for="space in sortedSpaceStats()" :key="'stat-workload-' + space.id">
                            <div class="min-w-0 rounded-[12px] bg-white/5 border border-white/10 p-3">
                                <button type="button" @click="toggleSpace(space.id)" class="w-full flex items-center justify-between gap-3 text-sm mb-2 min-w-0 text-left">
                                    <span class="truncate text-white/78" x-text="space.name"></span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="text-white/50" x-text="spaceTasksByStatus(space.id).length + ' tapşırıq'"></span>
                                        <span class="text-white/45 transition" :class="isSpaceOpen(space.id) ? 'rotate-90' : ''">›</span>
                                    </span>
                                </button>
                                <div class="h-3 rounded-full bg-[#0d244f] overflow-hidden flex">
                                    <div :style="'width:' + spaceStatusPart(space.id, 'todo') + '%; background:' + statusColor('todo')"></div>
                                    <div :style="'width:' + spaceStatusPart(space.id, 'in_progress') + '%; background:' + statusColor('in_progress')"></div>
                                    <div :style="'width:' + spaceStatusPart(space.id, 'waiting_for_approve') + '%; background:' + statusColor('waiting_for_approve')"></div>
                                    <div :style="'width:' + spaceStatusPart(space.id, 'completed') + '%; background:' + statusColor('completed')"></div>
                                    <div :style="'width:' + spaceStatusPart(space.id, 'canceled') + '%; background:' + statusColor('canceled')"></div>
                                </div>
                                <div class="mt-3 space-y-2" x-show="isSpaceOpen(space.id)">
                                    <template x-if="spaceTasksByStatus(space.id).length === 0">
                                        <div class="rounded-[10px] bg-white/5 border border-white/10 px-3 py-2 text-xs text-white/45">Bu status üzrə tapşırıq yoxdur.</div>
                                    </template>
                                    <template x-for="task in spaceTasksByStatus(space.id).slice(0, 5)" :key="'space-status-task-' + space.id + '-' + task.id">
                                        <button type="button" @click="openTaskModal(task.id)" class="w-full text-left rounded-[10px] bg-white/5 hover:bg-white/9 border border-white/10 px-3 py-2 min-w-0 transition">
                                            <div class="flex items-start justify-between gap-3 min-w-0">
                                                <p class="text-xs text-white/80 truncate" :title="task.title" x-text="task.title"></p>
                                                <span class="shrink-0 text-[10px] text-white/45" x-text="task.due_date ? formatDate(task.due_date) : '-'"></span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-white/45 truncate" x-text="task.board?.name || 'Boardsuz'"></p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <h2 class="font-semibold mb-4 break-words">Tamamlanma reytinqi</h2>
                    <div class="statistics-scroll max-h-[420px] overflow-y-auto pr-1 space-y-3">
                        <template x-for="space in sortedByCompletion()" :key="'stat-completion-' + space.id">
                            <div class="rounded-[10px] bg-white/5 border border-white/10 p-3 min-w-0">
                                <div class="flex items-center justify-between gap-3 mb-2 min-w-0">
                                    <span class="truncate text-sm text-white/78" x-text="space.name"></span>
                                    <b class="text-[#7ef0a1] shrink-0" x-text="spaceCompletion(space) + '%'"></b>
                                </div>
                                <div class="h-2.5 rounded-full bg-[#0d244f] overflow-hidden">
                                    <div class="h-2.5 rounded-full bg-[#38d56f]" :style="'width:' + spaceCompletion(space) + '%'"></div>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3 text-[11px] text-white/48 min-w-0">
                                    <span class="truncate" x-text="spaceCompletedCount(space.id) + ' tamamlandı'"></span>
                                    <span class="shrink-0" x-text="spaceTaskCount(space.id) + ' tapşırıq'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <h2 class="font-semibold break-words">Siz tərəfindən yaradılan və verilən tapşırıqlar</h2>
                    <span class="text-xs text-white/50" x-text="assignedByTasks.length + ' tapşırıq'"></span>
                </div>
                <div class="statistics-scroll max-h-[520px] overflow-y-auto pr-1 space-y-3">
                    <template x-if="assignedByTasks.length === 0">
                        <div class="rounded-[12px] bg-white/5 border border-white/10 px-4 py-4 text-sm text-white/55">Sizin tərəfinizdən yaradılan və ya verilən tapşırıq yoxdur.</div>
                    </template>
                    <template x-for="task in assignedByTasks" :key="'assigned-by-task-' + task.id">
                        <button type="button" @click="openTaskModal(task.id)" class="w-full text-left rounded-[12px] bg-white/5 hover:bg-white/9 border border-white/10 px-4 py-3 min-w-0 transition">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background:' + statusColor(task.status)"></span>
                                        <p class="font-medium text-white truncate" :title="task.title" x-text="task.title"></p>
                                    </div>
                                    <p class="text-xs text-white/48 mt-1 truncate">
                                        <span x-text="task.space?.name || 'Departament yoxdur'"></span>
                                        <span> / </span>
                                        <span x-text="task.board?.name || 'Boardsuz'"></span>
                                    </p>
                                    <p class="text-xs text-white/60 mt-2 line-clamp-2 break-words" x-text="task.description || 'Təsvir yoxdur'"></p>
                                </div>
                                <div class="shrink-0 min-w-[220px] space-y-2">
                                    <div class="flex justify-between gap-3 text-xs text-white/55">
                                        <span>Status</span>
                                        <b class="text-white/80" x-text="statusLabel(task.status)"></b>
                                    </div>
                                    <div class="flex justify-between gap-3 text-xs text-white/55">
                                        <span>Son tarix</span>
                                        <b class="text-white/80" x-text="task.due_date ? formatDate(task.due_date) : '-'"></b>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-xs text-white/55">Təyinatçılar</span>
                                        <div class="flex -space-x-2">
                                            <template x-for="person in (task.assignees || []).slice(0, 5)" :key="'assigned-person-' + task.id + '-' + person.id">
                                                <img :src="person.avatar_url" :title="person.full_name" class="w-7 h-7 rounded-full object-cover ring-2 ring-[#142d64]">
                                            </template>
                                            <span x-show="!(task.assignees || []).length" class="text-xs text-white/45">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <div x-show="taskModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-3 sm:p-4" @keydown.escape.window="closeTaskModal()">
        <div @click.outside="closeTaskModal()" x-transition.scale class="w-full max-w-6xl h-[88vh] overflow-hidden rounded-[24px] bg-gradient-to-b from-[#1f397e] to-[#182d65] border border-white/10 shadow-2xl text-white">
            <div class="px-5 py-4 flex items-start justify-between gap-4 border-b border-white/10">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-[18px] sm:text-[22px] font-semibold break-words" x-text="taskDetail?.title || 'Tapşırıq'"></h2>
                        <template x-if="taskDetail?.assigner?.full_name">
                            <span class="text-sm text-white/65">- <span x-text="taskDetail.assigner.full_name"></span> tərəfindən</span>
                        </template>
                    </div>
                    <p class="mt-1 text-xs text-white/55 truncate">
                        <span x-text="taskDetail?.space?.name || ''"></span>
                        <span x-show="taskDetail?.board?.name"> / </span>
                        <span x-text="taskDetail?.board?.name || ''"></span>
                    </p>
                </div>
                <button type="button" @click="closeTaskModal()" class="text-white/60 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <div class="grid grid-cols-12 h-[calc(88vh-72px)] overflow-hidden">
                <div class="col-span-12 lg:col-span-7 p-4 sm:p-5 space-y-4 overflow-y-auto statistics-scroll">
                    <template x-if="taskLoading">
                        <div class="text-sm text-white/60">Yüklənir...</div>
                    </template>

                    <template x-if="taskDetail && !taskLoading">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-white/45 mb-1 text-xs">Status</p>
                                    <select x-model="taskDetail.status" @change="saveTaskStatus(taskDetail.status)" :disabled="!canEditTask(taskDetail)" class="w-full h-11 rounded-xl bg-[#1b2f64] text-white border border-white/15 px-3 focus:outline-none disabled:opacity-60">
                                        <template x-for="s in statusSections" :key="'modal-status-' + s.key">
                                            <option class="bg-[#1b2f64] text-white" :value="s.key" x-text="s.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-white/45 mb-1 text-xs">Prioritet</p>
                                    <select x-model="taskDetail.priority" @change="saveTaskPriority(taskDetail.priority)" :disabled="!canEditTask(taskDetail)" class="w-full h-11 rounded-xl bg-[#1b2f64] text-white border border-white/15 px-3 focus:outline-none disabled:opacity-60">
                                        <option class="bg-[#1b2f64] text-white" value="low">Aşağı</option>
                                        <option class="bg-[#1b2f64] text-white" value="medium">Orta</option>
                                        <option class="bg-[#1b2f64] text-white" value="high">Yüksək</option>
                                        <option class="bg-[#1b2f64] text-white" value="urgent">Təcili</option>
                                    </select>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-base font-semibold">Tapşırıq</h3>
                                    <button x-show="canEditTask(taskDetail) && !editingTaskMain" type="button" @click="openTaskMainEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                                </div>
                                <div x-show="!editingTaskMain" class="space-y-3">
                                    <p class="text-lg font-semibold break-words" x-text="taskDetail.title"></p>
                                    <p class="text-sm text-white/72 leading-6 whitespace-pre-wrap break-words" x-text="taskDetail.description || 'Təsvir yoxdur'"></p>
                                </div>
                                <div x-show="editingTaskMain" class="space-y-3">
                                    <input type="text" x-model="taskMainForm.title" class="w-full h-11 rounded-xl px-4 bg-white text-slate-800 focus:outline-none" placeholder="Başlıq">
                                    <textarea x-model="taskMainForm.description" rows="5" class="w-full rounded-xl px-4 py-3 bg-white text-slate-800 focus:outline-none resize-none" placeholder="Təsvir"></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="editingTaskMain=false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                        <button type="button" @click="saveTaskMain()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-base font-semibold">İrəliləyiş</h3>
                                    <span class="text-xs text-white/70" x-text="taskProgress(taskDetail) + '%'"></span>
                                </div>
                                <div class="h-2.5 rounded-full bg-[#0d214d] overflow-hidden">
                                    <div class="h-2.5 rounded-full bg-[#c5a13c]" :style="'width:' + taskProgress(taskDetail) + '%'"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <h3 class="text-base font-semibold">Təyinatçılar</h3>
                                        <button x-show="canEditTask(taskDetail) && !editingTaskAssignees" type="button" @click="openTaskAssigneeEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                                    </div>
                                    <div class="space-y-2" x-show="!editingTaskAssignees">
                                        <template x-if="!(taskDetail.assignees || []).length">
                                            <div class="text-sm text-white/55">Təyinatçı seçilməyib</div>
                                        </template>
                                        <template x-for="person in (taskDetail.assignees || [])" :key="'stat-modal-assignee-' + person.id">
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
                                            <template x-for="emp in selectedTaskAssignees" :key="'stat-selected-assignee-' + emp.id">
                                                <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                                    <img :src="emp.avatar_url" class="w-4 h-4 rounded-full object-cover">
                                                    <span x-text="emp.full_name"></span>
                                                    <button type="button" @click="removeTaskAssignee(emp.id)" class="hover:text-red-300">x</button>
                                                </span>
                                            </template>
                                        </div>
                                        <input type="text" x-model="taskAssigneeSearch" @input.debounce.300ms="searchTaskAssignees()" placeholder="Təyinatçı axtar..." class="w-full h-11 rounded-xl px-4 bg-white text-slate-800 focus:outline-none">
                                        <div class="rounded-2xl bg-[#163067] border border-white/10 max-h-40 overflow-y-auto statistics-scroll" x-show="taskAssigneeResults.length">
                                            <template x-for="emp in taskAssigneeResults" :key="'stat-result-assignee-' + emp.id">
                                                <button type="button" @click="selectTaskAssignee(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-white/5">
                                                    <img :src="emp.avatar_url" class="w-8 h-8 rounded-full object-cover">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-white truncate" x-text="emp.full_name"></p>
                                                        <p class="text-[11px] text-white/45 truncate" x-text="emp.position || emp.email || ''"></p>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="editingTaskAssignees=false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                            <button type="button" @click="saveTaskAssignees()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <h3 class="text-base font-semibold">Tarix</h3>
                                        <button x-show="canEditTask(taskDetail) && !editingTaskDates" type="button" @click="prepareTaskDates(); editingTaskDates=true" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                                    </div>
                                    <div x-show="!editingTaskDates" class="space-y-2 text-sm">
                                        <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                            <p class="text-white/45 mb-1 text-xs">Başlama tarixi</p>
                                            <p x-text="taskDetail.start_date ? formatDate(taskDetail.start_date) : '-'"></p>
                                        </div>
                                        <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                            <p class="text-white/45 mb-1 text-xs">Son tarix</p>
                                            <p x-text="taskDetail.due_date ? formatDate(taskDetail.due_date) : '-'"></p>
                                        </div>
                                    </div>
                                    <div x-show="editingTaskDates" class="space-y-3">
                                        <input type="date" x-model="taskDateForm.start_date" class="w-full h-11 rounded-xl px-4 bg-white text-slate-800 focus:outline-none">
                                        <input type="date" x-model="taskDateForm.due_date" class="w-full h-11 rounded-xl px-4 bg-white text-slate-800 focus:outline-none">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="editingTaskDates=false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                            <button type="button" @click="saveTaskDates()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Saxla</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-4">
                                <h3 class="text-base font-semibold">Köməkçi və nəzarətçi əməkdaşlar</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <p class="text-xs text-white/45">Köməkçilər</p>
                                        <template x-if="!(taskDetail.helpers || []).length">
                                            <div class="text-sm text-white/55">Köməkçi seçilməyib</div>
                                        </template>
                                        <template x-for="person in (taskDetail.helpers || [])" :key="'stat-modal-helper-' + person.id">
                                            <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                                <img :src="person.avatar_url" class="w-9 h-9 rounded-full object-cover">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium truncate" x-text="person.full_name"></p>
                                                    <p class="text-[11px] text-white/50 truncate" x-text="person.position || person.email || ''"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs text-white/45">Nəzarətçilər</p>
                                        <template x-if="!(taskDetail.supervisors || []).length">
                                            <div class="text-sm text-white/55">Nəzarətçi seçilməyib</div>
                                        </template>
                                        <template x-for="person in (taskDetail.supervisors || [])" :key="'stat-modal-supervisor-' + person.id">
                                            <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                                <img :src="person.avatar_url" class="w-9 h-9 rounded-full object-cover">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium truncate" x-text="person.full_name"></p>
                                                    <p class="text-[11px] text-white/50 truncate" x-text="person.position || person.email || ''"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                <h3 class="text-base font-semibold">Alt tapşırıqlar</h3>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1 statistics-scroll">
                                    <template x-for="subtask in (taskDetail.subtasks || [])" :key="'stat-modal-subtask-' + subtask.id">
                                        <div class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background:' + statusColor(subtask.status)"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm truncate" x-text="subtask.title"></p>
                                                <p class="text-[11px] text-white/45" x-text="subtask.due_date ? formatDate(subtask.due_date) : ''"></p>
                                            </div>
                                            <div class="flex -space-x-2" x-show="(subtask.assignees || []).length">
                                                <template x-for="person in (subtask.assignees || [])" :key="'stat-modal-subtask-person-' + subtask.id + '-' + person.id">
                                                    <img :src="person.avatar_url" :title="person.full_name" class="w-7 h-7 rounded-full object-cover ring-2 ring-[#163067]">
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!(taskDetail.subtasks || []).length" class="text-sm text-white/55">Alt tapşırıq yoxdur</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="col-span-12 lg:col-span-5 p-4 sm:p-5 border-l border-white/10 bg-[#163067]/80 overflow-hidden">
                    <div class="rounded-2xl bg-[#132857] border border-white/10 p-4 space-y-4 h-full flex flex-col">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-base font-semibold">Şərhlər</h3>
                            <button type="button" @click="loadTaskComments()" class="text-[11px] text-white/45 hover:text-white">Yenilə</button>
                        </div>

                        <div class="space-y-3 overflow-y-auto flex-1 pr-1 statistics-scroll" x-ref="commentsList">
                            <template x-if="commentsLoading">
                                <div class="text-sm text-white/45">Şərhlər yüklənir...</div>
                            </template>
                            <template x-for="comment in flattenComments(comments)" :key="'stat-comment-' + comment.id">
                                <div class="flex gap-3" :style="`margin-left: ${Math.min(comment._depth, 6) * 18}px`">
                                    <img :src="comment.author?.avatar_url || '{{ auth()->user()->avatar_url }}'" class="w-8 h-8 rounded-full object-cover mt-1">
                                    <div class="flex-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-sm font-medium" x-text="comment.author?.full_name || 'İstifadəçi'"></span>
                                            <span class="text-[10px] text-white/45" x-text="comment.created_at ? formatDate(comment.created_at) : ''"></span>
                                        </div>
                                        <p class="text-sm text-white/75 whitespace-pre-wrap break-words" x-text="comment.body || comment.content || ''"></p>
                                        <div class="mt-2 flex items-center gap-3">
                                            <button type="button" @click="startReply(comment)" class="text-[11px] text-white/45 hover:text-white">Cavabla</button>
                                            <button type="button" x-show="comment._replyCount" @click="toggleCommentReplies(comment)" class="text-[11px] text-white/45 hover:text-white" x-text="expandedComments[comment.id] ? 'Cavabları gizlət' : `${comment._replyCount} cavabı göstər`"></button>
                                        </div>
                                        <div x-show="replyingTo?.id === comment.id" class="mt-3 space-y-2">
                                            <textarea x-model="replyText" rows="2" class="w-full rounded-xl px-3 py-2 bg-white text-slate-800 focus:outline-none resize-none" placeholder="Cavab yazın"></textarea>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="cancelReply()" class="px-3 py-1.5 rounded-lg bg-white/8 text-xs">Ləğv</button>
                                                <button type="button" @click="submitReply(comment)" :disabled="!replyText.trim()" class="px-3 py-1.5 rounded-lg bg-[#6d44c5] hover:bg-[#613db1] text-xs disabled:opacity-50">Göndər</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!commentsLoading && comments.length === 0" class="text-sm text-white/45">Hələ şərh yoxdur</div>
                        </div>

                        <div class="pt-2 space-y-3">
                            <textarea x-model="quickComment" rows="3" placeholder="Şərh yazın" class="w-full rounded-2xl px-4 py-3 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none resize-none"></textarea>
                            <div class="flex justify-end">
                                <button type="button" @click="submitTaskComment()" :disabled="!quickComment.trim()" class="px-4 py-2.5 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50">Göndər</button>
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
    <script src="{{ asset('js/dashboard_statistics.js') }}"></script>
@endpush
