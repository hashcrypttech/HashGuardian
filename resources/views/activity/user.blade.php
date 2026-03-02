@extends('hashguardian::layouts.app')

@section('title', 'User Activity: ' . ($user['name'] ?? $user['email'] ?? 'User #' . $userId))
@section('filterable', true)

@section('content')
    @if($user)
        <div class="wd-card" style="margin-bottom: 20px;">
            <div class="wd-card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--wd-accent); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 18px;">
                        {{ strtoupper(substr($user['name'] ?? $user['email'] ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 16px; color: var(--wd-text-heading);">
                            {{ $user['name'] ?? 'Unknown' }}
                        </div>
                        <div style="font-size: 13px; color: var(--wd-text-muted);">
                            {{ $user['email'] ?? 'No email' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="wd-card">
        @if($entries->isEmpty())
            <div class="wd-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
                <div class="wd-empty-title">No activity found</div>
                <div class="wd-empty-text">This user has no recorded activity.</div>
            </div>
        @else
            <div class="wd-card-header">
                <span class="wd-card-title">Activity Timeline</span>
            </div>
            <div class="wd-card-body" style="padding: 0;">
                <div style="position: relative; padding: 20px;">
                    @foreach($entries as $entry)
                        @php
                            $c = $entry->content ?? [];
                            $isFirst = $loop->first;
                            $isLast = $loop->last;
                        @endphp
                        <div style="display: flex; gap: 16px; position: relative; padding-bottom: 24px; {{ !$isLast ? 'border-left: 2px solid var(--wd-border); margin-left: 11px; padding-left: 24px;' : '' }}">
                            <div style="position: absolute; left: {{ $isLast ? '0' : '-6px' }}; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--wd-accent); border: 2px solid var(--wd-surface);"></div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <span class="wd-method wd-method-{{ strtolower($c['action'] ?? 'get') }}" style="font-size: 11px;">{{ $c['action'] ?? 'GET' }}</span>
                                    <span style="color: var(--wd-text-heading); font-weight: 500;">{{ $c['uri'] ?? 'N/A' }}</span>
                                    @if($c['route_name'])
                                        <span style="color: var(--wd-text-muted); font-size: 11px;">({{ $c['route_name'] }})</span>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; font-size: 12px; color: var(--wd-text-muted);">
                                    <span>{{ \Carbon\Carbon::parse($entry->created_at)->format('Y-m-d H:i:s') }}</span>
                                    @if($c['ip'])
                                        <span>•</span>
                                        <span>{{ $c['ip'] }}</span>
                                    @endif
                                    @if($c['session_id'])
                                        <span>•</span>
                                        <a href="{{ route('hashguardian.activity.session', $c['session_id']) }}" style="color: var(--wd-primary); text-decoration: none;">
                                            Session: {{ $c['session_id'] }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($entries->count() >= 50)
                <div class="wd-pagination">
                    <a href="?before={{ $entries->last()->id }}" class="wd-btn">Load More</a>
                </div>
            @endif
        @endif
    </div>
@endsection
