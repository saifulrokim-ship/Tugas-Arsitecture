<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('nav.summary')) · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-slate-200 bg-white md:flex">
            <a href="{{ route('projects.index') }}" class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">MP</span>
                <span>
                    <span class="block text-sm font-semibold leading-tight text-slate-900">{{ config('app.name') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('nav.tagline') }}</span>
                </span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
                @if (isset($project))
                    <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('nav.project') }}</p>
                    <p class="px-3 pb-4 text-sm font-medium text-slate-900">{{ $project->name }}</p>

                    @foreach ([
                        ['projects.show', __('nav.summary'), 'projects.show'],
                        ['projects.tasks.index', __('nav.tasks'), 'projects.tasks.*'],
                        ['projects.calendar.edit', __('nav.calendar'), 'projects.calendar.*'],
                        ['projects.team.index', __('nav.team'), 'projects.team.*'],
                        ['projects.gantt', __('nav.gantt'), 'projects.gantt'],
                        ['projects.pert', __('nav.pert'), 'projects.pert'],
                    ] as [$routeName, $label, $pattern])
                        <a
                            href="{{ route($routeName, $project) }}"
                            class="mb-1 flex items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs($pattern) ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        >
                            {{ $label }}
                            @if (request()->routeIs($pattern))
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                            @endif
                        </a>
                    @endforeach
                @else
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('nav.menu') }}</p>
                    <a
                        href="{{ route('projects.index') }}"
                        class="mb-1 flex items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs('projects.index') ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ __('nav.all_projects') }}
                    </a>
                @endif
            </nav>

            <div class="border-t border-slate-200 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('nav.mode_title') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('nav.mode_body') }}</p>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold text-slate-900">@yield('title', __('nav.summary'))</h1>
                        @hasSection('subtitle')
                            <p class="mt-0.5 truncate text-sm text-slate-500">@yield('subtitle')</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Pergantian bahasa (PRD §10): disimpan di session, default English. --}}
                        <form method="POST" action="{{ route('locale.store') }}" class="flex items-center gap-1 rounded-lg border border-slate-200 p-1" aria-label="{{ __('nav.language') }}">
                            @csrf
                            @foreach (['en' => 'EN', 'id' => 'ID'] as $code => $short)
                                <button
                                    type="submit"
                                    name="locale"
                                    value="{{ $code }}"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold {{ app()->getLocale() === $code ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                                    aria-pressed="{{ app()->getLocale() === $code ? 'true' : 'false' }}"
                                >{{ $short }}</button>
                            @endforeach
                        </form>

                        @yield('actions')
                    </div>
                </div>

                @if (isset($project))
                    <nav class="flex gap-1 overflow-x-auto border-t border-slate-100 px-4 py-2 md:hidden">
                        @foreach ([
                            ['projects.show', __('nav.summary'), 'projects.show'],
                            ['projects.tasks.index', __('nav.tasks_short'), 'projects.tasks.*'],
                            ['projects.calendar.edit', __('nav.calendar_short'), 'projects.calendar.*'],
                            ['projects.team.index', __('nav.team_short'), 'projects.team.*'],
                            ['projects.gantt', __('nav.gantt'), 'projects.gantt'],
                            ['projects.pert', __('nav.pert'), 'projects.pert'],
                        ] as [$routeName, $label, $pattern])
                            <a
                                href="{{ route($routeName, $project) }}"
                                class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm {{ request()->routeIs($pattern) ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                            >{{ $label }}</a>
                        @endforeach
                    </nav>
                @endif
            </header>

            <main class="flex-1 px-4 py-6 md:px-6">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
