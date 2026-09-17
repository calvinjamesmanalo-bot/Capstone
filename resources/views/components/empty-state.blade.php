@props(['heading', 'description', 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->class(['rounded-xl bg-slate-50 px-6 py-10 text-center']) }} role="status">
    <h3 class="text-base font-semibold text-slate-800">{{ $heading }}</h3>
    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">{{ $description }}</p>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex rounded-lg bg-[#000638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#10175a] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ $actionLabel }}</a>
    @endif
</div>
