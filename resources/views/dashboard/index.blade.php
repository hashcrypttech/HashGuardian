@extends('hashguardian::layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $typeRoutes = [
            'request' => 'hashguardian.requests.index',
            'query' => 'hashguardian.queries.index',
            'exception' => 'hashguardian.exceptions.index',
            'job' => 'hashguardian.jobs.index',
            'outgoing_request' => 'hashguardian.outgoing-requests.index',
            'cache' => 'hashguardian.cache.index',
            'mail' => 'hashguardian.mail.index',
            'notification' => 'hashguardian.notifications.index',
            'command' => 'hashguardian.commands.index',
            'schedule' => 'hashguardian.schedule.index',
            'log' => 'hashguardian.logs.index',
            'batch' => 'hashguardian.batches.index',
            'dump' => 'hashguardian.dumps.index',
            'event' => 'hashguardian.events.index',
            'gate' => 'hashguardian.gates.index',
            'model' => 'hashguardian.models.index',
            'redis' => 'hashguardian.redis.index',
            'view' => 'hashguardian.views.index',
        ];

        $typeIcons = [
            'request' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>',
            'query' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
            'exception' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
            'job' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
            'outgoing_request' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7,7 17,7 17,17"/></svg>',
            'cache' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
            'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'notification' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
            'command' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>',
            'schedule' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            'log' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'batch' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>',
            'dump' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>',
            'event' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
            'gate' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'model' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            'redis' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
            'view' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        ];

        $typeAccents = [
            'request' => 'info',
            'query' => 'info',
            'exception' => 'danger',
            'job' => 'warning',
            'outgoing_request' => 'info',
            'cache' => 'success',
            'mail' => 'info',
            'notification' => 'warning',
            'command' => '',
            'schedule' => '',
            'log' => 'danger',
            'batch' => 'info',
            'dump' => '',
            'event' => 'warning',
            'gate' => 'success',
            'model' => 'info',
            'redis' => 'danger',
            'view' => '',
        ];
    @endphp

    <div class="wd-stats-grid">
        @foreach($entryTypes as $type => $label)
            <a href="{{ route($typeRoutes[$type] ?? 'hashguardian.dashboard') }}" style="text-decoration: none;">
                <div class="wd-stat-card" data-accent="{{ $typeAccents[$type] ?? '' }}">
                    <div class="wd-stat-icon {{ $typeAccents[$type] ?? '' }}">
                        {!! $typeIcons[$type] ?? '' !!}
                    </div>
                    <div class="wd-stat-label">{{ $label }}</div>
                    <div class="wd-stat-value {{ $type === 'exception' ? 'danger' : '' }}">
                        {{ number_format($counts[$type] ?? 0) }}
                    </div>
                    <div class="wd-stat-meta">Last 24h</div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="wd-dashboard-grid">
        {{-- Recent Exceptions --}}
        <div class="wd-card">
            <div class="wd-card-header">
                <h3 class="wd-card-title">Recent Exceptions</h3>
                <a href="{{ route('hashguardian.exceptions.index') }}" class="wd-btn" style="font-size:12px; padding:4px 12px;">View All</a>
            </div>
            @if($recentExceptions->isEmpty())
                <div class="wd-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div class="wd-empty-title">No exceptions</div>
                    <div class="wd-empty-text">No exceptions recorded in the last 24 hours.</div>
                </div>
            @else
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th>Exception</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentExceptions as $entry)
                            <tr>
                                <td>
                                    <a href="{{ route('hashguardian.exceptions.show', $entry->uuid) }}" style="color: var(--wd-danger); text-decoration: none; font-weight: 500;">
                                        {{ \Illuminate\Support\Str::limit($entry->content['class'] ?? 'Unknown', 50) }}
                                    </a>
                                    <div style="font-size: 12px; color: var(--wd-text-muted); margin-top: 3px; line-height: 1.4;">
                                        {{ \Illuminate\Support\Str::limit($entry->content['message'] ?? '', 80) }}
                                    </div>
                                </td>
                                <td style="white-space: nowrap; color: var(--wd-text-muted); font-size: 12px;">
                                    {{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Slow Queries --}}
        <div class="wd-card">
            <div class="wd-card-header">
                <h3 class="wd-card-title">Slowest Queries</h3>
                <a href="{{ route('hashguardian.queries.index') }}" class="wd-btn" style="font-size:12px; padding:4px 12px;">View All</a>
            </div>
            @if($slowQueries->isEmpty())
                <div class="wd-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    <div class="wd-empty-title">No queries</div>
                    <div class="wd-empty-text">No queries recorded in the last 24 hours.</div>
                </div>
            @else
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slowQueries as $entry)
                            <tr>
                                <td>
                                    <code class="wd-code-inline">{{ \Illuminate\Support\Str::limit($entry->content['sql'] ?? 'N/A', 60) }}</code>
                                </td>
                                <td>
                                    @php $dur = $entry->duration ?? 0; @endphp
                                    <span class="wd-duration {{ $dur > 1000 ? 'wd-duration-slow' : ($dur > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                        {{ number_format($dur, 2) }}ms
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Failed Jobs --}}
        <div class="wd-card">
            <div class="wd-card-header">
                <h3 class="wd-card-title">Failed Jobs</h3>
                <a href="{{ route('hashguardian.jobs.index') }}" class="wd-btn" style="font-size:12px; padding:4px 12px;">View All</a>
            </div>
            @if($failedJobs->isEmpty())
                <div class="wd-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                    <div class="wd-empty-title">No failed jobs</div>
                    <div class="wd-empty-text">No job failures in the last 24 hours.</div>
                </div>
            @else
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $entry)
                            <tr>
                                <td>
                                    <a href="{{ route('hashguardian.jobs.show', $entry->uuid) }}" style="color: var(--wd-danger); text-decoration: none; font-weight: 500;">
                                        {{ $entry->content['name'] ?? 'Unknown' }}
                                    </a>
                                </td>
                                <td style="white-space: nowrap; color: var(--wd-text-muted); font-size: 12px;">
                                    {{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
