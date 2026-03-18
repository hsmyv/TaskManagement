<!DOCTYPE html>
<html lang="az" x-data="appLayout()" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TİS') — Tapşırıq İdarəetmə Sistemi</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8' },
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .kanban-card { transition: box-shadow 0.2s, transform 0.15s; }
        .kanban-card:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .sortable-ghost { opacity: 0.4; background: #e0f2fe !important; border: 2px dashed #0ea5e9 !important; }
        .sortable-chosen { box-shadow: 0 12px 30px rgba(0,0,0,0.2) !important; }
        .status-todo { background: #f1f5f9; border-top: 3px solid #94a3b8; }
        .status-in_progress { background: #eff6ff; border-top: 3px solid #3b82f6; }
        .status-waiting_for_approve { background: #fffbeb; border-top: 3px solid #f59e0b; }
        .status-completed { background: #f0fdf4; border-top: 3px solid #22c55e; }
        .status-canceled { background: #fef2f2; border-top: 3px solid #ef4444; }
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.5} }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

    </style>
</head>
<body class="h-full bg-slate-50 font-sans antialiased" x-cloak>

<div class="flex h-full">

    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-900 text-white flex flex-col h-screen sticky top-0 shrink-0">
        <div class="px-6 py-5 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center font-bold text-sm">TİS</div>
                <span class="font-semibold">Tapşırıq Sistemi</span>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            @can('admin.access')
<div x-data="{ open: true }">
    <button @click="open = !open"
        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-slate-200 transition-colors mt-4">
        <span>Admin Panel</span>
        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" x-transition class="space-y-0.5">
        <a href="{{ route('admin.spaces') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm {{ request()->routeIs('admin.spaces') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Space-lər
        </a>
        <a href="{{ route('admin.employees') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm {{ request()->routeIs('admin.employees') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Əməkdaşlar
        </a>
        <a href="{{ route('admin.roles') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm {{ request()->routeIs('admin.roles') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Rollar
        </a>
    </div>
</div>
@endcan

            <div x-data="{ open: true }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-slate-200 transition-colors mt-2">
                    <span>Team Spaces</span>
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition>
                    @foreach(auth()->user()->hasGlobalAccess() ? \App\Models\Space::where('is_active',true)->get() : auth()->user()->spaces as $space)
                    <a href="{{ route('spaces.show', $space) }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm mt-0.5 {{ request()->routeIs('spaces.show') && request()->route('space')?->id === $space->id ? 'bg-slate-800 text-white' : '' }}">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $space->color }}"></span>
                        {{ $space->name }}
                    </a>
                    @endforeach

                    @can('create', \App\Models\Space::class)
                    <button x-data @click="$dispatch('open-create-space')"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-500 hover:text-slate-300 transition-colors text-sm mt-1 w-full">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Yeni Space
                    </button>
                    @endcan
                </div>
            </div>
        </nav>

        <div class="px-4 py-4 border-t border-slate-700" x-data="{ open: false }">
            <button @click="open = !open" class="w-full flex items-center gap-3 hover:bg-slate-800 rounded-lg p-2 transition-colors">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="w-8 h-8 rounded-full">
                <div class="text-left flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->position }}</p>
                </div>
            </button>
            <div x-show="open" x-transition @click.outside="open=false"
                 class="absolute bottom-20 left-4 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-2 z-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Çıxış</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">

        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-40">
            <h1 class="text-lg font-semibold text-slate-800">@yield('page-title', 'Dashboard')</h1>

            <div class="flex items-center gap-3">
                {{-- Bildirişlər --}}
                <div x-data="notificationBell()" x-init="init()" class="relative">
                    <button @click="open = !open; if(open) loadNotifications()"
                            class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="unread > 0" x-text="unread > 99 ? '99+' : unread"
                              class="pulse-dot absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">
                        </span>
                    </button>

                    <div x-show="open" x-transition @click.outside="open=false"
                         class="absolute right-0 top-12 w-96 bg-white rounded-xl shadow-2xl border border-slate-100 z-50">
                        <div class="flex items-center justify-between px-4 py-3 border-b">
                            <h3 class="font-semibold text-slate-800">Bildirişlər</h3>
                            <button @click="markAllRead()" class="text-xs text-blue-600 hover:underline">Hamısını oxu</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto scrollbar-thin">
                            <template x-if="notifications.length === 0">
                                <div class="py-8 text-center text-slate-400 text-sm">Bildiriş yoxdur</div>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <div @click="markRead(n)"
                                     class="flex gap-3 px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 transition-colors"
                                     :class="!n.is_read ? 'bg-blue-50/40' : ''">
                                    <div class="flex-col shrink-0 items-center justify-start pt-1">
                                        {{-- Event tipinə görə emoji ikona --}}
                                        <span class="text-base" x-text="notificationIcon(n)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        {{-- Kim nə etdi — əsas mətn --}}
                                        <p class="text-sm text-slate-700 leading-snug" x-text="notificationText(n)"></p>
                                        {{-- Space adı --}}
                                        <p class="text-xs text-blue-500 mt-0.5" x-show="n.data?.space_name" x-text="'📁 ' + (n.data?.space_name ?? '')"></p>
                                        {{-- Tarix --}}
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="formatDate(n.created_at)"></p>
                                    </div>
                                    {{-- Oxunmamış nöqtə --}}
                                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                         :class="!n.is_read ? 'bg-blue-500' : 'bg-transparent'"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto">
            @yield('content')
        </main>
    </div>
</div>

{{-- ── Create Space Modal ─────────────────────────────────────────────────── --}}
<div x-data="createSpaceModal()"
     x-init="init()"
     @open-create-space.window="open = true"
     x-show="open"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold text-slate-800">Yeni Space yarat</h2>
            <button @click="open = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ad <span class="text-red-500">*</span></label>
                <input type="text"
                       x-model="form.name"
                       @keyup.enter="submit()"
                       placeholder="Space adı..."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Təsvir</label>
                <textarea x-model="form.description"
                          rows="2"
                          placeholder="Qısa təsvir..."
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
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
                <label class="block text-sm font-medium text-slate-700 mb-2">Rəng</label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           x-model="form.color"
                           class="w-10 h-10 rounded-lg cursor-pointer border border-slate-200 p-0.5">
                    <span class="text-sm text-slate-500" x-text="form.color"></span>
                </div>
            </div>
            {{-- Xəta mesajı --}}
            <p x-show="error" x-text="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
        </div>

        <div class="px-6 pb-6 flex justify-end gap-3">
            <button @click="open = false"
                    class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                Ləğv et
            </button>
            <button @click="submit()"
                    :disabled="saving || !form.name.trim()"
                    class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span x-text="saving ? 'Yaradılır...' : 'Yarat'"></span>
            </button>
        </div>
    </div>
</div>

{{-- Global API helper --}}
<script>
const API_BASE   = '/api';
const CSRF_TOKEN = document.querySelector('meta[name=csrf-token]').getAttribute('content');
const AUTH_USER  = @json(auth()->user());

async function api(method, url, data = null, isFormData = false) {
    const opts = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
        },
        credentials: 'same-origin',
    };
    if (data) opts.body = isFormData ? data : JSON.stringify(data);
    const res = await fetch(API_BASE + url, opts);
    if (!res.ok) {
        const err = await res.json().catch(() => ({ message: 'Xəta baş verdi.' }));
        throw new Error(err.message || 'Xəta');
    }
    if (res.status === 204) return null;
    return res.json();
}

function appLayout() {
    return {}
}

// ── Bildiriş Bell ─────────────────────────────────────────────────────────────
function notificationBell() {
    return {
        open: false,
        unread: 0,
        notifications: [],
        _timer: null,

        init() {
            this.fetchUnreadCount();
            this._timer = setInterval(() => this.fetchUnreadCount(), 30_000);
        },

        async fetchUnreadCount() {
            try {
                const res  = await api('GET', '/notifications/unread-count');
                const prev = this.unread;
                this.unread = res.count;
                if (res.count > prev && prev !== null) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Yeni bildiriş var 🔔', type: 'info' }
                    }));
                }
            } catch(e) {}
        },

        async loadNotifications() {
            try {
                const res          = await api('GET', '/notifications?per_page=20');
                this.notifications = res.data;
                this.unread        = res.unread;
            } catch(e) {}
        },

        async markRead(n) {
            if (n.is_read) return;
            await api('PATCH', `/notifications/${n.id}/read`);
            n.is_read = true;
            this.unread = Math.max(0, this.unread - 1);
        },

        async markAllRead() {
            await api('PATCH', '/notifications/read-all');
            this.notifications.forEach(n => n.is_read = true);
            this.unread = 0;
        },

        // ── Event tipinə görə ikona ──────────────────────────────────────
        notificationIcon(n) {
            const icons = {
                task_created:        '📋',
                task_updated:        '✏️',
                task_deleted:        '🗑️',
                assignee_changed:    '👤',
                status_changed:      '🔄',
                comment_added:       '💬',
                attachment_added:    '📎',
                attachment_deleted:  '🗑️',
                approval_requested:  '⏳',
                task_approved:       '✅',
                deadline_reminder:   '⏰',
                task_overdue:        '🔴',
            };
            return icons[n.event] ?? '🔔';
        },

        // ── Event tipinə görə insan oxuya biləcəyi mətn ──────────────────
        notificationText(n) {
            const d = n.data ?? {};
            const title = d.task_title ? `"${d.task_title}"` : 'tapşırıq';

            const map = {
                task_created:       () => `${d.created_by ?? 'Biri'} yeni tapşırıq yaratdı: ${title}`,
                task_updated:       () => `${d.updated_by ?? 'Biri'} tapşırığı yenilədi: ${title}`,
                task_deleted:       () => `${d.deleted_by ?? 'Biri'} tapşırığı sildi: ${title}`,
                assignee_changed:   () => `${d.assigned_by ?? 'Biri'} sizi tapşırığa əlavə etdi: ${title}`,
                status_changed:     () => `${d.changed_by ?? 'Biri'} statusu dəyişdi: ${d.from_label ?? d.from_status} → ${d.to_label ?? d.to_status} (${title})`,
                comment_added:      () => `${d.commented_by ?? 'Biri'} şərh yazdı: ${title}`,
                attachment_added:   () => `${d.uploaded_by ?? 'Biri'} fayl əlavə etdi: ${title}`,
                attachment_deleted: () => `${d.deleted_by ?? 'Biri'} faylı sildi: ${title}`,
                approval_requested: () => `Təsdiqiniz gözlənilir: ${title}`,
                task_approved:      () => `${d.approved_by ?? 'Biri'} tapşırığı təsdiqlədi: ${title}`,
                deadline_reminder:  () => `Deadline yaxınlaşır (${d.due_date ?? ''}): ${title}`,
                task_overdue:       () => `Gecikmiş tapşırıq: ${title}`,
            };

            return map[n.event]?.() ?? (d.task_title ?? 'Yeni bildiriş');
        },

        formatDate(dt) {
            return new Date(dt).toLocaleDateString('az-AZ', {
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        }
    }
}

// ── Create Space Modal ────────────────────────────────────────────────────────
function createSpaceModal() {
    return {
        open:        false,
        saving:      false,
        error:       '',
        departments: [],
        form:        { name: '', description: '', color: '#3B82F6', department_id: '' },

        async init() {
            // Departamentləri bir dəfə yüklə
            try {
                const data = await api('GET', '/departments');
                this.departments = Array.isArray(data) ? data : (data?.data ?? []);
            } catch(e) {}
        },

        async submit() {
            this.error = '';
            if (!this.form.name.trim()) {
                this.error = 'Space adı mütləqdir.';
                return;
            }
            this.saving = true;
            try {
                await api('POST', '/spaces', {
                    name:          this.form.name,
                    description:   this.form.description || null,
                    color:         this.form.color,
                    department_id: this.form.department_id || null,
                });
                this.open = false;
                this.form = { name: '', description: '', color: '#3B82F6', department_id: '' };
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Space uğurla yaradıldı!', type: 'success' }
                }));
                setTimeout(() => window.location.reload(), 800);
            } catch(e) {
                this.error = e.message || 'Xəta baş verdi.';
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>

{{-- Toast --}}
<div x-data="toastManager()" class="fixed bottom-5 right-5 z-50 space-y-2" @toast.window="addToast($event.detail)">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible" x-transition
             class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl shadow-lg px-4 py-3 max-w-sm">
            <div class="w-2 h-2 rounded-full shrink-0"
                 :class="{'bg-green-500':toast.type==='success','bg-blue-500':toast.type==='info','bg-red-500':toast.type==='error'}"></div>
            <p class="text-sm text-slate-700" x-text="toast.message"></p>
            <button @click="toast.visible=false" class="ml-auto text-slate-400 hover:text-slate-600 text-xs">✕</button>
        </div>
    </template>
</div>

<script>
function toastManager() {
    return {
        toasts: [],
        addToast({ message, type = 'info' }) {
            const id = Date.now();
            this.toasts.push({ id, message, type, visible: true });
            setTimeout(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = false;
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 500);
            }, 4000);
        }
    }
}
</script>

@stack('scripts')
</body>
</html>
