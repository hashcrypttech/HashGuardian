@extends('hashguardian::layouts.app')

@section('title', 'Events')
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
                <div class="wd-empty-text">There are no events to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Listeners</th>
                        <th>Broadcast</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $c = $entry->content ?? [];
                            $listeners = $c['listeners'] ?? [];
                        @endphp
                        <tr>
                            <td>
                                <code class="wd-code-inline">{{ class_basename($c['name'] ?? 'N/A') }}</code>
                                @if(class_exists($c['name'] ?? ''))
                                    <div style="font-size: 11px; color: var(--wd-text-muted); margin-top: 3px;">
                                        {{ $c['name'] }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if(count($listeners) > 0)
                                    @foreach($listeners as $listener)
                                        <div style="font-size: 12px; margin-bottom: 2px;">
                                            {{ class_basename($listener['name'] ?? 'Unknown') }}
                                            @if($listener['queued'] ?? false)
                                                <span class="wd-badge wd-badge-warning" style="font-size: 10px; padding: 1px 6px;">queued</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span style="color: var(--wd-text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($c['broadcast'] ?? false)
                                    <span class="wd-badge wd-badge-success">Yes</span>
                                @else
                                    <span style="color: var(--wd-text-muted);">No</span>
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
