@props([
    'searchable' => false,
    'loader' => true,
    'searchUrl' => '',
    'searchValue' => '',
    'searchPlaceholder' => '',
    'skeleton' => null,
    'topLeft' => null,
    'topRight' => null,
])

@if($searchable || trim(($topLeft ?? '')) !== '' || trim(($topRight ?? '')) !== '')
    <x-moonshine::layout.flex justify-align="between" items-align="center">
        <x-moonshine::layout.flex justify-align="start">
            @if($searchable)
                <x-moonshine::form
                    raw
                    action="{{ $searchUrl }}"
                    @submit.prevent="asyncFormRequest"
                >
                    <x-moonshine::form.input
                        name="search"
                        type="search"
                        value="{{ $searchValue }}"
                        placeholder="{{ $searchPlaceholder }}"
                    />
                </x-moonshine::form>
            @endif

            {!! $topLeft ?? '' !!}
        </x-moonshine::layout.flex>

        @if(trim(($topRight ?? '')) !== '')
            <x-moonshine::layout.flex justify-align="end">
                {!! $topRight ?? '' !!}
            </x-moonshine::layout.flex>
        @endif
    </x-moonshine::layout.flex>
@endif

@if(($searchable || trim(($topLeft ?? '')) !== '') || trim(($topRight ?? '')) !== '')
    <x-moonshine::layout.line-break />
@endif

@if($skeleton ?? false)
    <x-moonshine::skeleton x-show="loading">
        {!! $skeleton !!}
    </x-moonshine::skeleton>
@elseif($loader)
    <x-moonshine::loader x-show="loading" />
@endif

<div @if(($skeleton ?? false) || $loader) x-show="!loading" @endif>
    {{ $slot }}
</div>
