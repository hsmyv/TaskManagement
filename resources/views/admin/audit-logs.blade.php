@extends('layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')
<div class="p-6" x-data="adminAuditLogs()" x-init="load()">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between mb-5">
        <div>
            <p class="text-sm text-slate-500">Tapşırıq, board və board list dəyişikliklərinin sistem tarixçəsi.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="resetFilters()"
                    class="h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50">
                Sıfırla
            </button>
            <button @click="loadLogs()"
                    class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500">
                Yenilə
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Axtarış</label>
                <input x-model="filters.q" @input.debounce.500ms="loadLogs()"
                       placeholder="Başlıq, action, əməkdaş..."
                       class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Növ</label>
                <select x-model="filters.entity_type" @change="loadLogs()"
                        class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Hamısı</option>
                    <option value="task">Tapşırıq</option>
                    <option value="board">Board</option>
                    <option value="board_list">Board list</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Əməliyyat</label>
                <select x-model="filters.action" @change="loadLogs()"
                        class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Hamısı</option>
                    <template x-for="action in options.actions" :key="action">
                        <option :value="action" x-text="actionLabel(action)"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Task ID</label>
                <input type="number" min="1" x-model="filters.task_id" @input.debounce.500ms="loadLogs()"
                       placeholder="Məs: 15"
                       class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Board</label>
                <select x-model="filters.board_id" @change="loadLogs()"
                        class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Hamısı</option>
                    <template x-for="board in options.boards" :key="board.id">
                        <option :value="board.id" x-text="`${board.name} #${board.id}`"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Başlanğıc</label>
                <input type="date" x-model="filters.from" @change="loadLogs()"
                       class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Son</label>
                <input type="date" x-model="filters.to" @change="loadLogs()"
                       class="w-full h-10 rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-700">Tarixçə</p>
            <span class="text-xs text-slate-400" x-text="`${meta.total ?? 0} nəticə`"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Vaxt</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">İcraçı</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Əməliyyat</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Obyekt</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Board / Space</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Detallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">Yüklənir...</td>
                        </tr>
                    </template>
                    <template x-if="!loading && logs.length === 0">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">Log tapılmadı</td>
                        </tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500" x-text="formatDate(log.created_at)"></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <img :src="log.employee?.avatar_url" class="w-8 h-8 rounded-full" alt="">
                                    <span class="font-medium text-slate-700" x-text="log.employee?.full_name ?? '—'"></span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                      :class="actionClass(log.action)"
                                      x-text="actionLabel(log.action)"></span>
                            </td>
                            <td class="px-5 py-3 min-w-56">
                                <template x-if="log.entity_type === 'task'">
                                    <div>
                                        <a :href="`/tasks/${log.entity_id}`" class="font-semibold text-blue-700 hover:underline"
                                           x-text="log.task?.task_code ?? (log.meta?.task_code ?? `Task #${log.entity_id}`)"></a>
                                        <p class="text-xs text-slate-500 mt-1" x-text="log.task?.title ?? log.meta?.title ?? '—'"></p>
                                    </div>
                                </template>
                                <template x-if="log.entity_type !== 'task'">
                                    <div>
                                        <p class="font-semibold text-slate-700" x-text="entityLabel(log.entity_type) + ` #${log.entity_id}`"></p>
                                        <p class="text-xs text-slate-500 mt-1" x-text="log.meta?.name ?? log.meta?.title ?? '—'"></p>
                                    </div>
                                </template>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-slate-700" x-text="log.board?.name ?? '—'"></p>
                                <p class="text-xs text-slate-400 mt-1" x-text="log.space?.name ?? '—'"></p>
                            </td>
                            <td class="px-5 py-3 min-w-72">
                                <button @click="toggleDetails(log.id)"
                                        class="text-xs text-blue-600 hover:underline mb-2">
                                    Detalları göstər
                                </button>
                                <pre x-show="expanded.includes(log.id)"
                                     class="text-xs whitespace-pre-wrap bg-slate-900 text-slate-100 rounded-xl p-3 max-w-xl overflow-auto"
                                     x-text="formatMeta(log.meta)"></pre>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="meta.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
            <span class="text-xs text-slate-400" x-text="`Səhifə ${meta.current_page} / ${meta.last_page}`"></span>
            <div class="flex gap-2">
                <button @click="prevPage()" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40">
                    Əvvəl
                </button>
                <button @click="nextPage()" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40">
                    Sonra
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
    <script src="{{ asset('js/admin/audit_logs.js') }}"></script>
@endpush
