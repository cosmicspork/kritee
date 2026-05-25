<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <ul class="menu w-full px-0" aria-label="{{ __('Settings') }}">
            <li>
                <a href="{{ route('profile.edit') }}" wire:navigate @class(['menu-active' => request()->routeIs('profile.edit')])>{{ __('Profile') }}</a>
            </li>
            <li>
                <a href="{{ route('security.edit') }}" wire:navigate @class(['menu-active' => request()->routeIs('security.edit')])>{{ __('Security') }}</a>
            </li>
            <li>
                <a href="{{ route('appearance.edit') }}" wire:navigate @class(['menu-active' => request()->routeIs('appearance.edit')])>{{ __('Appearance') }}</a>
            </li>
            @if (auth()->user()?->isAdmin())
                <li>
                    <a href="{{ route('team.edit') }}" wire:navigate @class(['menu-active' => request()->routeIs('team.edit')])>{{ __('Team') }}</a>
                </li>
            @endif
        </ul>
    </div>

    <hr class="border-base-300 md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg font-medium">{{ $heading ?? '' }}</h2>
        <p class="text-base-content/60">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
