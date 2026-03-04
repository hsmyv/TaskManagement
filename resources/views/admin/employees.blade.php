@extends('layouts.app')
@section('title', 'Əməkdaş İdarəetməsi')
@section('page-title', 'Əməkdaş İdarəetməsi')

@section('content')
<div class="p-6" x-data="adminEmployees()" x-init="load()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">Bütün sistem istifadəçilərini idarə edin</p>
        <button @click="openCreate()"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Əməkdaş
        </button>
    </div>

    {{-- Filterlər --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="text" x-model="filters.q"
                   @input.debounce.400ms="loadEmployees()"
                   placeholder="Ad, soyad, e-poçt axtar..."
                   class="pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-72">
        </div>
        <select x-model="filters.department_id" @change="loadEmployees()"
                class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün departamentlər</option>
            <template x-for="d in departments" :key="d.id">
                <option :value="d.id" x-text="d.name"></option>
            </template>
        </select>
        <select x-model="filters.role" @change="loadEmployees()"
                class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün rollar</option>
            <template x-for="r in roles" :key="r.name">
                <option :value="r.name" x-text="r.label"></option>
            </template>
        </select>
        <select x-model="filters.status" @change="loadEmployees()"
                class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün statuslar</option>
            <option value="active">Aktiv</option>
            <option value="inactive">Passiv</option>
        </select>
        <span class="text-xs text-slate-400 ml-auto" x-text="`${meta.total ?? 0} nəticə`"></span>
    </div>

    {{-- Cədvəl --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Əməkdaş</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departament</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rol</th>
                    <th class="text-center px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Əməliyyat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-if="loading">
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                            <svg class="w-5 h-5 animate-spin mx-auto mb-2 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Yüklənir...
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && employees.length === 0">
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 text-sm">Əməkdaş tapılmadı</td>
                    </tr>
                </template>
                <template x-for="emp in employees" :key="emp.id">
                    <tr class="hover:bg-slate-50 transition-colors">
                        {{-- Əməkdaş --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <img :src="emp.avatar_url" class="w-9 h-9 rounded-full shrink-0" alt="">
                                <div>
                                    <p class="font-medium text-slate-800" x-text="emp.full_name"></p>
                                    <p class="text-xs text-slate-400" x-text="emp.email"></p>
                                </div>
                            </div>
                        </td>
                        {{-- Departament --}}
                        <td class="px-5 py-3.5">
                            <span class="text-sm text-slate-600"
                                  x-text="emp.department?.name ?? '—'"></span>
                            <p class="text-xs text-slate-400" x-text="emp.position ?? ''"></p>
                        </td>
                        {{-- Rol --}}
                        <td class="px-5 py-3.5">
                            <template x-for="r in (emp.roles ?? [])" :key="r">
                                <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full mr-1"
                                      :class="{
                                        'bg-red-100 text-red-700':    r === 'administrator',
                                        'bg-orange-100 text-orange-700': r === 'executive_manager',
                                        'bg-blue-100 text-blue-700':  r === 'senior_manager',
                                        'bg-purple-100 text-purple-700': r === 'middle_manager',
                                        'bg-slate-100 text-slate-600': r === 'employee',
                                      }"
                                      x-text="roleLabel(r)">
                                </span>
                            </template>
                        </td>
                        {{-- Status --}}
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
                                  :class="emp.is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="emp.is_active ? 'bg-green-500' : 'bg-slate-400'"></span>
                                <span x-text="emp.is_active ? 'Aktiv' : 'Passiv'"></span>
                            </span>
                        </td>
                        {{-- Əməliyyatlar --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="openEdit(emp)"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Redaktə et">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="toggleActive(emp)"
                                        class="p-1.5 rounded-lg transition-colors"
                                        :class="emp.is_active
                                            ? 'text-slate-400 hover:text-orange-600 hover:bg-orange-50'
                                            : 'text-slate-400 hover:text-green-600 hover:bg-green-50'"
                                        :title="emp.is_active ? 'Deaktiv et' : 'Aktiv et'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete(emp)"
                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Sil">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        {{-- Pagination --}}
        <div x-show="meta.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
            <span class="text-xs text-slate-400"
                  x-text="`Səhifə ${meta.current_page} / ${meta.last_page}`"></span>
            <div class="flex gap-2">
                <button @click="prevPage()" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 transition-colors">
                    ← Əvvəl
                </button>
                <button @click="nextPage()" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 transition-colors">
                    Sonra →
                </button>
            </div>
        </div>
    </div>

    {{-- ── CREATE / EDIT MODAL ──────────────────────────────────────────── --}}
    <div x-show="showForm" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

            <div class="px-6 py-4 border-b flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="font-semibold text-slate-800"
                    x-text="editMode ? 'Əməkdaşı redaktə et' : 'Yeni əməkdaş əlavə et'"></h2>
                <button @click="showForm = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ad <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="Ad"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Soyad <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.surname" placeholder="Soyad"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ata adı</label>
                    <input type="text" x-model="form.patronymic" placeholder="Ata adı"
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">E-poçt <span class="text-red-500">*</span></label>
                    <input type="email" x-model="form.email" placeholder="email@example.com"
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Şifrə <span x-show="!editMode" class="text-red-500">*</span>
                        <span x-show="editMode" class="text-slate-400 font-normal">(boş buraxsanız dəyişmir)</span>
                    </label>
                    <input type="password" x-model="form.password" placeholder="Şifrə"
                           :required="!editMode"
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Vəzifə</label>
                        <input type="text" x-model="form.position" placeholder="Vəzifə"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefon</label>
                        <input type="text" x-model="form.phone" placeholder="+994 XX XXX XX XX"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Departament</label>
                    <select x-model="form.department_id"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">— Seçin —</option>
                        <template x-for="d in departments" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select x-model="form.role"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">— Seçin —</option>
                        <template x-for="r in roles" :key="r.name">
                            <option :value="r.name" x-text="r.label"></option>
                        </template>
                    </select>
                </div>
                <div x-show="editMode" class="flex items-center gap-3 pt-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-200 peer-checked:bg-blue-600 rounded-full peer
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5
                                    after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-slate-700">Aktiv hesab</span>
                </div>
                <p x-show="error" x-text="error"
                   class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
            </div>

            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showForm = false"
                        class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    Ləğv et
                </button>
                <button @click="submit()" :disabled="saving"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-colors disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span x-text="saving ? 'Saxlanılır...' : (editMode ? 'Saxla' : 'Əlavə et')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── DELETE CONFIRM ──────────────────────────────────────────────── --}}
    <div x-show="showDelete" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Əməkdaş silinsin?</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Bu əməliyyat geri qaytarıla bilməz</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-5 bg-slate-50 rounded-lg px-3 py-2">
                "<span x-text="deleteTarget?.full_name"></span>" hesabı silinəcək.
            </p>
            <div class="flex gap-3">
                <button @click="showDelete = false"
                        class="flex-1 px-4 py-2 text-sm border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Ləğv et
                </button>
                <button @click="deleteEmployee()" :disabled="saving"
                        class="flex-1 px-4 py-2 text-sm bg-red-600 hover:bg-red-500 text-white rounded-xl transition-colors disabled:opacity-50">
                    <span x-text="saving ? 'Silinir...' : 'Sil'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function adminEmployees() {
    return {
        employees:   [],
        departments: [],
        roles:       [],
        loading:     false,
        meta:        { current_page: 1, last_page: 1, total: 0 },
        filters:     { q: '', department_id: '', role: '', status: '' },
        page:        1,

        showForm:     false,
        editMode:     false,
        saving:       false,
        error:        '',
        form:         {},

        showDelete:   false,
        deleteTarget: null,

        roleLabelMap: {
            administrator:    'Administrator',
            executive_manager:'İdarə heyəti üzvü',
            senior_manager:   'Baş menecer',
            middle_manager:   'Menecer',
            employee:         'Əməkdaş',
        },

        async load() {
            await Promise.all([
                this.loadEmployees(),
                this.loadDepartments(),
                this.loadRoles(),
            ]);
        },

        async loadEmployees() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.page });
                if (this.filters.q)             params.set('q',             this.filters.q);
                if (this.filters.department_id) params.set('department_id', this.filters.department_id);
                if (this.filters.role)          params.set('role',          this.filters.role);
                if (this.filters.status)        params.set('status',        this.filters.status);

                const res        = await api('GET', `/admin/employees?${params}`);
                this.employees   = res.data ?? [];
                this.meta        = res.meta  ?? {};
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.loading = false;
            }
        },

        async loadDepartments() {
            const data = await api('GET', '/departments');
            this.departments = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadRoles() {
            const data = await api('GET', '/admin/roles');
            this.roles = Array.isArray(data) ? data : [];
        },

        roleLabel(name) {
            return this.roleLabelMap[name] ?? name;
        },

        prevPage() { if (this.page > 1) { this.page--; this.loadEmployees(); } },
        nextPage() { if (this.page < this.meta.last_page) { this.page++; this.loadEmployees(); } },

        openCreate() {
            this.editMode = false;
            this.error    = '';
            this.form     = { name:'', surname:'', patronymic:'', email:'', password:'',
                              phone:'', position:'', department_id:'', role:'', is_active: true };
            this.showForm = true;
        },

        openEdit(emp) {
            this.editMode = true;
            this.error    = '';
            this.form     = {
                id:            emp.id,
                name:          emp.name,
                surname:       emp.surname,
                patronymic:    emp.patronymic ?? '',
                email:         emp.email,
                password:      '',
                phone:         emp.phone ?? '',
                position:      emp.position ?? '',
                department_id: emp.department?.id ?? '',
                role:          emp.roles?.[0] ?? '',
                is_active:     emp.is_active,
            };
            this.showForm = true;
        },

        async submit() {
            this.error = '';
            if (!this.form.name.trim() || !this.form.surname.trim()) {
                this.error = 'Ad və soyad mütləqdir.'; return;
            }
            if (!this.form.email.trim()) { this.error = 'E-poçt mütləqdir.'; return; }
            if (!this.editMode && !this.form.password) { this.error = 'Şifrə mütləqdir.'; return; }
            if (!this.form.role) { this.error = 'Rol seçilməlidir.'; return; }

            this.saving = true;
            try {
                const payload = { ...this.form };
                if (!payload.password) delete payload.password;
                if (!payload.department_id) payload.department_id = null;

                let result;
                if (this.editMode) {
                    result = await api('PUT', `/admin/employees/${this.form.id}`, payload);
                    const idx = this.employees.findIndex(e => e.id === this.form.id);
                    if (idx !== -1) this.employees[idx] = result;
                } else {
                    result = await api('POST', '/admin/employees', payload);
                    this.employees.unshift(result);
                    this.meta.total = (this.meta.total ?? 0) + 1;
                }

                this.showForm = false;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: this.editMode ? 'Məlumatlar yeniləndi!' : 'Əməkdaş əlavə edildi!', type:'success' }
                }));
            } catch(e) {
                this.error = e.message;
            } finally {
                this.saving = false;
            }
        },

        async toggleActive(emp) {
            try {
                const result = await api('PATCH', `/admin/employees/${emp.id}/toggle`);
                const idx = this.employees.findIndex(e => e.id === emp.id);
                if (idx !== -1) this.employees[idx] = result;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: result.is_active ? 'Aktiv edildi.' : 'Deaktiv edildi.', type:'info' }
                }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        confirmDelete(emp) {
            this.deleteTarget = emp;
            this.showDelete   = true;
        },

        async deleteEmployee() {
            this.saving = true;
            try {
                await api('DELETE', `/admin/employees/${this.deleteTarget.id}`);
                this.employees  = this.employees.filter(e => e.id !== this.deleteTarget.id);
                this.meta.total = Math.max(0, (this.meta.total ?? 1) - 1);
                this.showDelete = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Əməkdaş silindi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.saving = false;
            }
        },
    }
}
</script>
@endpush
