@extends('layouts.app')
@section('title', 'Space İdarəetməsi')
@section('page-title', 'Space İdarəetməsi')

@section('content')
<div class="p-6" x-data="adminSpaces()" x-init="load()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-slate-500 mt-0.5">Bütün Team Space-ləri idarə edin</p>
        </div>
        <button @click="openCreate()"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Space
        </button>
    </div>

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-4">
        <input type="text" x-model="search" placeholder="Axtar..."
               class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
        <select x-model="filterDept"
                class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün departamentlər</option>
            <template x-for="d in departments" :key="d.id">
                <option :value="d.id" x-text="d.name"></option>
            </template>
        </select>
        <select x-model="filterStatus"
                class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Bütün statuslar</option>
            <option value="1">Aktiv</option>
            <option value="0">Passiv</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Space</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departament</th>
                    <th class="text-center px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Üzv</th>
                    <th class="text-center px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tapşırıq</th>
                    <th class="text-center px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Əməliyyat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-if="filteredSpaces.length === 0">
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 text-sm">
                            Space tapılmadı
                        </td>
                    </tr>
                </template>
                <template x-for="space in filteredSpaces" :key="space.id">
                    <tr class="hover:bg-slate-50 transition-colors">
                        {{-- Space adı + rəng --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                     :style="`background: ${space.color}25`">
                                    <div class="w-3.5 h-3.5 rounded-full" :style="`background: ${space.color}`"></div>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800" x-text="space.name"></p>
                                    <p class="text-xs text-slate-400" x-text="space.description || '—'"></p>
                                </div>
                            </div>
                        </td>
                        {{-- Departament --}}
                        <td class="px-5 py-4">
                            <span x-show="space.department"
                                  class="inline-flex items-center gap-1.5 text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg font-medium"
                                  x-text="space.department?.name ?? '—'"></span>
                            <span x-show="!space.department" class="text-slate-400 text-xs">—</span>
                        </td>
                        {{-- Üzv sayı --}}
                        <td class="px-5 py-4 text-center">
                            <span class="font-semibold text-slate-700" x-text="space.members_count ?? 0"></span>
                        </td>
                        {{-- Tapşırıq sayı --}}
                        <td class="px-5 py-4 text-center">
                            <span class="font-semibold text-slate-700" x-text="space.tasks_count ?? 0"></span>
                        </td>
                        {{-- Status --}}
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
                                  :class="space.is_active
                                    ? 'bg-green-50 text-green-700'
                                    : 'bg-slate-100 text-slate-500'">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="space.is_active ? 'bg-green-500' : 'bg-slate-400'"></span>
                                <span x-text="space.is_active ? 'Aktiv' : 'Passiv'"></span>
                            </span>
                        </td>
                        {{-- Əməliyyatlar --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit(space)"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Redaktə et">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="openMembers(space)"
                                        class="p-1.5 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Üzvlər">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                                <button @click="toggleActive(space)"
                                        class="p-1.5 rounded-lg transition-colors"
                                        :class="space.is_active
                                            ? 'text-slate-400 hover:text-orange-600 hover:bg-orange-50'
                                            : 'text-slate-400 hover:text-green-600 hover:bg-green-50'"
                                        :title="space.is_active ? 'Deaktiv et' : 'Aktiv et'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete(space)"
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
    </div>

    {{-- ── CREATE / EDIT MODAL ─────────────────────────────────────────── --}}
    <div x-show="showForm"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-slate-800"
                    x-text="editMode ? 'Space redaktə et' : 'Yeni Space yarat'"></h2>
                <button @click="showForm = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                {{-- Ad --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ad <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name"
                           placeholder="Space adı..."
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                {{-- Təsvir --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Təsvir</label>
                    <textarea x-model="form.description" rows="2" placeholder="Qısa təsvir..."
                              class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
                </div>
                {{-- Departament --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Departament</label>
                    <select x-model="form.department_id"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">— Seçin (opsional) —</option>
                        <template x-for="d in departments" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>
                {{-- Rəng --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Rəng</label>
                    <div class="flex items-center gap-3">
                        {{-- Hazır rənglər --}}
                        <div class="flex gap-2">
                            <template x-for="c in presetColors" :key="c">
                                <button type="button" @click="form.color = c"
                                        class="w-7 h-7 rounded-full border-2 transition-all"
                                        :style="`background: ${c}`"
                                        :class="form.color === c ? 'border-slate-800 scale-110' : 'border-transparent'">
                                </button>
                            </template>
                        </div>
                        {{-- Custom rəng --}}
                        <input type="color" x-model="form.color"
                               class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200 p-0.5">
                        <span class="text-xs text-slate-400" x-text="form.color"></span>
                    </div>
                </div>
                {{-- Aktiv/Passiv (yalnız edit modda) --}}
                <div x-show="editMode" class="flex items-center gap-3 pt-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-200 peer-checked:bg-blue-600 rounded-full peer
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5
                                    after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-slate-700">Aktiv Space</span>
                </div>
                {{-- Xəta --}}
                <p x-show="error" x-text="error"
                   class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
            </div>

            <div class="px-6 pb-6 flex justify-end gap-3">
                <button @click="showForm = false"
                        class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    Ləğv et
                </button>
                <button @click="submit()"
                        :disabled="saving || !form.name.trim()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-colors disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span x-text="saving ? 'Saxlanılır...' : (editMode ? 'Saxla' : 'Yarat')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MEMBERS MODAL ───────────────────────────────────────────────── --}}
    <div x-show="showMembers"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop
             class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[80vh] flex flex-col">

            <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
                <div>
                    <h2 class="font-semibold text-slate-800" x-text="`${selectedSpace?.name} — Üzvlər`"></h2>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="`${members.length} üzv`"></p>
                </div>
                <button @click="showMembers = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            {{-- Üzv əlavə et --}}
            <div class="px-6 py-3 border-b bg-slate-50 shrink-0" x-data="{ empSearch: '', showSuggestions: false }">
                {{-- Axtarış + Rol + Düymə --}}
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text"
                               x-model="empSearch"
                               @input="showSuggestions = empSearch.length > 1"
                               @keydown.escape="showSuggestions = false"
                               placeholder="Ad və ya soyad ilə axtar..."
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

                        {{-- Seçilmiş əməkdaş --}}
                        <div x-show="newMember.employee_id && !showSuggestions"
                             class="absolute inset-y-0 right-2 flex items-center">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md"
                                  x-text="newMember._name"></span>
                            <button @click="newMember.employee_id = ''; newMember._name = ''; empSearch = ''"
                                    class="ml-1 text-slate-400 hover:text-red-500">✕</button>
                        </div>

                        {{-- Dropdown nəticələr --}}
                        <div x-show="showSuggestions && filteredAvailable(empSearch).length > 0"
                             @click.outside="showSuggestions = false"
                             class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-52 overflow-y-auto">
                            <template x-for="e in filteredAvailable(empSearch)" :key="e.id">
                                <button type="button"
                                        @click="newMember.employee_id = e.id; newMember._name = e.full_name; empSearch = e.full_name; showSuggestions = false"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-left transition-colors">
                                    <img :src="e.avatar_url" class="w-7 h-7 rounded-full shrink-0" alt="">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate" x-text="e.full_name"></p>
                                        <p class="text-xs text-slate-400 truncate" x-text="`${e.position ?? '—'} ${e.department?.name ? '· ' + e.department.name : ''}`"></p>
                                    </div>
                                </button>
                            </template>
                            <div x-show="filteredAvailable(empSearch).length === 0"
                                 class="px-4 py-3 text-sm text-slate-400 text-center">
                                Nəticə tapılmadı
                            </div>
                        </div>
                    </div>

                    {{-- <select x-model="newMember.space_role"
                            class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shrink-0">
                        <option value="employee">Əməkdaş</option>
                        <option value="middle_manager">Orta rəhbər</option>
                        <option value="senior_manager">Baş rəhbər</option>
                    </select> --}}
                    <button @click="addMember()"
                            :disabled="!newMember.employee_id"
                            class="px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors shrink-0">
                        Əlavə et
                    </button>
                </div>
            </div>

            {{-- Üzv siyahısı --}}
            <div class="overflow-y-auto flex-1 divide-y divide-slate-50">
                <template x-if="members.length === 0">
                    <div class="py-8 text-center text-slate-400 text-sm">Üzv yoxdur</div>
                </template>
                <template x-for="m in members" :key="m.id">
                    <div class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50">
                        <img :src="m.avatar_url" class="w-8 h-8 rounded-full shrink-0" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800" x-text="m.full_name"></p>
                            <p class="text-xs text-slate-400" x-text="m.position ?? '—'"></p>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                              :class="{
                                'bg-blue-100 text-blue-700':   m.pivot?.space_role === 'senior_manager',
                                'bg-purple-100 text-purple-700': m.pivot?.space_role === 'middle_manager',
                                'bg-slate-100 text-slate-600':   m.pivot?.space_role === 'employee',
                              }"
                              x-text="{
                                senior_manager: 'Baş rəhbər',
                                middle_manager: 'Orta rəhbər',
                                employee: 'Əməkdaş'
                              }[m.pivot?.space_role] ?? m.pivot?.space_role">
                        </span>
                        <button @click="removeMember(m)"
                                class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors ml-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── DELETE CONFIRM ──────────────────────────────────────────────── --}}
    <div x-show="showDelete"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Space silinsin?</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Bu əməliyyat geri qaytarıla bilməz</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-5 bg-slate-50 rounded-lg px-3 py-2">
                "<span x-text="deleteTarget?.name"></span>" Space-i bütün üzvlik məlumatları ilə birgə silinəcək.
                Tapşırıqlar silinməyəcək.
            </p>
            <div class="flex gap-3">
                <button @click="showDelete = false"
                        class="flex-1 px-4 py-2 text-sm border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Ləğv et
                </button>
                <button @click="deleteSpace()"
                        :disabled="saving"
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
function adminSpaces() {
    return {
        spaces:      [],
        departments: [],
        allEmployees:[],
        members:     [],
        search:      '',
        filterDept:  '',
        filterStatus:'',

        // Formlar
        showForm:    false,
        editMode:    false,
        saving:      false,
        error:       '',
        form:        {},

        // Members modal
        showMembers:      false,
        selectedSpace:    null,
        newMember:        { employee_id: '', space_role: 'employee', _name: '' },

        // Delete confirm
        showDelete:   false,
        deleteTarget: null,

        // Hazır rəng palitras
        presetColors: ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#EC4899','#06B6D4','#64748B'],

        async load() {
            await Promise.all([
                this.loadSpaces(),
                this.loadDepartments(),
                this.loadAllEmployees(),
            ]);
        },

        async loadSpaces() {
            const data = await api('GET', '/spaces');
            this.spaces = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadDepartments() {
            const data = await api('GET', '/departments');
            this.departments = Array.isArray(data) ? data : (data?.data ?? []);
        },

        async loadAllEmployees() {
            const data = await api('GET', '/employees');
            this.allEmployees = Array.isArray(data) ? data : (data?.data ?? []);
        },

        // ── Filter ──────────────────────────────────────────────────────
        get filteredSpaces() {
            return this.spaces.filter(s => {
                const matchSearch = !this.search
                    || s.name.toLowerCase().includes(this.search.toLowerCase())
                    || (s.description ?? '').toLowerCase().includes(this.search.toLowerCase());
                const matchDept   = !this.filterDept   || s.department_id == this.filterDept;
                const matchStatus = this.filterStatus === ''
                    || (this.filterStatus === '1' ? s.is_active : !s.is_active);
                return matchSearch && matchDept && matchStatus;
            });
        },

        // ── CREATE ───────────────────────────────────────────────────────
        openCreate() {
            this.editMode = false;
            this.error    = '';
            this.form     = { name:'', description:'', color:'#3B82F6', department_id:'', is_active: true };
            this.showForm = true;
        },

        // ── EDIT ─────────────────────────────────────────────────────────
        openEdit(space) {
            this.editMode = true;
            this.error    = '';
            this.form     = {
                id:            space.id,
                name:          space.name,
                description:   space.description ?? '',
                color:         space.color,
                department_id: space.department_id ?? '',
                is_active:     space.is_active,
            };
            this.showForm = true;
        },

        // ── SUBMIT (create / update) ──────────────────────────────────────
        async submit() {
            this.error = '';
            if (!this.form.name.trim()) { this.error = 'Ad mütləqdir.'; return; }
            this.saving = true;
            try {
                const payload = {
                    name:          this.form.name,
                    description:   this.form.description || null,
                    color:         this.form.color,
                    department_id: this.form.department_id || null,
                };

                if (this.editMode) {
                    payload.is_active = this.form.is_active;
                    const updated = await api('PUT', `/spaces/${this.form.id}`, payload);
                    const idx = this.spaces.findIndex(s => s.id === this.form.id);
                    if (idx !== -1) this.spaces[idx] = updated;
                } else {
                    const created = await api('POST', '/spaces', payload);
                    this.spaces.unshift(created);
                }

                this.showForm = false;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: this.editMode ? 'Space yeniləndi!' : 'Space yaradıldı!', type: 'success' }
                }));
            } catch(e) {
                this.error = e.message || 'Xəta baş verdi.';
            } finally {
                this.saving = false;
            }
        },

        // ── TOGGLE ACTIVE ─────────────────────────────────────────────────
        async toggleActive(space) {
            try {
                const updated = await api('PUT', `/spaces/${space.id}`, { is_active: !space.is_active });
                const idx = this.spaces.findIndex(s => s.id === space.id);
                if (idx !== -1) this.spaces[idx] = updated;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: updated.is_active ? 'Aktiv edildi.' : 'Deaktiv edildi.', type: 'info' }
                }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        // ── DELETE ───────────────────────────────────────────────────────
        confirmDelete(space) {
            this.deleteTarget = space;
            this.showDelete   = true;
        },

        async deleteSpace() {
            this.saving = true;
            try {
                await api('DELETE', `/spaces/${this.deleteTarget.id}`);
                this.spaces     = this.spaces.filter(s => s.id !== this.deleteTarget.id);
                this.showDelete = false;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Space silindi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            } finally {
                this.saving = false;
            }
        },

        // ── MEMBERS ───────────────────────────────────────────────────────
        async openMembers(space) {
            this.selectedSpace = space;
            this.newMember     = { employee_id: '', space_role: 'employee', _name: '' };
            this.showMembers   = true;
            await this.loadMembers(space.id);
        },

        async loadMembers(spaceId) {
            const data     = await api('GET', `/spaces/${spaceId}/members`);
            this.members   = Array.isArray(data) ? data : (data?.data ?? []);
        },

        get availableEmployees() {
            const memberIds = this.members.map(m => m.id);
            return this.allEmployees.filter(e => !memberIds.includes(e.id));
        },

        filteredAvailable(q) {
            const lower = (q ?? '').toLowerCase();
            const memberIds = this.members.map(m => m.id);
            return this.allEmployees
                .filter(e => !memberIds.includes(e.id))
                .filter(e =>
                    e.full_name.toLowerCase().includes(lower) ||
                    (e.position ?? '').toLowerCase().includes(lower) ||
                    (e.department?.name ?? '').toLowerCase().includes(lower)
                )
                .slice(0, 10); // maksimum 10 nəticə
        },

        async addMember() {
            if (!this.newMember.employee_id) return;
            try {
                await api('POST', `/spaces/${this.selectedSpace.id}/members`, this.newMember);
                await this.loadMembers(this.selectedSpace.id);
                this.newMember = { employee_id: '', space_role: 'employee', _name: '' };
                // spaces siyahısında members_count artır
                const idx = this.spaces.findIndex(s => s.id === this.selectedSpace.id);
                if (idx !== -1) this.spaces[idx].members_count = (this.spaces[idx].members_count ?? 0) + 1;
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv əlavə edildi.', type:'success' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },

        async removeMember(member) {
            try {
                await api('DELETE', `/spaces/${this.selectedSpace.id}/members/${member.id}`);
                this.members = this.members.filter(m => m.id !== member.id);
                const idx = this.spaces.findIndex(s => s.id === this.selectedSpace.id);
                if (idx !== -1) this.spaces[idx].members_count = Math.max(0, (this.spaces[idx].members_count ?? 1) - 1);
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message:'Üzv silindi.', type:'info' } }));
            } catch(e) {
                window.dispatchEvent(new CustomEvent('toast', { detail:{ message: e.message, type:'error' } }));
            }
        },
    }
}
</script>
@endpush
