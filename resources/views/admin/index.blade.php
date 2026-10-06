@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="p-6" x-data="adminDashboard()" x-init="load()">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-6">
        <div>
            <p class="text-sm text-slate-500">Sistem üzrə ümumi vəziyyət, gecikmələr və son aktivliklər.</p>
        </div>
        <button @click="load()"
                class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 disabled:opacity-60"
                :disabled="loading">
            <span x-text="loading ? 'Yüklənir...' : 'Yenilə'"></span>
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <template x-for="card in cards" :key="card.key">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="card.label"></p>
                        <p class="text-3xl font-semibold text-slate-800 mt-2" x-text="card.value"></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         :class="card.bg">
                        <span class="text-lg" x-text="card.icon"></span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-3" x-text="card.note"></p>
            </div>
        </template>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_1fr] mb-6">
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Status bölgüsü</h2>
                <span class="text-xs text-slate-400" x-text="`${totalTasks} task`"></span>
            </div>
            <div class="p-5 space-y-4">
                <template x-for="item in statusRows" :key="item.key">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-600" x-text="item.label"></span>
                            <span class="font-semibold text-slate-800" x-text="item.value"></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="item.bar" :style="`width: ${item.percent}%`"></div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Son audit loglar</h2>
                <a href="{{ route('admin.audit-logs') }}" class="text-xs text-blue-600 hover:underline">Hamısına bax</a>
            </div>
            <div class="divide-y divide-slate-50">
                <template x-if="!recentLogs.length">
                    <div class="p-5 text-sm text-slate-400 text-center">Log yoxdur</div>
                </template>
                <template x-for="log in recentLogs" :key="log.id">
                    <div class="px-5 py-3 flex items-start gap-3">
                        <img :src="log.employee?.avatar_url" class="w-8 h-8 rounded-full shrink-0" alt="">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700">
                                <b x-text="log.employee?.full_name ?? '—'"></b>
                                <span x-text="actionLabel(log.action)"></span>
                            </p>
                            <p class="text-xs text-slate-400 truncate" x-text="log.task?.title || log.board?.name || log.meta?.title || log.entity_type"></p>
                        </div>
                        <span class="text-xs text-slate-400 whitespace-nowrap" x-text="formatDate(log.created_at)"></span>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Gecikmiş tapşırıqlar</h2>
                <span class="text-xs text-red-600 font-semibold" x-text="`${overdue.length} göstərilir`"></span>
            </div>
            <div class="divide-y divide-slate-50">
                <template x-if="!overdue.length">
                    <div class="p-5 text-sm text-slate-400 text-center">Gecikmiş tapşırıq yoxdur</div>
                </template>
                <template x-for="task in overdue" :key="`overdue-${task.id}`">
                    <a :href="`/tasks/${task.id}`" class="block px-5 py-3 hover:bg-red-50/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate" x-text="`${task.task_code} · ${task.title}`"></p>
                                <p class="text-xs text-slate-500 mt-1" x-text="task.space?.name || 'Space yoxdur'"></p>
                            </div>
                            <span class="text-xs text-red-700 bg-red-50 rounded-full px-2.5 py-1" x-text="formatDateOnly(task.due_date)"></span>
                        </div>
                    </a>
                </template>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">1 gün qalan tapşırıqlar</h2>
                <span class="text-xs text-amber-600 font-semibold" x-text="`${dueTomorrow.length} göstərilir`"></span>
            </div>
            <div class="divide-y divide-slate-50">
                <template x-if="!dueTomorrow.length">
                    <div class="p-5 text-sm text-slate-400 text-center">Sabah deadline olan tapşırıq yoxdur</div>
                </template>
                <template x-for="task in dueTomorrow" :key="`due-${task.id}`">
                    <a :href="`/tasks/${task.id}`" class="block px-5 py-3 hover:bg-amber-50/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate" x-text="`${task.task_code} · ${task.title}`"></p>
                                <p class="text-xs text-slate-500 mt-1" x-text="task.space?.name || 'Space yoxdur'"></p>
                            </div>
                            <span class="text-xs text-amber-700 bg-amber-50 rounded-full px-2.5 py-1" x-text="formatDateOnly(task.due_date)"></span>
                        </div>
                    </a>
                </template>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/dashboard.js') }}"></script>
@endpush
