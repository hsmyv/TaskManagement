@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="p-6" x-data="dashboard()" x-init="load()">

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <template x-for="stat in statCards" :key="stat.key">
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="stat.bg">
                        <span class="text-lg" x-text="stat.icon"></span>
                    </div>
                    <span class="text-2xl font-bold text-slate-800" x-text="stats[stat.key] ?? 0"></span>
                </div>
                <p class="text-sm font-medium text-slate-500" x-text="stat.label"></p>
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Tezliklə bitəcəklər --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">⏰ Tezliklə bitəcək tapşırıqlar</h2>
                <span class="text-xs text-slate-400">Son 7 gün</span>
            </div>
            <div class="divide-y divide-slate-50">
                <template x-if="dueSoon.length === 0">
                    <div class="py-10 text-center text-slate-400 text-sm">Heç bir tapşırıq yoxdur</div>
                </template>
                <template x-for="task in dueSoon" :key="task.id">
                    <a :href="`/tasks/${task.id}`"
                       class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 transition-colors">
                        <span class="w-2 h-2 rounded-full shrink-0"
                              :class="{
                                'bg-slate-400': task.priority==='low',
                                'bg-blue-500': task.priority==='medium',
                                'bg-orange-500': task.priority==='high',
                                'bg-red-500': task.priority==='urgent'
                              }"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate" x-text="task.title"></p>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="task.space?.name"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-medium px-2 py-1 rounded-lg"
                                  :class="getDueDateClass(task.due_date)"
                                  x-text="formatDate(task.due_date)"></span>
                        </div>
                    </a>
                </template>
            </div>
        </div>

        {{-- Gecikmiş tapşırıqlar --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">🔴 Gecikmiş tapşırıqlar</h2>
            </div>
            <div class="divide-y divide-slate-50">
                <template x-if="overdue.length === 0">
                    <div class="py-8 text-center text-slate-400 text-sm">Gecikmiş tapşırıq yoxdur 🎉</div>
                </template>
                <template x-for="task in overdue" :key="task.id">
                    <a :href="`/tasks/${task.id}`"
                       class="flex items-center gap-3 px-5 py-3 hover:bg-red-50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate" x-text="task.title"></p>
                            <p class="text-xs text-red-500 mt-0.5" x-text="'Son tarix: ' + formatDate(task.due_date)"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>

    {{-- My Spaces --}}
    <div class="mt-6">
        <h2 class="font-semibold text-slate-800 mb-4">📁 Mənim Space-lərim</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="space in spaces" :key="space.id">
                <a :href="`/spaces/${space.id}`"
                   class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5 group">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             :style="`background: ${space.color}20`">
                            <div class="w-4 h-4 rounded-full" :style="`background: ${space.color}`"></div>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 group-hover:text-blue-600 transition-colors" x-text="space.name"></p>
                            <p class="text-xs text-slate-400" x-text="space.description"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span x-text="`${space.tasks_count || 0} tapşırıq`"></span>
                        <span x-text="`${space.members_count || 0} üzv`"></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        stats: {},
        dueSoon: [],
        overdue: [],
        spaces: [],
        statCards: [
            { key: 'todo',                label: 'Görüləcək',         icon: '📋', bg: 'bg-slate-100' },
            { key: 'in_progress',         label: 'İcra olunur',       icon: '🔄', bg: 'bg-blue-100' },
            { key: 'waiting_for_approve', label: 'Təsdiq gözləyir',   icon: '⏳', bg: 'bg-yellow-100' },
            { key: 'completed',           label: 'Tamamlandı',        icon: '✅', bg: 'bg-green-100' },
            { key: 'overdue',             label: 'Gecikmiş',          icon: '🔴', bg: 'bg-red-100' },
        ],

        async load() {
            try {
                const data = await api('GET', '/dashboard');
                this.stats   = data.stats;
                this.dueSoon = data.due_soon;
                this.overdue = data.overdue;
                this.spaces  = data.my_spaces;
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            }
        },

        formatDate(dt) {
            if (!dt) return '';
            return new Date(dt).toLocaleDateString('az-AZ', { day: 'numeric', month: 'short' });
        },

        getDueDateClass(dt) {
            if (!dt) return 'bg-slate-100 text-slate-500';
            const days = (new Date(dt) - new Date()) / 86400000;
            if (days < 0)  return 'bg-red-100 text-red-700';
            if (days <= 2) return 'bg-orange-100 text-orange-700';
            return 'bg-slate-100 text-slate-600';
        }
    }
}
</script>
@endpush
