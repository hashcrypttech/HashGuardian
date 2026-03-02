@extends('hashguardian::layouts.app')

@section('title', 'Gates')
@section('filterable', true)

@section('content')
    <div class="wd-card">
        @if($entries->isEmpty())
            <div class="wd-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
                <div class="wd-empty-title">No entries found</div>
                <div class="wd-empty-text">There are no gate checks to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Ability</th>
                        <th>Result</th>
                        <th>Arguments</th>
                        <th>Location</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php $c = $entry->content ?? []; @endphp
                        <tr>
                            <td><code class="wd-code-inline">{{ $c['ability'] ?? 'N/A' }}</code></td>
                            <td>
                                @php $result = $c['result'] ?? 'unknown'; @endphp
                                <span class="wd-badge {{ $result === 'allowed' ? 'wd-badge-success' : 'wd-badge-danger' }}">
                                    {{ strtoupper($result) }}
                                </span>
                                @if($c['message'] ?? null)
                                    <div style="font-size: 11px; color: var(--wd-text-muted); margin-top: 3px;">
                                        {{ $c['message'] }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php $args = $c['arguments'] ?? []; @endphp
                                @if(count($args) > 0)
                                    @foreach($args as $arg)
                                        <div style="font-size: 12px;">
                                            <code class="wd-code-inline">{{ is_string($arg) ? \Illuminate\Support\Str::limit($arg, 50) : json_encode($arg) }}</code>
                                        </div>
                                    @endforeach
                                @else
                                    <span style="color: var(--wd-text-muted);">—</span>
                                @endif
                            </td>
                            <td style="font-size: 12px;">
                                @if($c['file'] ?? null)
                                    <code class="wd-code-inline">{{ basename($c['file']) }}:{{ $c['line'] ?? '' }}</code>
                                @else
                                    <span style="color: var(--wd-text-muted);">—</span>
                                @endif
                            </td>
                            <td style="white-space: nowrap; color: var(--wd-text-muted); font-size: 13px;">
                                {{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($entries->count() >= 50)
                <div class="wd-pagination">
                    <a href="?before={{ $entries->last()->id }}" class="wd-btn">Load More</a>
                </div>
            @endif
        @endif
    </div>
@endsection
