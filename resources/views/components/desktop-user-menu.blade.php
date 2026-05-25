@php($user = auth()->user())

<x-dropdown right {{ $attributes }}>
    <x-slot:trigger>
        <button type="button" class="btn btn-ghost gap-2 px-2" data-test="sidebar-menu-button">
            <div class="avatar avatar-placeholder">
                <div class="w-8 rounded-md bg-neutral text-neutral-content">
                    <span class="text-xs">{{ $user->initials() }}</span>
                </div>
            </div>
            <span class="hidden text-sm font-medium lg:block">{{ $user->name }}</span>
            <x-icon name="o-chevron-up-down" class="h-4 w-4 opacity-60" />
        </button>
    </x-slot:trigger>

    <div class="flex items-center gap-2 px-3 py-2">
        <div class="avatar avatar-placeholder">
            <div class="w-8 rounded-md bg-neutral text-neutral-content">
                <span class="text-xs">{{ $user->initials() }}</span>
            </div>
        </div>
        <div class="grid leading-tight">
            <span class="truncate text-sm font-medium">{{ $user->name }}</span>
            <span class="truncate text-xs opacity-60">{{ $user->email }}</span>
        </div>
    </div>

    <li><hr class="my-1 border-base-300" /></li>

    <li>
        <a href="{{ route('profile.edit') }}" wire:navigate>
            <x-icon name="o-cog-6-tooth" class="h-4 w-4" />
            {{ __('Settings') }}
        </a>
    </li>

    <li>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2" data-test="logout-button">
                <x-icon name="o-arrow-right-start-on-rectangle" class="h-4 w-4" />
                {{ __('Log out') }}
            </button>
        </form>
    </li>
</x-dropdown>
