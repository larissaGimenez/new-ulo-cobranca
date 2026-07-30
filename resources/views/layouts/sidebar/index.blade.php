<aside class="w-64 min-h-screen bg-base-200 text-base-content p-4 flex flex-col justify-between border-r border-base-300">
    <div>
        <!-- Brand/Logo -->
        <div class="flex items-center gap-2 px-2 py-4 mb-6">
            <x-application-mark class="block h-9 w-auto text-primary" />
            <span class="text-xl font-bold tracking-tight text-base-content">{{ config('app.name', 'Laravel') }}</span>
        </div>

        <!-- Navigation Menu -->
        <ul class="menu menu-md w-full gap-1 p-0">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'menu-active' : '' }} flex items-center gap-3">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>

            <li>
                <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.show') ? 'menu-active' : '' }} flex items-center gap-3">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ __('Profile') }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- User Profile Footer Section -->
    <div class="border-t border-base-300 pt-4 mt-auto">
        <div class="flex items-center gap-3 px-2">
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <img class="size-10 rounded-full object-cover border border-base-300" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
            @else
                <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content w-10 rounded-full">
                        <span class="text-xs">{{ substr(Auth::user()->name, 0, 2) }}</span>
                    </div>
                </div>
            @endif
            <div class="truncate">
                <div class="font-bold text-sm text-base-content truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-base-content/60 truncate">{{ Auth::user()->email }}</div>
            </div>
        </div>

        <!-- Logout Form -->
        <form method="POST" action="{{ route('logout') }}" x-data class="mt-4">
            @csrf
            <button type="submit" @click.prevent="$root.submit();" class="btn btn-sm btn-ghost btn-error w-full flex items-center justify-start gap-2">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
</aside>
