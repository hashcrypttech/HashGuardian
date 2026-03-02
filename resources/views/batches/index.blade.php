@extends('hashguardian::layouts.app')

@section('title', 'Batches')
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
                <div class="wd-empty-text">There are no job batches to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Name</th>
                        <th>Jobs</th>
                        <th>Queue</th>
                        <th>Connection</th>
                        <th>Failures</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php $c = $entry->content ?? []; @endphp
                        <tr>
                            <td><code class="wd-code-inline">{{ \Illuminate\Support\Str::limit($c['id'] ?? 'N/A', 20) }}</code></td>
                            <td>{{ $c['name'] ?? '—' }}</td>
                            <td>
                                <span class="wd-badge wd-badge-info">{{ $c['total_jobs'] ?? 0 }}</span>
                            </td>
                            <td><code class="wd-code-inline">{{ $c['queue'] ?? 'default' }}</code></td>
                            <td>{{ $c['connection'] ?? 'default' }}</td>
                            <td>
                                @if(($c['allows_failures'] ?? false))
                                    <span class="wd-badge wd-badge-warning">Allowed</span>
                                @else
                                    <span class="wd-badge wd-badge-muted">Not Allowed</span>
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
