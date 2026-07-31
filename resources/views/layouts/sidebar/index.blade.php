<aside class="is-drawer-close:w-16 is-drawer-open:w-64 min-h-screen bg-base-200 text-base-content p-3 flex flex-col justify-between border-r border-base-300 transition-all duration-300 select-none">
    <div>
        <!-- Brand/Logo Header -->
        <div class="flex items-center justify-between px-1 py-3 mb-4 border-b border-base-300/60 min-h-[3.5rem]">
            <div class="flex items-center gap-2.5 overflow-hidden">
                <x-application-mark class="block h-8 w-auto text-primary shrink-0" />
                <span class="text-lg font-bold tracking-tight text-base-content truncate is-drawer-close:hidden">{{ config('app.name', 'Laravel') }}</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="menu menu-md w-full gap-1 p-0">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'menu-active' : '' }} flex items-center gap-3 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Dashboard') }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="is-drawer-close:hidden truncate">{{ __('Dashboard') }}</span>
                </a>
            </li>

            <li>
                <a href="{{ route('clientes') }}" class="{{ request()->routeIs('clientes') ? 'menu-active' : '' }} flex items-center gap-3 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Clientes') }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="is-drawer-close:hidden truncate">{{ __('Clientes') }}</span>
                </a>
            </li>

            <li>
                <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.show') ? 'menu-active' : '' }} flex items-center gap-3 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Profile') }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="is-drawer-close:hidden truncate">{{ __('Profile') }}</span>
                </a>
            </li>

            <li>
                <a href="{{ route('dev-settings') }}" class="{{ request()->routeIs('dev-settings') ? 'menu-active' : '' }} flex items-center gap-3 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Dev Settings') }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="is-drawer-close:hidden truncate">{{ __('Dev Settings') }}</span>
                </a>
            </li>

            <li>
                <a href="{{ route('configuracoes') }}" class="{{ request()->routeIs('configuracoes') ? 'menu-active' : '' }} flex items-center gap-3 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Configurações') }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span class="is-drawer-close:hidden truncate">{{ __('Configurações') }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- User Profile Footer Section -->
    <div class="border-t border-base-300 pt-3 mt-auto">
        <div class="flex items-center gap-3 px-1 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ Auth::user()->name }}">
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <img class="size-9 rounded-full object-cover border border-base-300 shrink-0" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
            @else
                <div class="avatar placeholder shrink-0">
                    <div class="bg-neutral text-neutral-content w-9 rounded-full">
                        <span class="text-xs">{{ substr(Auth::user()->name, 0, 2) }}</span>
                    </div>
                </div>
            @endif
            <div class="truncate is-drawer-close:hidden">
                <div class="font-bold text-xs text-base-content truncate">{{ Auth::user()->name }}</div>
                <div class="text-[10px] text-base-content/60 truncate">{{ Auth::user()->email }}</div>
            </div>
        </div>

        <!-- Logout Form -->
        <form method="POST" action="{{ route('logout') }}" x-data class="mt-3">
            @csrf
            <button type="submit" @click.prevent="$root.submit();" class="btn btn-xs btn-ghost btn-error w-full flex items-center gap-2 is-drawer-close:justify-center is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="{{ __('Log Out') }}">
                <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="is-drawer-close:hidden text-xs">{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
</aside>
