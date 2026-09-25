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

 <link rel="stylesheet" href="{{ asset('css/app.css') }}">

</head>
<body class="h-full bg-slate-50 font-sans antialiased" x-cloak>

@php($isAdminView = request()->routeIs('admin.*'))

@if($isAdminView)
<div class="flex h-full">

    <aside class="w-64 bg-slate-900 text-white flex flex-col h-screen sticky top-0 shrink-0">
        <div class="px-6 py-5 border-b border-slate-700">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg hover:bg-slate-800/70 transition-colors">
                <span class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center font-bold text-sm">TİS</span>
                <span class="font-semibold">Tapşırıq Sistemi</span>
            </a>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <a href="{{ route('tasks.calendar') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors {{ request()->routeIs('tasks.calendar') ? 'bg-slate-800 text-white' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                Təqvim
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
                    <a href="{{ route('admin.audit-logs') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm {{ request()->routeIs('admin.audit-logs') ? 'bg-slate-800 text-white' : '' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 012-2z"/></svg>
                        Audit log
                    </a>
                </div>
            </div>
            @endcan
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

    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-40">
            <h1 class="text-lg font-semibold text-slate-800">@yield('page-title', 'Dashboard')</h1>

            <div class="flex items-center gap-3">
                <div x-data="notificationBell()" x-init="init()" class="relative">
                    <button @click="open = !open; if(open) loadNotifications()"
                            class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="unread > 0" x-text="unread > 99 ? '99+' : unread"
                              class="pulse-dot absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1"></span>
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
                                     :class="!n.is_read ? 'bg-blue-100/80' : 'bg-white'">
                                    <div class="flex-col shrink-0 items-center justify-start pt-1">
                                        <span class="text-base" x-text="notificationIcon(n)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-slate-800 leading-snug font-semibold" x-text="notificationText(n)"></p>
                                        <p class="text-xs text-blue-500 mt-0.5" x-show="n.data?.space_name" x-text="'📁 ' + (n.data?.space_name ?? '')"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="formatDate(n.created_at)"></p>
                                    </div>
                                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="!n.is_read ? 'bg-blue-500' : 'bg-transparent'"></div>
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
@else
<div class="min-h-screen flex flex-col bg-[#b8b0c3] overflow-hidden">
    <header class="relative z-40 bg-gradient-to-r from-[#132e69] via-[#1b2960] to-[#39245f] shadow-[0_10px_30px_rgba(10,18,48,0.35)]">
        <div class="h-[74px] px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-4 min-w-0">
                <a href="{{ route('dashboard') }}" class="h-12 w-20 rounded-xl bg-[#0d2757] shadow-inner flex items-center justify-center text-[#6fb3ff] font-extrabold text-3xl tracking-tight hover:bg-[#12346f] transition-colors">TIS</a>
                <div class="hidden md:block text-white/90">
                    <p class="text-lg font-medium leading-none">@yield('page-title', 'Tapşırıq İdarəetmə Sistemi')</p>
                </div>
                <a href="{{ route('tasks.calendar') }}"
                   class="hidden lg:inline-flex h-10 items-center rounded-lg px-4 text-sm font-semibold text-white/85 hover:bg-white/10 {{ request()->routeIs('tasks.calendar') ? 'bg-white/15 text-white' : '' }}">
                    Təqvim
                </a>
            </div>

            <div class="flex items-center gap-4">
                <div x-data="notificationBell()" x-init="init()" class="relative">
                    <button @click="open = !open; if(open) loadNotifications()"
                            class="relative p-2 rounded-full text-white hover:bg-white/10 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="unread > 0" x-text="unread > 99 ? '99+' : unread"
                              class="pulse-dot absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1"></span>
                    </button>

                    <div x-show="open" x-transition @click.outside="open=false"
                         class="absolute right-0 top-12 w-96 max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden">
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
                                     :class="!n.is_read ? 'bg-blue-100/80' : 'bg-white'">
                                    <div class="flex-col shrink-0 items-center justify-start pt-1">
                                        <span class="text-base" x-text="notificationIcon(n)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-slate-800 leading-snug font-semibold" x-text="notificationText(n)"></p>
                                        <p class="text-xs text-blue-500 mt-0.5" x-show="n.data?.space_name" x-text="'📁 ' + (n.data?.space_name ?? '')"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="formatDate(n.created_at)"></p>
                                    </div>
                                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="!n.is_read ? 'bg-blue-500' : 'bg-transparent'"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false, saving:false, async saveProfile(){ this.saving = true; try { const res = await fetch('/api/auth/profile', { method:'POST', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept':'application/json' }, body: new FormData(this.$refs.profileForm), credentials:'same-origin' }); if(!res.ok){ const err = await res.json().catch(() => ({})); throw new Error(err.message || 'Profil yenilənmədi'); } window.location.reload(); } catch(e){ window.dispatchEvent(new CustomEvent('toast', { detail:{ message:e.message || 'Xəta', type:'error' } })); } finally { this.saving = false; } } }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center gap-3 rounded-full pl-4 pr-1 py-1 text-white hover:bg-white/10 transition-colors">
                        <span class="hidden sm:block text-base font-medium">{{ auth()->user()->full_name }}</span>
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="w-11 h-11 rounded-full ring-2 ring-white/30 object-cover">
                    </button>

                    <div x-show="open" x-transition @click.outside="open=false"
                         class="absolute right-0 top-14 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->full_name }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ auth()->user()->position }}</p>
                        </div>
                        <form x-ref="profileForm" @submit.prevent="saveProfile()" class="px-4 py-3 space-y-3 border-b border-slate-100">
                            <div class="grid grid-cols-2 gap-2">
                                <input name="name" value="{{ auth()->user()->name }}" class="h-10 rounded-xl border border-slate-200 px-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Ad">
                                <input name="surname" value="{{ auth()->user()->surname }}" class="h-10 rounded-xl border border-slate-200 px-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Soyad">
                            </div>
                            <input type="file" name="avatar" accept="image/*" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-slate-700">
                            <button type="submit" :disabled="saving" class="w-full h-10 rounded-xl bg-[#1f4f9f] text-white text-sm font-semibold hover:bg-[#1b4386] disabled:opacity-60" x-text="saving ? 'Yenilənir...' : 'Profili yenilə'"></button>
                        </form>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">Çıxış</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-auto">
        @yield('content')
    </main>
</div>
@endif

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

<script>
const AUTH_USER  = @json(auth()->user());
</script>
@push('scripts')
    <script src="{{ asset('js/app.js') }}"></script>
@endpush

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



@stack('scripts')
</body>
</html>
