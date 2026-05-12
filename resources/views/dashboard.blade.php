@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="p-6" x-data="dashboard()" x-init="load()">



    {{-- My Spaces --}}
    <div class="mt-6">
        <h2 class="font-semibold text-slate-800 mb-4">Departamentlər</h2>
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
