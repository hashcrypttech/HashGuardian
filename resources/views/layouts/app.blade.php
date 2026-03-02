<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - HashGuardian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/hashguardian/css/hashguardian.css') }}">
    @stack('styles')
</head>
<body class="hashguardian-body">
    <div class="wd-layout">
        <aside class="wd-sidebar">
            <div class="wd-sidebar-header">
                <a href="{{ route('hashguardian.dashboard') }}" class="wd-sidebar-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    HashGuardian
                </a>
            </div>

            <nav class="wd-sidebar-nav">
                <div class="wd-nav-section">Overview</div>
                <a href="{{ route('hashguardian.dashboard') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('hashguardian.trends.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.trends.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Trends
                </a>

                <a href="{{ route('hashguardian.server.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.server.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                    Server
                </a>

                <a href="{{ route('hashguardian.htop.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.htop.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><polyline points="6 13 9 9 12 11 15 7 18 10"/></svg>
                    Htop Monitor
                </a>

                <div class="wd-nav-section">Events</div>
                <a href="{{ route('hashguardian.requests.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.requests.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    Requests
                </a>

                <a href="{{ route('hashguardian.queries.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.queries.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    Queries
                </a>

                <a href="{{ route('hashguardian.exceptions.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.exceptions.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Exceptions
                </a>

                <a href="{{ route('hashguardian.jobs.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.jobs.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                    Jobs
                </a>

                <a href="{{ route('hashguardian.batches.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.batches.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>
                    Batches
                </a>

                <a href="{{ route('hashguardian.events.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.events.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Events
                </a>

                <a href="{{ route('hashguardian.outgoing-requests.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.outgoing-requests.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7,7 17,7 17,17"/></svg>
                    Outgoing Requests
                </a>

                <a href="{{ route('hashguardian.cache.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.cache.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    Cache
                </a>

                <a href="{{ route('hashguardian.redis.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.redis.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    Redis
                </a>

                <a href="{{ route('hashguardian.mail.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.mail.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Mail
                </a>

                <a href="{{ route('hashguardian.notifications.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.notifications.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    Notifications
                </a>

                <a href="{{ route('hashguardian.commands.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.commands.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                    Commands
                </a>

                <a href="{{ route('hashguardian.schedule.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.schedule.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Schedule
                </a>

                <a href="{{ route('hashguardian.logs.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.logs.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Logs
                </a>

                <a href="{{ route('hashguardian.dumps.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.dumps.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    Dumps
                </a>

                <a href="{{ route('hashguardian.models.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.models.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Models
                </a>

                <a href="{{ route('hashguardian.views.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.views.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Views
                </a>

                <div class="wd-nav-section">Security</div>
                <a href="{{ route('hashguardian.activity.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.activity.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Activity
                </a>

                <a href="{{ route('hashguardian.gates.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.gates.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Gates
                </a>

                <div class="wd-nav-section">Custom</div>
                <a href="{{ route('hashguardian.metrics.index') }}" class="wd-nav-link {{ request()->routeIs('hashguardian.metrics.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    Metrics
                </a>
            </nav>
        </aside>

        <main class="wd-main">
            <header class="wd-header">
                <h1 class="wd-header-title">@yield('title', 'Dashboard')</h1>
                <div class="wd-header-actions">
                    @yield('actions')

                    <label class="wd-refresh-toggle">
                        <input type="checkbox" id="wd-auto-refresh">
                        Auto-refresh
                    </label>

                    <button class="wd-theme-btn" id="wd-theme-toggle" title="Toggle theme">
                        <svg id="wd-theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                    </button>
                </div>
            </header>

            <div class="wd-content">
                @hasSection('filterable')
                    <div class="wd-filter-bar">
                        <input type="text" id="wd-search-input" class="wd-search-input" placeholder="Search entries...">

                        <form id="wd-date-filter" class="wd-date-inputs">
                            <input type="datetime-local" id="wd-since" class="wd-date-input" value="{{ request('since') }}" placeholder="From">
                            <span style="color: var(--wd-text-muted);">to</span>
                            <input type="datetime-local" id="wd-until" class="wd-date-input" value="{{ request('until') }}" placeholder="To">
                            <button type="submit" class="wd-btn" style="padding: 6px 12px; font-size: 13px;">Filter</button>
                            @if(request('since') || request('until'))
                                <button type="button" id="wd-date-clear" class="wd-btn" style="padding: 6px 12px; font-size: 13px;">Clear</button>
                            @endif
                        </form>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('vendor/hashguardian/js/hashguardian.js') }}"></script>
    @stack('scripts')
</body>
</html>
