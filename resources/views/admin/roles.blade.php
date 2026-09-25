@extends('layouts.app')
@section('title', 'Rol İdarəetməsi')
@section('page-title', 'Rol İdarəetməsi')

@section('content')
<div class="p-6" x-data="adminRoles()" x-init="load()">

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <template x-for="role in roles" :key="role.id">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

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

        <template x-if="loading">
            <template x-for="i in 5" :key="i">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm h-40 animate-pulse"></div>
            </template>
        </template>
    </div>

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
    <script src="{{ asset('js/admin/roles.js') }}"></script>
@endpush

