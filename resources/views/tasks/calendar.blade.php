@extends('layouts.app')

@section('title', 'Təqvim')
@section('page-title', 'Təqvim görünüşü')

@section('content')
<div class="min-h-full bg-[#b8b0c3] p-4 lg:p-8" x-data="taskCalendar()" x-init="init()">
    <div class="max-w-7xl mx-auto space-y-5">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-[#142857]">Tapşırıq təqvimi</h1>
                <p class="text-sm text-[#263a6b]/75 mt-1">Son tarixlərə görə aylıq iş yükü və gecikmələr.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button @click="previousMonth()" class="h-10 w-10 rounded-lg bg-white/80 text-[#142857] shadow-sm hover:bg-white" title="Əvvəlki ay">
                    <span aria-hidden="true">&larr;</span>
                </button>
                <button @click="goToday()" class="h-10 px-4 rounded-lg bg-[#142857] text-white text-sm font-semibold shadow-sm hover:bg-[#1e3975]">
                    Bu ay
                </button>
                <button @click="nextMonth()" class="h-10 w-10 rounded-lg bg-white/80 text-[#142857] shadow-sm hover:bg-white" title="Növbəti ay">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1fr_280px]">
            <div class="bg-white/90 border border-white/60 rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-800" x-text="monthTitle"></h2>
                    <div class="text-xs text-slate-500" x-text="`${tasks.length} tapşırıq`"></div>
                </div>

                <div class="grid grid-cols-7 bg-slate-100 text-[11px] font-semibold uppercase text-slate-500">
                    <template x-for="day in weekDays" :key="day">
                        <div class="px-3 py-2 border-r border-white last:border-r-0" x-text="day"></div>
                    </template>
                </div>

                <div class="grid grid-cols-7 min-h-[680px]">
                    <template x-for="day in calendarDays" :key="day.key">
                        <div class="min-h-28 border-r border-b border-slate-100 p-2 bg-white/80"
                             :class="{'bg-slate-50/80 text-slate-400': !day.inMonth, 'ring-2 ring-inset ring-blue-300': day.isToday}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold" x-text="day.date.getDate()"></span>
                                <span x-show="tasksFor(day).length" class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600" x-text="tasksFor(day).length"></span>
                            </div>
                            <div class="space-y-1">
                                <template x-for="task in tasksFor(day).slice(0, 4)" :key="task.id">
                                    <a :href="`/tasks/${task.id}`"
                                       class="block rounded-md border px-2 py-1 text-xs leading-snug hover:shadow-sm"
                                       :class="priorityClass(task)">
                                        <span class="block font-semibold truncate" x-text="`${task.task_code} · ${task.title}`"></span>
                                        <span class="block truncate opacity-80" x-text="task.space?.name || 'Space yoxdur'"></span>
                                    </a>
                                </template>
                                <button x-show="tasksFor(day).length > 4"
                                        @click="selectedDate = formatDateKey(day.date)"
                                        class="text-[11px] text-blue-700 hover:underline">
                                    daha çox...
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="bg-white/90 border border-white/60 rounded-lg shadow-sm p-4">
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Seçilmiş gün</label>
                    <input type="date" x-model="selectedDate" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
                </div>

                <div class="bg-white/90 border border-white/60 rounded-lg shadow-sm p-4">
                    <h3 class="font-semibold text-slate-800 mb-3" x-text="selectedDateLabel"></h3>
                    <div class="space-y-2 max-h-[560px] overflow-y-auto scrollbar-thin">
                        <template x-if="selectedTasks.length === 0">
                            <p class="text-sm text-slate-500">Bu gün üçün tapşırıq yoxdur.</p>
                        </template>
                        <template x-for="task in selectedTasks" :key="`selected-${task.id}`">
                            <a :href="`/tasks/${task.id}`" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-blue-700" x-text="task.task_code"></span>
                                    <span class="text-[11px] rounded-full px-2 py-0.5" :class="statusClass(task.status)" x-text="task.status_label"></span>
                                </div>
                                <p class="text-sm font-semibold text-slate-800 mt-1" x-text="task.title"></p>
                                <p class="text-xs text-slate-500 mt-1" x-text="(task.participants?.executors || []).map(p => p.full_name).join(', ') || 'İcraçı seçilməyib'"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
function taskCalendar() {
    return {
        current: new Date(),
        selectedDate: '',
        tasks: [],
        weekDays: ['B.e', 'Ç.a', 'Ç', 'C.a', 'C', 'Ş', 'B'],

        async init() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            this.selectedDate = this.formatDateKey(new Date());
            await this.loadTasks();
        },

        get monthTitle() {
            return this.current.toLocaleDateString('az-AZ', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const first = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            const startOffset = (first.getDay() + 6) % 7;
            const start = new Date(first);
            start.setDate(first.getDate() - startOffset);

            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(start);
                date.setDate(start.getDate() + index);
                return {
                    date,
                    key: this.formatDateKey(date),
                    inMonth: date.getMonth() === this.current.getMonth(),
                    isToday: this.formatDateKey(date) === this.formatDateKey(new Date()),
                };
            });
        },

        get selectedTasks() {
            return this.tasks.filter(task => task.due_date === this.selectedDate);
        },

        get selectedDateLabel() {
            if (!this.selectedDate) return 'Gün seçilməyib';
            return new Date(`${this.selectedDate}T12:00:00`).toLocaleDateString('az-AZ', {
                day: 'numeric', month: 'long', year: 'numeric'
            });
        },

        async loadTasks() {
            const firstDay = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            const lastDay = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0);
            const params = new URLSearchParams({
                from: this.formatDateKey(firstDay),
                to: this.formatDateKey(lastDay),
            });
            const response = await api('GET', `/tasks/calendar?${params.toString()}`);
            this.tasks = Array.isArray(response) ? response : (response.data || []);
        },

        async previousMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1);
            await this.loadTasks();
        },

        async nextMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1);
            await this.loadTasks();
        },

        async goToday() {
            this.current = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            this.selectedDate = this.formatDateKey(new Date());
            await this.loadTasks();
        },

        tasksFor(day) {
            return this.tasks.filter(task => task.due_date === day.key);
        },

        formatDateKey(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        priorityClass(task) {
            if (task.is_overdue) return 'border-red-200 bg-red-50 text-red-800';
            return {
                urgent: 'border-red-200 bg-red-50 text-red-800',
                high: 'border-orange-200 bg-orange-50 text-orange-800',
                medium: 'border-blue-200 bg-blue-50 text-blue-800',
                low: 'border-slate-200 bg-slate-50 text-slate-700',
            }[task.priority] || 'border-slate-200 bg-slate-50 text-slate-700';
        },

        statusClass(status) {
            return {
                todo: 'bg-slate-100 text-slate-600',
                in_progress: 'bg-blue-100 text-blue-700',
                waiting_for_approve: 'bg-amber-100 text-amber-700',
                completed: 'bg-green-100 text-green-700',
                canceled: 'bg-red-100 text-red-700',
            }[status] || 'bg-slate-100 text-slate-600';
        },
    };
}
</script>
@endpush
