@extends('hashguardian::layouts.app')

@section('title', 'Views')
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
                <div class="wd-empty-text">There are no view renders to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>View</th>
                        <th>Path</th>
                        <th>Data</th>
                        <th>Composers</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $c = $entry->content ?? [];
                            $dataKeys = $c['data'] ?? [];
                            $composers = $c['composers'] ?? [];
                        @endphp
                        <tr>
                            <td><code class="wd-code-inline">{{ $c['name'] ?? 'N/A' }}</code></td>
                            <td style="font-size: 12px; color: var(--wd-text-muted);">
                                {{ \Illuminate\Support\Str::limit($c['path'] ?? '—', 60) }}
                            </td>
                            <td>
                                @if(count($dataKeys) > 0)
                                    @foreach(array_slice($dataKeys, 0, 4) as $key)
                                        <span class="wd-badge wd-badge-muted" style="font-size: 10px; margin: 1px;">{{ $key }}</span>
                                    @endforeach
                                    @if(count($dataKeys) > 4)
                                        <span style="font-size: 11px; color: var(--wd-text-muted);">+{{ count($dataKeys) - 4 }}</span>
                                    @endif
                                @else
                                    <span style="color: var(--wd-text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                @if(count($composers) > 0)
                                    @foreach($composers as $composer)
                                        <div style="font-size: 12px;">
                                            {{ class_basename($composer['name'] ?? 'Unknown') }}
                                            <span class="wd-badge wd-badge-muted" style="font-size: 10px;">{{ $composer['type'] ?? 'composer' }}</span>
                                        </div>
                                    @endforeach
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
