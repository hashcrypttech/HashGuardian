@extends('hashguardian::layouts.app')

@section('title', 'Models')
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
                <div class="wd-empty-text">There are no model events to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Key</th>
                        <th>Changes</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $c = $entry->content ?? [];
                            $action = $c['action'] ?? 'unknown';
                            $actionBadge = match($action) {
                                'created' => 'wd-badge-success',
                                'updated' => 'wd-badge-warning',
                                'deleted' => 'wd-badge-danger',
                                'restored' => 'wd-badge-info',
                                'retrieved' => 'wd-badge-muted',
                                default => 'wd-badge-muted',
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="wd-badge {{ $actionBadge }}">
                                    {{ strtoupper($action) }}
                                </span>
                            </td>
                            <td>
                                <code class="wd-code-inline">{{ class_basename($c['model_class'] ?? $c['model'] ?? 'N/A') }}</code>
                                <div style="font-size: 11px; color: var(--wd-text-muted); margin-top: 3px;">
                                    {{ $c['model_class'] ?? '' }}
                                </div>
                            </td>
                            <td>
                                @if($action === 'retrieved' && isset($c['count']))
                                    <span class="wd-badge wd-badge-info">{{ $c['count'] }} hydrated</span>
                                @else
                                    {{ $c['key'] ?? '—' }}
                                @endif
                            </td>
                            <td>
                                @php $changes = $c['changes'] ?? []; @endphp
                                @if(!empty($changes))
                                    @foreach(array_slice($changes, 0, 3) as $field => $value)
                                        <div style="font-size: 12px;">
                                            <strong>{{ $field }}:</strong>
                                            <code class="wd-code-inline">{{ \Illuminate\Support\Str::limit(is_string($value) ? $value : json_encode($value), 30) }}</code>
                                        </div>
                                    @endforeach
                                    @if(count($changes) > 3)
                                        <div style="font-size: 11px; color: var(--wd-text-muted);">+{{ count($changes) - 3 }} more</div>
                                    @endif
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
