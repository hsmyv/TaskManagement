@extends('layouts.app')
@section('title', 'Rol İdarəetməsi')
@section('page-title', 'Rol İdarəetməsi')

@section('content')
<div class="p-6" x-data="adminRoles()" x-init="load()">

    {{-- Rol kartları --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <template x-for="role in roles" :key="role.id">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                {{-- Rol başlığı --}}
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                             :class="roleColor(role.name).bg">
                            <span class="text-base" x-text="roleIcon(role.name)"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800" x-text="role.label"></p>
                            <p class="text-xs text-slate-400" x-text="`${role.permissions_count} icazə`"></p>
                        </div>
                    </div>
                    <span class="text-xs font-mono bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg"
                          x-text="role.name"></span>
                </div>

                {{-- İcazələr --}}
                <div class="p-5">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="perm in role.permissions" :key="perm">
                            <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-lg font-medium"
                                  :class="permColor(perm)">
                                <span x-text="permLabel(perm)"></span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Həmin roldakı əməkdaşlar --}}
                <div class="px-5 pb-5">
                    <button @click="loadRoleEmployees(role)"
                            class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Bu roldakı əməkdaşlara bax
                    </button>
                </div>
            </div>
        </template>

        {{-- Yükləmə skeleton --}}
        <template x-if="loading">
            <template x-for="i in 5" :key="i">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm h-40 animate-pulse"></div>
            </template>
        </template>
    </div>

    {{-- Statistik xülasə --}}
    <div class="mt-6 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">📊 Rol üzrə əməkdaş sayı</h3>
        <div class="space-y-3">
            <template x-for="role in roles" :key="role.id">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600 w-44 shrink-0" x-text="role.label"></span>
                    <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-500"
                             :class="roleColor(role.name).bar"
                             :style="`width: ${totalEmployees > 0 ? Math.round((role.employee_count ?? 0) / totalEmployees * 100) : 0}%`">
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 w-8 text-right"
                          x-text="role.employee_count ?? 0"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Rol üzrə əməkdaşlar modal ───────────────────────────────────── --}}
    <div x-show="showEmployees" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop
             class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">

            <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
                <div>
                    <h2 class="font-semibold text-slate-800"
                        x-text="`${selectedRole?.label} — Əməkdaşlar`"></h2>
                    <p class="text-xs text-slate-400 mt-0.5"
                       x-text="`${roleEmployees.length} nəfər`"></p>
                </div>
                <button @click="showEmployees = false"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            {{-- Axtarış --}}
            <div class="px-6 py-3 border-b bg-slate-50 shrink-0">
                <input type="text" x-model="empSearch" placeholder="Ad, soyad axtar..."
                       class="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="overflow-y-auto flex-1 divide-y divide-slate-50">
                <template x-if="filteredRoleEmployees.length === 0">
                    <div class="py-8 text-center text-slate-400 text-sm">Əməkdaş tapılmadı</div>
                </template>
                <template x-for="emp in filteredRoleEmployees" :key="emp.id">
                    <div class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50">
                        <img :src="emp.avatar_url" class="w-8 h-8 rounded-full shrink-0" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800" x-text="emp.full_name"></p>
                            <p class="text-xs text-slate-400" x-text="emp.position ?? '—'"></p>
                        </div>
                        <span class="text-xs text-slate-400" x-text="emp.department?.name ?? '—'"></span>
                        <span class="w-2 h-2 rounded-full shrink-0"
                              :class="emp.is_active ? 'bg-green-400' : 'bg-slate-300'"
                              :title="emp.is_active ? 'Aktiv' : 'Passiv'"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function adminRoles() {
    return {
        roles:         [],
        loading:       true,
        showEmployees: false,
        selectedRole:  null,
        roleEmployees: [],
        empSearch:     '',

        get totalEmployees() {
            return this.roles.reduce((s, r) => s + (r.employee_count ?? 0), 0);
        },

        get filteredRoleEmployees() {
            if (!this.empSearch.trim()) return this.roleEmployees;
            const q = this.empSearch.toLowerCase();
            return this.roleEmployees.filter(e =>
                e.full_name.toLowerCase().includes(q) ||
                (e.position ?? '').toLowerCase().includes(q)
            );
        },

        async load() {
            this.loading = true;
            try {
                const data   = await api('GET', '/admin/roles');
                // Hər rol üçün əməkdaş sayını da yüklə
                const counts = await api('GET', '/admin/employees?per_page=1').catch(() => null);
                this.roles   = Array.isArray(data) ? data : [];
                // Sayları ayrıca yüklə
                await this.loadCounts();
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.loading = false;
            }
        },

        async loadCounts() {
            await Promise.all(this.roles.map(async (role) => {
                try {
                    const res = await api('GET', `/admin/employees?role=${role.name}&per_page=1`);
                    role.employee_count = res.meta?.total ?? 0;
                } catch(e) {
                    role.employee_count = 0;
                }
            }));
        },

        async loadRoleEmployees(role) {
            this.selectedRole  = role;
            this.empSearch     = '';
            this.showEmployees = true;
            this.roleEmployees = [];
            try {
                const res          = await api('GET', `/admin/employees?role=${role.name}&per_page=200`);
                this.roleEmployees = res.data ?? [];
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        roleIcon(name) {
            const icons = {
                administrator:    '🔑',
                executive_manager:'👔',
                senior_manager:   '🏆',
                middle_manager:   '📋',
                employee:         '👤',
            };
            return icons[name] ?? '⚙️';
        },

        roleColor(name) {
            const map = {
                administrator:    { bg: 'bg-red-100',    bar: 'bg-red-500' },
                executive_manager:{ bg: 'bg-orange-100', bar: 'bg-orange-500' },
                senior_manager:   { bg: 'bg-blue-100',   bar: 'bg-blue-500' },
                middle_manager:   { bg: 'bg-purple-100', bar: 'bg-purple-500' },
                employee:         { bg: 'bg-slate-100',  bar: 'bg-slate-400' },
            };
            return map[name] ?? { bg: 'bg-slate-100', bar: 'bg-slate-400' };
        },

        permLabel(perm) {
            const labels = {
                'space.create':             '➕ Space yarat',
                'space.update':             '✏️ Space redaktə',
                'space.delete':             '🗑️ Space sil',
                'space.view':               '👁️ Space görüntülə',
                'space.manage_members':     '👥 Üzvlər',
                'task.create':              '➕ Task yarat',
                'task.view.all':            '👁️ Bütün tasklar',
                'task.view.own':            '👁️ Öz taskları',
                'task.update.all':          '✏️ Bütün taskları redaktə',
                'task.update.own':          '✏️ Öz taskını redaktə',
                'task.delete.all':          '🗑️ Bütün taskları sil',
                'task.delete.own':          '🗑️ Öz taskını sil',
                'task.assign':              '👤 Məsul təyin et',
                'task.approve':             '✅ Təsdiqlə',
                'task.update.deadline.any': '📅 Deadline dəyiş',
                'comment.create':           '💬 Şərh yaz',
                'comment.delete.own':       '🗑️ Öz şərhini sil',
                'comment.delete.any':       '🗑️ Hər şərhi sil',
                'attachment.upload':        '📎 Fayl yüklə',
                'attachment.delete.own':    '🗑️ Öz faylını sil',
                'attachment.delete.any':    '🗑️ Hər faylı sil',
                'admin.access':             '🔐 Admin panel',
                'admin.manage_roles':       '⚙️ Rol idarəetmə',
                'admin.manage_employees':   '👥 Əməkdaş idarəetmə',
            };
            return labels[perm] ?? perm;
        },

        permColor(perm) {
            if (perm.startsWith('admin'))      return 'bg-red-50 text-red-700';
            if (perm.startsWith('space'))      return 'bg-blue-50 text-blue-700';
            if (perm.includes('delete'))       return 'bg-orange-50 text-orange-700';
            if (perm.includes('view'))         return 'bg-slate-100 text-slate-600';
            if (perm.includes('approve'))      return 'bg-green-50 text-green-700';
            return 'bg-purple-50 text-purple-700';
        },
    }
}
</script>
@endpush
