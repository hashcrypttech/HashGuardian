@extends('hashguardian::layouts.app')

@section('title', 'Custom Metrics')

@section('content')
    @if(empty($metrics))
        <div class="wd-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
            </svg>
            <h3>No Metrics Recorded</h3>
            <p>Start recording custom metrics using <code>HashGuardian::count()</code>, <code>HashGuardian::metric()</code>, or <code>HashGuardian::startTimer()</code>.</p>
        </div>
    @else
        <div class="wd-stats-grid">
            @foreach($metrics as $metric)
                <div class="wd-stat-card" data-accent="{{ $metric['type'] === 'counter' ? 'info' : ($metric['type'] === 'timer' ? 'warning' : 'success') }}">
                    <div class="wd-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
                        </svg>
                    </div>
                    <div class="wd-stat-label">{{ $metric['name'] }}</div>
                    <div class="wd-stat-value">{{ number_format($metric['latest'], $metric['type'] === 'timer' ? 2 : 0) }}{{ $metric['type'] === 'timer' ? 'ms' : '' }}</div>
                    <div class="wd-stat-meta">{{ ucfirst($metric['type']) }} · {{ $metric['count'] }} records</div>
                </div>
            @endforeach
        </div>

        <div class="wd-card" style="margin-top: 24px;">
            <div class="wd-card-header">
                <h2 class="wd-card-title">Metric Details</h2>
            </div>
            <div class="wd-card-body">
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Latest</th>
                            <th>Average</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metrics as $metric)
                            <tr>
                                <td><code>{{ $metric['name'] }}</code></td>
                                <td><span class="wd-badge">{{ $metric['type'] }}</span></td>
                                <td>{{ number_format($metric['latest'], $metric['type'] === 'timer' ? 2 : 0) }}{{ $metric['type'] === 'timer' ? 'ms' : '' }}</td>
                                <td>{{ number_format($metric['avg'], $metric['type'] === 'timer' ? 2 : 0) }}{{ $metric['type'] === 'timer' ? 'ms' : '' }}</td>
                                <td>{{ number_format($metric['min'], $metric['type'] === 'timer' ? 2 : 0) }}{{ $metric['type'] === 'timer' ? 'ms' : '' }}</td>
                                <td>{{ number_format($metric['max'], $metric['type'] === 'timer' ? 2 : 0) }}{{ $metric['type'] === 'timer' ? 'ms' : '' }}</td>
                                <td>{{ number_format($metric['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($entries->isNotEmpty())
            <div class="wd-card" style="margin-top: 24px;">
                <div class="wd-card-header">
                    <h2 class="wd-card-title">Recent Entries</h2>
                </div>
                <div class="wd-card-body">
                    <table class="wd-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Name</th>
                                <th>Value</th>
                                <th>Type</th>
                                <th>Metadata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries->take(50) as $entry)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('Y-m-d H:i:s') }}</td>
                                    <td><code>{{ $entry->content['name'] ?? 'unknown' }}</code></td>
                                    <td>{{ number_format($entry->content['value'] ?? 0, $entry->content['metric_type'] === 'timer' ? 2 : 0) }}{{ $entry->content['metric_type'] === 'timer' ? 'ms' : '' }}</td>
                                    <td><span class="wd-badge">{{ $entry->content['metric_type'] ?? 'gauge' }}</span></td>
                                    <td>
                                        @if(!empty($entry->content['metadata']))
                                            <code style="font-size: 11px;">{{ json_encode($entry->content['metadata']) }}</code>
                                        @else
                                            <span style="color: var(--wd-text-muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
@endsection
