@extends('hashguardian::layouts.app')

@section('title', 'Exceptions')
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
                <div class="wd-empty-text">There are no exceptions to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Message</th>
                        <th>File</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php $c = $entry->content ?? []; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('hashguardian.exceptions.show', $entry->uuid) }}" style="color: var(--wd-danger); text-decoration: none;">
                                    {{ \Illuminate\Support\Str::limit($c['class'] ?? 'Unknown', 50) }}
                                </a>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($c['message'] ?? 'N/A', 80) }}</td>
                            <td style="font-size: 12px; color: var(--wd-text-muted);">{{ \Illuminate\Support\Str::limit($c['file'] ?? 'N/A', 60) }}</td>
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
