@extends('layouts.app')
@section('title', 'Statistika')
@section('page-title', 'Statistika')

@section('content')
<div class="min-h-[calc(100vh-74px)] bg-gradient-to-br from-[#132e69] via-[#1d2f67] to-[#39245f] px-3 sm:px-5 lg:px-8 py-5 text-white" x-data="dashboardStatistics()" x-init="init()">
    <section class="max-w-7xl mx-auto space-y-6">
        <style>
            .statistics-scroll {
                scrollbar-width: thin;
                scrollbar-color: rgba(147, 178, 231, .75) rgba(10, 27, 65, .18);
            }
            .statistics-scroll::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .statistics-scroll::-webkit-scrollbar-track {
                background: rgba(10, 27, 65, .18);
                border-radius: 999px;
            }
            .statistics-scroll::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, rgba(134, 171, 232, .9), rgba(29, 94, 170, .9));
                border-radius: 999px;
            }
        </style>

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
                                <div class="flex items-center justify-between gap-3 text-sm min-w-0">
                                    <span class="flex items-center gap-2 text-white/72 min-w-0">
                                        <i class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background:' + statusColor(s.key)"></i>
                                        <span class="truncate" x-text="s.label"></span>
                                    </span>
                                    <b class="shrink-0" x-text="statusTotal(s.key)"></b>
                                </div>
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
                                    <b class="text-[#ffaaa0] shrink-0" x-text="space.overdue_count || 0"></b>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div class="rounded-[16px] bg-[#142d64]/95 border border-white/10 p-5 min-w-0">
                    <h2 class="font-semibold mb-4 break-words">Departament yükü</h2>
                    <div class="statistics-scroll max-h-[420px] overflow-y-auto pr-1 space-y-4">
                        <template x-for="space in sortedSpaceStats()" :key="'stat-workload-' + space.id">
                            <div class="min-w-0">
                                <div class="flex items-center justify-between gap-3 text-sm mb-2 min-w-0">
                                    <span class="truncate text-white/78" x-text="space.name"></span>
                                    <span class="text-white/50 shrink-0" x-text="(space.tasks_total || 0) + ' tapşırıq'"></span>
                                </div>
                                <div class="h-3 rounded-full bg-[#0d244f] overflow-hidden flex">
                                    <div :style="'width:' + statPart(space.todo_count, space.tasks_total) + '%; background:' + statusColor('todo')"></div>
                                    <div :style="'width:' + statPart(space.in_progress_count, space.tasks_total) + '%; background:' + statusColor('in_progress')"></div>
                                    <div :style="'width:' + statPart(space.waiting_count, space.tasks_total) + '%; background:' + statusColor('waiting_for_approve')"></div>
                                    <div :style="'width:' + statPart(space.completed_count, space.tasks_total) + '%; background:' + statusColor('completed')"></div>
                                    <div :style="'width:' + statPart(space.canceled_count, space.tasks_total) + '%; background:' + statusColor('canceled')"></div>
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
                                    <span class="truncate" x-text="(space.completed_count || 0) + ' tamamlandı'"></span>
                                    <span class="shrink-0" x-text="(space.boards_count || 0) + ' layihə'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function dashboardStatistics() {
    return {
        loading: false,
        spaceStats: [],
        statusSections: [
            { key:'todo', label:'Görüləcək' },
            { key:'in_progress', label:'İcra olunur' },
            { key:'waiting_for_approve', label:'Təsdiq gözləyir' },
            { key:'completed', label:'Tamamlandı' },
            { key:'canceled', label:'Ləğv olundu' },
        ],

        async init() {
            await this.loadStatistics();
        },

        async loadStatistics() {
            this.loading = true;
            try {
                const data = await api('GET', '/dashboard');
                this.spaceStats = data.space_stats || [];
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Xəta', type: 'error' } }));
            } finally {
                this.loading = false;
            }
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
            return field ? this.spaceStats.reduce((sum, space) => sum + Number(space[field] || 0), 0) : 0;
        },

        overallTotal() {
            return this.spaceStats.reduce((sum, space) => sum + Number(space.tasks_total || 0), 0);
        },

        overallBoards() {
            return this.spaceStats.reduce((sum, space) => sum + Number(space.boards_count || 0), 0);
        },

        overallCompleted() {
            return this.statusTotal('completed');
        },

        overallOverdue() {
            return this.spaceStats.reduce((sum, space) => sum + Number(space.overdue_count || 0), 0);
        },

        activeTotal() {
            return this.statusTotal('todo') + this.statusTotal('in_progress') + this.statusTotal('waiting_for_approve');
        },

        statPart(value, total) {
            total = Number(total || 0);
            if (!total) return 0;
            return Math.max(0, Math.min(100, Math.round((Number(value || 0) / total) * 100)));
        },

        statPercent(value, total) {
            return this.statPart(value, total);
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

            return segments.length ? `background: conic-gradient(${segments.join(', ')})` : 'background: rgba(255,255,255,.12)';
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
            return [...this.spaceStats].sort((a, b) => Number(b.tasks_total || 0) - Number(a.tasks_total || 0));
        },

        riskySpaces() {
            return [...this.spaceStats].sort((a, b) => Number(b.overdue_count || 0) - Number(a.overdue_count || 0));
        },

        spaceCompletion(space) {
            return this.statPercent(space?.completed_count || 0, space?.tasks_total || 0);
        },

        sortedByCompletion() {
            return [...this.spaceStats].sort((a, b) => {
                const diff = this.spaceCompletion(b) - this.spaceCompletion(a);
                if (diff !== 0) return diff;
                return Number(b.tasks_total || 0) - Number(a.tasks_total || 0);
            });
        },
    };
}
</script>
@endpush
