@if (session('success'))
    <div class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-flash>
        <p>{{ session('success') }}</p>
        <button type="button" class="shrink-0 font-semibold opacity-60 hover:opacity-100" data-dismiss aria-label="{{ __('flash.close') }}">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" data-flash>
        <p>{{ session('error') }}</p>
        <button type="button" class="shrink-0 font-semibold opacity-60 hover:opacity-100" data-dismiss aria-label="{{ __('flash.close') }}">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" data-flash>
        <p class="mb-1 font-semibold">{{ __('flash.error_heading') }}</p>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="mt-2 font-semibold opacity-60 hover:opacity-100" data-dismiss>{{ __('flash.close') }}</button>
    </div>
@endif
