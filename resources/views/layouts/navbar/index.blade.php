<div class="navbar bg-base-100 border-b border-base-300 px-4 h-16">
    <!-- Navbar Start -->
    <div class="navbar-start">
        <!-- Toggle Sidebar (Mobile) -->
        <label for="main-drawer" class="btn btn-ghost btn-circle lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
        </label>
        
        <!-- Breadcrumbs or Page Title -->
        <div class="hidden sm:flex text-sm breadcrumbs py-0 ml-2">
            <ul>
                <li><span class="text-base-content/60">{{ config('app.name', 'Laravel') }}</span></li>
                <li><span class="font-semibold text-base-content">{{ $title ?? __('Dashboard') }}</span></li>
            </ul>
        </div>
    </div>

    <!-- Navbar Center -->
    <div class="navbar-center">
        <!-- Quick search field -->
        <div class="relative w-64 md:w-80">
            <input type="text" placeholder="Buscar..." class="input input-bordered input-sm w-full pl-9 pr-4" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-base-content/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Navbar End -->
    <div class="navbar-end gap-2">
        <!-- Theme Toggle -->
        <label class="swap swap-rotate btn btn-ghost btn-circle btn-sm">
            <input type="checkbox" class="theme-controller" value="dark" />
            
            <!-- sun icon -->
            <svg class="swap-off fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1 0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1 0,0,0,0,2H4A1,1 0,0,0,5,12Zm7-7a1,1 0,0,0,1-1V3a1,1 0,0,0-2,0V4A1,1 0,0,0,12,5ZM5.64,7.05a1,1 0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1 0,0,0,.7-.29l.71-.71a1,1 0,1,0-1.41-1.41L17,5.64a1,1 0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1 0,0,0,0,2h1a1,1 0,0,0,0-2Zm-9,8a1,1 0,0,0-1,1v1a1,1 0,0,0,2,0V20A1,1 0,0,0,12,19ZM18.36,17A1,1 0,0,0,17,18.36l.71.71a1,1 0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
            
            <!-- moon icon -->
            <svg class="swap-on fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1 0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1 0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1 0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
        </label>

        <!-- Notifications Dropdown -->
        <div class="dropdown dropdown-end">
            <button tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm relative">
                <div class="indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span id="nav-notification-badge" class="badge badge-xs badge-primary indicator-item {{ $unreadCount > 0 ? '' : 'hidden' }}"></span>
                </div>
            </button>
            <div tabindex="0" class="card card-compact dropdown-content z-[2] mt-3 w-80 bg-base-100 shadow-xl border border-base-200">
                <div class="card-body">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-base">Notificações</h3>
                        <div class="flex items-center gap-2">
                            <!-- Toggle Som Quick Button -->
                            <button onclick="toggleQuickSound()" id="quick-sound-btn" class="btn btn-ghost btn-circle btn-xs" title="Ativar/Desativar Som">
                                <span id="quick-sound-icon">
                                    @if($soundEnabled)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-success">
                                            <path d="M10 3.75a.75.75 0 0 0-1.264-.546L5.203 6.25H3.75a1.75 1.75 0 0 0-1.75 1.75v4c0 .966.784 1.75 1.75 1.75h1.453l3.533 3.046A.75.75 0 0 0 10 16.25V3.75ZM12.22 6.22a.75.75 0 0 1 1.06 0 3.978 3.978 0 0 1 1.13 2.82c0 .874-.28 1.683-.756 2.342a.75.75 0 1 1-1.218-.875 2.478 2.478 0 0 0 .474-1.467c0-.55-.178-1.06-.48-1.503a.75.75 0 0 1-.01-1.057Z"/>
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-base-content/40">
                                            <path d="M9.547 3.078a.75.75 0 0 1 .453.672v12.5a.75.75 0 0 1-1.264.546L5.203 13.75H3.5A1.5 1.5 0 0 1 2 12.25v-4.5A1.5 1.5 0 0 1 3.5 6.25h1.703l3.533-3.046a.75.75 0 0 1 .811-.126ZM12.78 7.22a.75.75 0 0 1 0 1.06L11.56 9.5l1.22 1.22a.75.75 0 1 1-1.06 1.06L10.5 10.56l-1.22 1.22a.75.75 0 1 1-1.06-1.06L9.44 9.5 8.22 8.28a.75.75 0 1 1 1.06-1.06L10.5 8.44l1.22-1.22a.75.75 0 0 1 1.06 0Z" />
                                        </svg>
                                    @endif
                                </span>
                            </button>
                            <button onclick="clearAllNotifications()" id="nav-clear-notifications-btn" class="btn btn-ghost btn-xs text-primary {{ $unreadCount > 0 ? '' : 'hidden' }}">Limpar</button>
                        </div>
                    </div>
                    <div class="divider my-1"></div>
                    
                    <div id="nav-notifications-list" class="max-h-64 overflow-y-auto space-y-2">
                        @forelse($unreadNotifications as $n)
                            <div class="p-2 hover:bg-base-200 rounded text-xs border-b border-base-300 last:border-0" data-notification-id="{{ $n->id }}">
                                <div class="flex justify-between font-semibold">
                                    <span class="text-base-content font-bold">{{ $n->title }}</span>
                                    <span class="text-[9px] opacity-60">{{ $n->created_at->format('d/m H:i') }}</span>
                                </div>
                                <p class="mt-1 text-base-content/85">{{ $n->message }}</p>
                            </div>
                        @empty
                            <p id="nav-no-notifications-placeholder" class="text-xs text-base-content/60 text-center py-4">Nenhuma notificação pendente</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- User Dropdown Menu -->
        <div class="dropdown dropdown-end ml-1">
            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar btn-sm">
                <div class="w-8 rounded-full">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                    @else
                        <div class="bg-neutral text-neutral-content w-full h-full flex items-center justify-center font-bold text-xs uppercase">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>
                    @endif
                </div>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-xl bg-base-100 rounded-box w-52 border border-base-200">
                <li>
                    <a href="{{ route('profile.show') }}" class="justify-between">
                        {{ __('Profile') }}
                        <span class="badge badge-sm badge-accent">Novo</span>
                    </a>
                </li>
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <li>
                        <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                            {{ __('Team Settings') }}
                        </a>
                    </li>
                @endif
                <div class="divider my-1"></div>
                <li>
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <button type="submit" @click.prevent="$root.submit();" class="text-error w-full text-left">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    let soundEnabled = {{ $soundEnabled ? 'true' : 'false' }};
    const loggedInUserId = {{ auth()->id() }};

    function playChime() {
        if (!soundEnabled) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            
            // Tone 1 (E5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.frequency.setValueAtTime(659.25, ctx.currentTime);
            gain1.gain.setValueAtTime(0.2, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.5);

            // Tone 2 (A5) after 100ms
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.1);
            gain2.gain.setValueAtTime(0.2, ctx.currentTime + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.7);
            osc2.start(ctx.currentTime + 0.1);
            osc2.stop(ctx.currentTime + 0.7);
        } catch (e) {
            console.error('Erro ao reproduzir sintetizador de áudio:', e);
        }
    }

    function toggleQuickSound() {
        soundEnabled = !soundEnabled;
        const iconBtn = document.getElementById('quick-sound-icon');
        
        if (soundEnabled) {
            iconBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-success">
                    <path d="M10 3.75a.75.75 0 0 0-1.264-.546L5.203 6.25H3.75a1.75 1.75 0 0 0-1.75 1.75v4c0 .966.784 1.75 1.75 1.75h1.453l3.533 3.046A.75.75 0 0 0 10 16.25V3.75ZM12.22 6.22a.75.75 0 0 1 1.06 0 3.978 3.978 0 0 1 1.13 2.82c0 .874-.28 1.683-.756 2.342a.75.75 0 1 1-1.218-.875 2.478 2.478 0 0 0 .474-1.467c0-.55-.178-1.06-.48-1.503a.75.75 0 0 1-.01-1.057Z"/>
                </svg>
            `;
        } else {
            iconBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-base-content/40">
                    <path d="M9.547 3.078a.75.75 0 0 1 .453.672v12.5a.75.75 0 0 1-1.264.546L5.203 13.75H3.5A1.5 1.5 0 0 1 2 12.25v-4.5A1.5 1.5 0 0 1 3.5 6.25h1.703l3.533-3.046a.75.75 0 0 1 .811-.126ZM12.78 7.22a.75.75 0 0 1 0 1.06L11.56 9.5l1.22 1.22a.75.75 0 1 1-1.06 1.06L10.5 10.56l-1.22 1.22a.75.75 0 1 1-1.06-1.06L9.44 9.5 8.22 8.28a.75.75 0 1 1 1.06-1.06L10.5 8.44l1.22-1.22a.75.75 0 0 1 1.06 0Z" />
                </svg>
            `;
        }

        fetch('/configuracoes', {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                sound_enabled: soundEnabled,
                notify_receivable_created: true,
                notify_receivable_updated: true,
                notify_receivable_paid: true,
                notify_client_created: true
            })
        });
    }

    function clearAllNotifications() {
        const btn = document.getElementById('nav-clear-notifications-btn');
        btn.disabled = true;

        fetch('/notifications/clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('nav-notification-badge').classList.add('hidden');
                document.getElementById('nav-notifications-list').innerHTML = `
                    <p id="nav-no-notifications-placeholder" class="text-xs text-base-content/60 text-center py-4">Nenhuma notificação pendente</p>
                `;
                btn.classList.add('hidden');
            }
            btn.disabled = false;
        })
        .catch(err => {
            console.error('Erro ao limpar notificações:', err);
            btn.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private(`users.${loggedInUserId}`)
                .listen('NotificationReceived', (e) => {
                    const badge = document.getElementById('nav-notification-badge');
                    if (badge) badge.classList.remove('hidden');

                    const placeholder = document.getElementById('nav-no-notifications-placeholder');
                    if (placeholder) placeholder.remove();

                    const clearBtn = document.getElementById('nav-clear-notifications-btn');
                    if (clearBtn) clearBtn.classList.remove('hidden');

                    const list = document.getElementById('nav-notifications-list');
                    if (list) {
                        const item = document.createElement('div');
                        item.className = 'p-2 hover:bg-base-200 rounded text-xs border-b border-base-300 last:border-0';
                        item.setAttribute('data-notification-id', e.id);
                        item.innerHTML = `
                            <div class="flex justify-between font-semibold">
                                <span class="text-base-content font-bold">${e.title}</span>
                                <span class="text-[9px] opacity-60">${e.created_at}</span>
                            </div>
                            <p class="mt-1 text-base-content/85">${e.message}</p>
                        `;
                        list.insertBefore(item, list.firstChild);
                    }

                    playChime();
                });
        }
    });
</script>
