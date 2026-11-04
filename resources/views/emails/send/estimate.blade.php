@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
        {{$data['company']['name']}}
        @endcomponent
    @endslot

    {{-- Body --}}
    <!-- Body here -->

    {{-- Subcopy --}}
    @slot('subcopy')
        @component('mail::subcopy')
            {!! $data['body'] !!}
            @component('mail::button', ['url' => $data['url']])
                {{ $data['view_label'] }}
            @endcomponent
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://craterapp.com">Crater</a>
        @endcomponent
    @endslot
@endcomponent

