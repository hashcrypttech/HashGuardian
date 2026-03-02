@extends('hashguardian::layouts.app')

@section('title', 'Dumps')
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
                <div class="wd-empty-text">There are no dumps to display. Use dump() or dd() in your code to capture output.</div>
            </div>
        @else
            @foreach($entries as $entry)
                @php $c = $entry->content ?? []; @endphp
                <div class="wd-card" style="margin-bottom: 12px; border: 1px solid var(--wd-border); border-radius: 8px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--wd-text-muted);">
                            @if($c['file'] ?? null)
                                <code class="wd-code-inline">{{ $c['file'] }}:{{ $c['line'] ?? '' }}</code>
                            @else
                                <span>Unknown source</span>
                            @endif
                        </div>
                        <span style="font-size: 12px; color: var(--wd-text-muted);">
                            {{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}
                        </span>
                    </div>
                    <div class="wd-dump-content" style="background: var(--wd-bg-secondary); border-radius: 6px; padding: 12px; overflow-x: auto; font-size: 13px;">
                        {!! $c['dump'] ?? '<em>Empty dump</em>' !!}
                    </div>
                </div>
            @endforeach
            @if($entries->count() >= 50)
                <div class="wd-pagination">
                    <a href="?before={{ $entries->last()->id }}" class="wd-btn">Load More</a>
                </div>
            @endif
        @endif
    </div>
@endsection
