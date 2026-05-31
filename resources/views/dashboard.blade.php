@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="min-h-[calc(100vh-74px)] bg-gradient-to-br from-[#132e69] via-[#1d2f67] to-[#39245f] px-3 sm:px-5 lg:px-8 py-5 text-white" x-data="dashboard()" x-init="init()">
    <section class="max-w-7xl mx-auto space-y-7">
        <style>
            .dashboard-scroll {
                scrollbar-width: thin;
                scrollbar-color: rgba(147, 178, 231, .7) rgba(10, 27, 65, .18);
            }
            .dashboard-scroll::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .dashboard-scroll::-webkit-scrollbar-track {
                background: rgba(10, 27, 65, .18);
                border-radius: 999px;
            }
            .dashboard-scroll::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, rgba(134, 171, 232, .9), rgba(29, 94, 170, .9));
                border-radius: 999px;
            }
        </style>

        <div class="overflow-hidden rounded-[18px] border border-white/10 bg-[#102756]/85 shadow-[0_24px_80px_rgba(5,14,45,0.34)]">
            <div class="min-h-[62px] bg-[#0d244f] px-5 sm:px-7 py-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold">İdarə heyəti</h1>
                    <p class="text-sm text-white/55 mt-1">Tapşırıqlar, departamentlər və ümumi performans eyni paneldə.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="openCreateTask()" class="h-10 px-4 rounded-[7px] border border-white/35 bg-[#0b2448] hover:bg-[#12305b] flex items-center gap-2 text-sm shadow-lg shadow-black/10">
                        <span class="text-2xl leading-none -mt-0.5">+</span>
                        <span>Tapşırıq</span>
                    </button>
                    <a href="{{ route('dashboard.statistics') }}" class="h-10 px-4 rounded-[7px] border border-white/25 bg-white/10 hover:bg-white/15 text-sm font-medium inline-flex items-center">
                        Statistika
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-12 min-h-[560px] bg-[#aaa7b6]">
                <aside class="col-span-12 lg:col-span-3 xl:col-span-3 bg-[#355188] px-5 py-6 text-white">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-[22px] font-medium">Tapşırıqlar</h2>
                        <button @click="openCreateTask()" class="w-9 h-9 rounded-[7px] bg-[#0d244f] hover:bg-[#163464] border border-white/10 text-3xl leading-none flex items-center justify-center pb-1">+</button>
                    </div>

                    <div class="space-y-2 mb-4">
                        <input type="search" x-model.debounce.400ms="filters.q" @input.debounce.400ms="loadTasks()" placeholder="Tapşırıq və layihə axtar..." class="w-full h-10 rounded-[7px] border border-white/15 bg-[#203d75] px-3 text-sm text-white placeholder:text-white/45 focus:outline-none focus:border-white/35">
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="filters.status" @change="loadTasks()" class="h-10 rounded-[7px] border border-white/15 bg-[#203d75] px-2 text-xs text-white focus:outline-none">
                                <option value="">Bütün statuslar</option>
                                <template x-for="s in statusSections" :key="'filter-status-' + s.key">
                                    <option :value="s.key" x-text="s.label"></option>
                                </template>
                            </select>
                            <select x-model="filters.priority" @change="loadTasks()" class="h-10 rounded-[7px] border border-white/15 bg-[#203d75] px-2 text-xs text-white focus:outline-none">
                                <option value="">Bütün prioritetlər</option>
                                <option value="low">Aşağı</option>
                                <option value="medium">Orta</option>
                                <option value="high">Yüksək</option>
                                <option value="urgent">Təcili</option>
                            </select>
                        </div>
                    </div>

                    <div class="dashboard-scroll max-h-[430px] overflow-y-auto pr-1 space-y-3">
                        <template x-if="tasksLoading">
                            <div class="rounded-[12px] bg-[#1d376d] px-4 py-4 text-sm text-white/65">Tapşırıqlar yüklənir...</div>
                        </template>
                        <template x-if="!tasksLoading && sidebarTasks().length === 0">
                            <div class="rounded-[12px] bg-[#1d376d] px-4 py-4 text-sm text-white/65">Tapşırıq yoxdur</div>
                        </template>
                        <template x-for="task in sidebarTasks()" :key="'side-task-' + task.id">
                            <button type="button" @click="openTaskModal(task.id)" class="w-full rounded-[12px] bg-[#1d376d] hover:bg-[#254780] px-3 py-3 text-left shadow-[0_12px_26px_rgba(4,16,45,0.18)] transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] truncate" :title="task.title" x-text="task.title"></p>
                                        <p class="text-[10px] text-white/45 mt-1 truncate" x-text="task.board?.name || task.space?.name || ''"></p>
                                    </div>
                                    <div class="flex -space-x-2 shrink-0">
                                        <template x-for="person in (task.assignees || []).slice(0, 2)" :key="'side-assignee-' + task.id + '-' + person.id">
                                            <img :src="person.avatar_url" :title="person.full_name" class="w-8 h-8 rounded-full object-cover ring-2 ring-[#1d376d]">
                                        </template>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-2">
                                    <div class="h-2 flex-1 rounded-full bg-[#102755] overflow-hidden">
                                        <div class="h-2 rounded-full" :class="taskProgress(task) >= 80 ? 'bg-[#38c66a]' : 'bg-[#c9782c]'" :style="'width:' + taskProgress(task) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] text-white/70 min-w-[34px]" x-text="taskProgress(task) + '%'"></span>
                                    <span class="text-[10px] text-white/65" x-text="task.due_date ? formatDate(task.due_date) : '-'"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </aside>

                <section class="col-span-12 lg:col-span-9 xl:col-span-9 px-5 sm:px-8 lg:px-12 py-10 text-[#102550]">
                    <template x-if="spacesLoading">
                        <div class="rounded-[12px] bg-white/55 border border-white/50 px-5 py-5 text-[#203157]/65">Departamentlər yüklənir...</div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <template x-for="space in orderedSpaces()" :key="'main-space-' + space.id">
                            <a :href="'/spaces/' + space.id" class="group min-h-[98px] rounded-[8px] bg-gradient-to-br from-[#2d5ba4] to-[#0d817c] hover:from-[#3368bb] hover:to-[#0b8f89] text-white shadow-[0_18px_40px_rgba(13,34,76,0.18)] px-5 py-4 flex items-center justify-center transition">
                                <div class="w-full flex items-start justify-between gap-3">
                                    <h3 class="text-[15px] leading-5 text-center flex-1 break-words hyphens-auto" x-text="space.name"></h3>
                                    <span x-show="Number(filters.space_id) === Number(space.id)" class="shrink-0 rounded-full bg-white/18 px-2 py-1 text-[10px]">Seçilib</span>
                                </div>
                            </a>
                        </template>
                    </div>
                </section>
            </div>
        </div>

    <div x-show="showCreateModal" x-cloak x-transition.opacity class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-4">
        <div @click.stop x-transition.scale class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[18px] bg-gradient-to-b from-[#233d82] to-[#182b5d] border border-white/10 shadow-2xl text-white">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-semibold text-lg">Yeni Tapşırıq</h2>
                <button @click="showCreateModal = false" class="text-white/60 hover:text-white">✕</button>
            </div>
            <form @submit.prevent="createTask()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Departament *</label>
                    <select x-model="newTask.space_id" required class="w-full h-12 rounded-xl px-4 tis-input">
                        <option value="">Seçin</option>
                        <template x-for="space in spaces" :key="`task-space-${space.id}`">
                            <option :value="space.id" x-text="space.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Başlıq *</label>
                    <input type="text" x-model="newTask.title" required placeholder="Tapşırığın adı..." class="w-full h-12 rounded-xl px-4 tis-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-1">Təsvir</label>
                    <textarea x-model="newTask.description" rows="3" placeholder="Ətraflı təsvir..." class="w-full rounded-xl px-4 py-3 tis-input resize-none"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-1">Başlama tarixi</label>
                        <input type="date" x-model="newTask.start_date" class="w-full h-12 rounded-xl px-4 tis-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-1">Son tarix</label>
                        <input type="date" x-model="newTask.due_date" class="w-full h-12 rounded-xl px-4 tis-input">
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
                </div>

                <div x-data="employeePicker()" x-init="init()">
                    <label class="block text-sm font-medium text-white/80 mb-1">Təyinatçı</label>
                    <input type="text" x-model="search" @input.debounce.300ms="searchEmployees(newTask.space_id)" @focus="open=true" placeholder="Ad ilə axtarın..." class="w-full h-12 rounded-xl px-4 tis-input">
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
                        <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="if (!results.length) loadAllEmployees()" placeholder="İşçini axtar..." class="w-full h-12 rounded-xl px-4 tis-input">
                        <button type="button" @click="loadAllEmployees()" class="px-4 rounded-xl bg-white/10 border border-white/10 hover:bg-white/15 text-sm">Bax</button>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3" x-show="selected.length">
                        <template x-for="emp in selected" :key="emp.id">
                            <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-2 rounded-full border border-white/10">
                                <img :src="emp.avatar_url" class="w-5 h-5 rounded-full object-cover">
                                <span x-text="emp.full_name"></span>
                                <button type="button" @click="remove(emp.id); newTask.assigned_by_id = null;" class="hover:text-red-300">✕</button>
                            </span>
                        </template>
                    </div>
                    <div x-show="open" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-48 overflow-y-auto">
                        <template x-if="!results.length">
                            <div class="px-4 py-3 text-sm text-white/60">Nəticə tapılmadı</div>
                        </template>
                        <template x-for="emp in results" :key="emp.id">
                            <button type="button" @click="select(emp); newTask.assigned_by_id = emp.id;" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 text-left text-sm">
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
                    <button type="submit" :disabled="creating || !newTask.space_id || !newTask.title.trim()" class="px-5 py-2.5 text-sm font-medium bg-[#6d44c5] hover:bg-[#613db1] rounded-xl disabled:opacity-60">
                        <span x-text="creating ? 'Yaradılır...' : 'Yarat'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="taskModalOpen" x-cloak x-transition.opacity class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-3 sm:p-4">
        <div @click.stop x-transition.scale class="w-full max-w-5xl h-[88vh] overflow-hidden rounded-[24px] bg-gradient-to-b from-[#1f397e] to-[#182d65] border border-white/10 shadow-2xl text-white">
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
                    <p class="text-sm text-white/55 mt-1" x-text="taskDetail?.space?.name || ''"></p>
                </div>
                <button @click="closeTaskModal()" class="text-white/60 hover:text-white">✕</button>
            </div>

            <div class="grid grid-cols-12 h-[calc(88vh-72px)] overflow-hidden">
                <div class="col-span-12 lg:col-span-7 p-4 sm:p-5 space-y-4 overflow-y-auto">
                    <template x-if="taskLoading">
                        <div class="text-white/60">Yüklənir...</div>
                    </template>

                    <template x-if="taskDetail">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-white/45 mb-1 text-xs">Status</p>
                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/8 border border-white/10">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="statusDotClass(taskDetail?.status)"></span>
                                        <span x-text="statusLabel(taskDetail?.status)"></span>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-white/45 mb-1 text-xs">Prioritet</p>
                                    <p class="text-white font-medium" x-text="priorityLabel(taskDetail?.priority) || 'Orta'"></p>
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
                                <p class="text-sm text-white/72 leading-6 whitespace-pre-wrap" x-text="taskDetail?.description || 'Departamentin işləri ilə bağlı tapşırıq təsviri əlavə edilməyib.'"></p>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <h3 class="text-base font-semibold">Təyinatçılar</h3>
                                        <button x-show="canEditTask(taskDetail)" @click="openTaskAssigneeEditor()" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">Redaktə et</button>
                                    </div>

                                    <div class="space-y-2" x-show="!editingTaskAssignees">
                                        <template x-if="(taskDetail?.assignees || []).length === 0">
                                            <div class="text-sm text-white/55">Təyinatçı seçilməyib</div>
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
                                        <input type="text" x-model="taskAssigneeSearch" @input.debounce.300ms="searchTaskAssignees()" placeholder="Təyinatçı axtar..." class="w-full h-11 rounded-xl px-4 tis-input">
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
                                            <p x-text="taskDetail?.start_date ? formatDate(taskDetail.start_date) : '-'"></p>
                                        </div>
                                        <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                                            <p class="text-white/45 mb-1 text-xs">Son tarix</p>
                                            <p x-text="taskDetail?.due_date ? formatDate(taskDetail.due_date) : '-'"></p>
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
                                    <div x-data="employeePicker(taskDetail?.space_id)" x-init="init()">
                                        <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-11 rounded-xl px-4 tis-input">
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <template x-for="emp in selected" :key="`dashboard-new-sub-assignee-${emp.id}`">
                                                <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                                    <img :src="emp.avatar_url" class="w-4 h-4 rounded-full object-cover">
                                                    <span x-text="emp.full_name"></span>
                                                    <button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button>
                                                </span>
                                            </template>
                                        </div>
                                        <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-40 overflow-y-auto">
                                            <template x-for="emp in results" :key="`dashboard-new-sub-result-${emp.id}`">
                                                <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 text-left text-sm">
                                                    <img :src="emp.avatar_url" class="w-7 h-7 rounded-full object-cover">
                                                    <div>
                                                        <p class="font-medium text-white" x-text="emp.full_name"></p>
                                                        <p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p>
                                                    </div>
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
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                    <template x-for="sub in (taskDetail?.subtasks || [])" :key="`sub-${sub.id}`">
                                        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5" x-init="prepareSubtaskEdit(sub)">
                                            <span class="w-2.5 h-2.5 rounded-full" :class="sub.status === 'completed' ? 'bg-[#22d34f]' : 'bg-white/50'"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm truncate" x-text="sub.title"></p>
                                                <p class="text-[11px] text-white/45" x-text="sub.due_date ? formatDate(sub.due_date) : ''"></p>
                                            </div>
                                            <div class="flex -space-x-2" x-show="(sub.assignees || []).length">
                                                <template x-for="person in (sub.assignees || [])" :key="`dashboard-sub-assignee-${sub.id}-${person.id}`">
                                                    <img :src="person.avatar_url" :title="person.full_name" class="w-7 h-7 rounded-full object-cover ring-2 ring-[#163067]">
                                                </template>
                                            </div>
                                            <button x-show="canEditSubtask(sub) && sub.status !== 'completed'" @click="completeSubtask(sub)" class="px-3 py-1.5 rounded-lg bg-[#22d34f]/20 text-[#8effa9] border border-[#22d34f]/30 text-xs">Təsdiqlə</button>
                                            <button x-show="canEditSubtask(sub)" @click="sub.editing = !sub.editing; prepareSubtaskEdit(sub)" class="px-3 py-1.5 rounded-lg bg-white/8 hover:bg-white/12 text-xs">Redaktə et</button>
                                            <div x-show="sub.editing" class="basis-full space-y-3 rounded-xl bg-[#10285a] border border-white/10 p-3">
                                                <input type="text" x-model="sub.edit.title" class="w-full h-10 rounded-xl px-4 tis-input">
                                                <input type="date" x-model="sub.edit.due_date" class="w-full h-10 rounded-xl px-4 tis-input">
                                                <div x-data="employeePicker(taskDetail?.space_id)" x-init="init(sub.assignees || [])">
                                                    <input type="text" x-model="search" @input.debounce.300ms="searchEmployees()" @focus="open=true" placeholder="Məsul şəxs axtar..." class="w-full h-10 rounded-xl px-4 tis-input">
                                                    <div class="flex flex-wrap gap-2 mt-2">
                                                        <template x-for="emp in selected" :key="`dashboard-edit-sub-assignee-${sub.id}-${emp.id}`">
                                                            <span class="flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                                                <img :src="emp.avatar_url" class="w-4 h-4 rounded-full object-cover">
                                                                <span x-text="emp.full_name"></span>
                                                                <button type="button" @click="remove(emp.id)" class="hover:text-red-300">x</button>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <div x-show="open && results.length > 0" @click.outside="open=false" class="relative z-10 mt-2 bg-[#1d315f] border border-white/10 rounded-2xl shadow-2xl max-h-40 overflow-y-auto">
                                                        <template x-for="emp in results" :key="`dashboard-edit-sub-result-${sub.id}-${emp.id}`">
                                                            <button type="button" @click="select(emp)" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 text-left text-sm">
                                                                <img :src="emp.avatar_url" class="w-7 h-7 rounded-full object-cover">
                                                                <div>
                                                                    <p class="font-medium text-white" x-text="emp.full_name"></p>
                                                                    <p class="text-xs text-white/45" x-text="emp.position || emp.email || ''"></p>
                                                                </div>
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
                                    <div x-show="!(taskDetail?.subtasks || []).length" class="text-sm text-white/55">Alt tapşırıq yoxdur</div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-semibold">Yoxlama Siyahısı</h3>
                                    <button x-show="canEditTask(taskDetail)" @click="showChecklistForm = !showChecklistForm" class="text-[11px] px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/10">+ Bənd əlavə et</button>
                                </div>
                                <div x-show="showChecklistForm" class="rounded-2xl bg-[#163067] border border-white/10 p-3 space-y-3">
                                    <input type="text" x-model="newChecklistItem.title" placeholder="Yoxlama siyahısı bəndi" class="w-full h-11 rounded-xl px-4 tis-input">
                                    <div class="flex justify-end gap-2">
                                        <button @click="showChecklistForm = false" class="px-3 py-2 rounded-xl bg-white/8 hover:bg-white/12 text-sm">Ləğv</button>
                                        <button @click="createChecklistItem()" class="px-3 py-2 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm">Əlavə et</button>
                                    </div>
                                </div>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                    <template x-for="item in (taskDetail?.checklists || [])" :key="`check-${item.id}`">
                                        <label class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 px-3 py-3 cursor-pointer">
                                            <input type="checkbox" class="mt-1 rounded border-white/20 bg-transparent" :checked="!!(item.is_done || item.is_completed)" @change="toggleChecklistItem(item)">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm" :class="(item.is_done || item.is_completed) ? 'line-through text-white/45' : 'text-white'" x-text="item.title"></p>
                                            </div>
                                            <button x-show="canEditTask(taskDetail)" type="button" @click.stop="deleteChecklistItem(item)" class="text-xs text-white/45 hover:text-red-300">Sil</button>
                                        </label>
                                    </template>
                                    <div x-show="!(taskDetail?.checklists || []).length" class="text-sm text-white/55">Yoxlama siyahısı yoxdur</div>
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
                    </template>
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
                            <textarea x-model="quickComment" rows="3" placeholder="Şərh yazın" class="w-full rounded-2xl px-4 py-3 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none resize-none"></textarea>
                            <div class="flex justify-end">
                                <button @click="submitTaskComment()" :disabled="!quickComment.trim()" class="px-4 py-2.5 rounded-xl bg-[#6d44c5] hover:bg-[#613db1] text-sm disabled:opacity-50">Göndər</button>
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
function dashboard() {
    return {
        stats: {},
        spaces: [],
        spaceStats: [],
        groupedTasks: {},
        spacesLoading: false,
        tasksLoading: false,
        showCreateModal: false,
        creating: false,
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
        newInlineSubtask: { title:'', due_date:'', assignee_ids:[] },
        showChecklistForm: false,
        newChecklistItem: { title: '' },
        filters: { priority: '', status: '', due_days: '30', overdue: false, space_id: '', q: '' },
        newTask: {},
        statusSections: [
            { key:'todo', label:'Görüləcək' },
            { key:'in_progress', label:'İcra olunur' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir' },
            { key:'completed', label:'Tamamlandı' },
            { key:'canceled', label:'Ləğv olundu' },
        ],

        async init() {
            window.addEventListener('open-task-modal', event => {
                const taskId = event.detail?.taskId;
                if (taskId) this.openTaskModal(taskId);
            });
            await this.loadTasks();
        },

        async loadTasks() {
            this.spacesLoading = this.spaces.length === 0;
            this.tasksLoading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value === true) params.set(key, 1);
                    else if (value) params.set(key, value);
                });
                const data = await api('GET', `/dashboard?${params.toString()}`);
                this.stats = data.stats || {};
                this.spaces = data.my_spaces || [];
                this.spaceStats = data.space_stats || [];
                this.groupedTasks = data.grouped_tasks || {};
                this.decorateSpaces();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            } finally {
                this.spacesLoading = false;
                this.tasksLoading = false;
            }
        },

        decorateSpaces() {
            const tasks = Object.values(this.groupedTasks).flat();
            this.spaces = this.spaces.map(space => ({
                ...space,
                overdue_count: tasks.filter(t => Number(t.space_id) === Number(space.id) && t.is_overdue).length,
            }));
        },

        statPart(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
        },

        allDashboardTasks() {
            return Object.values(this.groupedTasks || {}).flat();
        },

        sidebarTasks() {
            return this.allDashboardTasks().slice(0, 12);
        },

        orderedSpaces() {
            const selectedId = Number(this.filters.space_id || 0);
            const list = [...(this.spaces || [])];
            if (!selectedId) return list;

            return list.sort((a, b) => {
                if (Number(a.id) === selectedId) return -1;
                if (Number(b.id) === selectedId) return 1;
                return String(a.name || '').localeCompare(String(b.name || ''), 'az');
            });
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

        statusTotal(status) {
            const fieldMap = {
                todo: 'todo_count',
                in_progress: 'in_progress_count',
                waiting_for_approve: 'waiting_count',
                completed: 'completed_count',
                canceled: 'canceled_count',
            };
            const field = fieldMap[status];
            if (!field) return 0;

            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space[field] || 0), 0);
        },

        overallTotal() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.tasks_total || 0), 0);
        },

        overallCompleted() {
            return this.statusTotal('completed');
        },

        overallOverdue() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.overdue_count || 0), 0);
        },

        overallBoards() {
            return (this.spaceStats || []).reduce((sum, space) => sum + Number(space.boards_count || 0), 0);
        },

        activeTotal() {
            return this.statusTotal('todo') + this.statusTotal('in_progress') + this.statusTotal('waiting_for_approve');
        },

        statPercent(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
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

            return segments.length
                ? `background: conic-gradient(${segments.join(', ')})`
                : 'background: rgba(255,255,255,.12)';
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
            return [...(this.spaceStats || [])].sort((a, b) => Number(b.tasks_total || 0) - Number(a.tasks_total || 0));
        },

        riskySpaces() {
            return [...(this.spaceStats || [])].sort((a, b) => Number(b.overdue_count || 0) - Number(a.overdue_count || 0));
        },

        spaceCompletion(space) {
            return this.statPercent(space?.completed_count || 0, space?.tasks_total || 0);
        },

        sortedByCompletion() {
            return [...(this.spaceStats || [])].sort((a, b) => {
                const diff = this.spaceCompletion(b) - this.spaceCompletion(a);
                if (diff !== 0) return diff;
                return Number(b.tasks_total || 0) - Number(a.tasks_total || 0);
            });
        },

        visibleStatusSections() {
            if (!this.filters.status) return this.statusSections;
            return this.statusSections.filter(s => s.key === this.filters.status);
        },

        openCreateTask() {
            this.newTask = {
                space_id: this.filters.space_id || '',
                title: '',
                description: '',
                priority: 'medium',
                visibility: 'all_members',
                start_date: new Date().toISOString().split('T')[0],
                due_date: '',
                assignee_ids: [],
                require_approval: false,
                deadline_locked: false,
                assigned_by_id: null,
            };
            this.showCreateModal = true;
        },

        async createTask() {
            if (!this.newTask.space_id || !this.newTask.title.trim()) return;
            this.creating = true;
            try {
                await api('POST', `/spaces/${this.newTask.space_id}/tasks`, this.newTask);
                this.showCreateModal = false;
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Tapşırıq yaradıldı!', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            } finally {
                this.creating = false;
            }
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
            this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
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
            this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
            this.newChecklistItem = { title: '' };
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
            if ((this.taskAssigneeSearch || '').length < 2) {
                this.taskAssigneeResults = [];
                return;
            }
            try {
                let url = `/employees/search?q=${encodeURIComponent(this.taskAssigneeSearch)}`;
                if (this.taskDetail?.space_id) url += `&space_id=${this.taskDetail.space_id}`;
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
                await this.loadTasks();
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
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async createInlineSubtask() {
            if (!this.taskDetail?.id || !this.newInlineSubtask.title?.trim()) return;
            try {
                await api('POST', `/tasks/${this.taskDetail.id}/subtasks`, this.newInlineSubtask);
                this.newInlineSubtask = { title:'', due_date:'', assignee_ids:[] };
                this.showInlineSubtaskForm = false;
                await this.refreshTaskDetail();
                await this.loadTasks();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        canEditSubtask(subtask) {
            if (!subtask) return false;
            return !!(subtask.can?.update || subtask.creator?.id === AUTH_USER?.id || (subtask.assignees || []).some(person => person.id === AUTH_USER?.id));
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

        async saveSubtask(subtask) {
            if (!subtask?.id || !subtask.edit?.title?.trim()) return;
            try {
                await api('PUT', `/tasks/${subtask.id}`, {
                    title: subtask.edit.title,
                    due_date: subtask.edit.due_date || null,
                });
                await api('PATCH', `/tasks/${subtask.id}/assignees`, {
                    assignee_ids: subtask.edit.assignee_ids || [],
                });
                subtask.editing = false;
                await this.refreshTaskDetail();
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq yeniləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xeta', type:'error' } }));
            }
        },

        async completeSubtask(subtask) {
            if (!subtask?.id || !this.canEditSubtask(subtask)) return;
            try {
                await api('PATCH', `/tasks/${subtask.id}/order`, { status: 'completed' });
                await this.refreshTaskDetail();
                await this.loadTasks();
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Alt tapşırıq təsdiqləndi', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xeta', type:'error' } }));
            }
        },

        async refreshTaskDetail() {
            if (!this.taskDetail?.id) return;
            this.taskDetail = await api('GET', `/tasks/${this.taskDetail.id}`);
        },

        async createChecklistItem() {
            if (!this.taskDetail?.id || !this.newChecklistItem.title?.trim()) return;
            try {
                const item = await api('POST', `/tasks/${this.taskDetail.id}/checklists`, { title: this.newChecklistItem.title });
                if (!Array.isArray(this.taskDetail.checklists)) this.taskDetail.checklists = [];
                this.taskDetail.checklists.push(item);
                this.newChecklistItem = { title: '' };
                this.showChecklistForm = false;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async toggleChecklistItem(item) {
            if (!item?.id) return;
            try {
                const updated = await api('PATCH', `/checklists/${item.id}/toggle`);
                item.is_done = updated?.is_done ?? updated?.is_completed ?? !item.is_done;
                item.is_completed = updated?.is_completed ?? updated?.is_done ?? item.is_done;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } }));
            }
        },

        async deleteChecklistItem(item) {
            if (!item?.id) return;
            try {
                await api('DELETE', `/checklists/${item.id}`);
                this.taskDetail.checklists = (this.taskDetail.checklists || []).filter(x => x.id !== item.id);
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

        statusLabel(status) {
            return this.statusSections.find(s => s.key === status)?.label || status || '-';
        },

        priorityLabel(priority) {
            return { low:'Aşağı', medium:'Orta', high:'Yüksək', urgent:'Təcili' }[priority] || priority || '';
        },

        statusDotClass(status) {
            return {
                in_progress: 'bg-[#ffa80d]',
                waiting_for_approve: 'bg-[#8b5cf6]',
                completed: 'bg-[#00c83a]',
                todo: 'bg-[#cbd5e1]',
                canceled: 'bg-[#ff3030]',
            }[status] || 'bg-white/50';
        },

        statusTextClass(status) {
            return {
                in_progress: 'text-[#ffa80d]',
                waiting_for_approve: 'text-[#8b5cf6]',
                completed: 'text-[#00c83a]',
                todo: 'text-[#cbd5e1]',
                canceled: 'text-[#ff5757]',
            }[status] || 'text-white';
        },

        taskProgress(task) {
            if (!task) return 0;
            if (task.progress !== undefined && task.progress !== null && task.progress !== '') {
                const value = parseInt(task.progress, 10);
                return Number.isNaN(value) ? 0 : Math.max(0, Math.min(100, value));
            }
            const progress = task.checklist_progress;
            if (progress && typeof progress === 'object') return Number(progress.percentage || 0);
            if (Number.isFinite(Number(progress))) return Number(progress);
            if (task.status === 'completed') return 100;
            if (task.status === 'waiting_for_approve') return 85;
            if (task.status === 'in_progress') return 80;
            return 0;
        },

        formatDate(dt) {
            if (!dt) return '';
                const date = new Date(dt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return `${day}/${month}/${year}`;
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

        async searchEmployees(spaceId = null) {
            if ((this.search || '').length < 2) {
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
