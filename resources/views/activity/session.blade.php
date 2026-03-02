@extends('hashguardian::layouts.app')

@section('title', 'Session Replay: ' . substr($sessionInfo['session_id'], 0, 12))

@section('content')
    <div class="wd-card" style="margin-bottom: 20px;">
        <div class="wd-card-body" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="font-weight: 600; font-size: 14px; color: var(--wd-text-heading); margin-bottom: 4px;">
                        Session ID: <code style="font-family: monospace; font-size: 12px; background: var(--wd-bg-elevated); padding: 2px 6px; border-radius: 4px;">{{ $sessionInfo['session_id'] }}</code>
                    </div>
                    <div style="font-size: 12px; color: var(--wd-text-muted);">
                        @if($sessionInfo['user'])
                            User: <a href="{{ route('hashguardian.activity.user', $sessionInfo['user']['id']) }}" style="color: var(--wd-primary); text-decoration: none;">
                                {{ $sessionInfo['user']['name'] ?? $sessionInfo['user']['email'] ?? 'User #' . $sessionInfo['user']['id'] }}
                            </a>
                        @else
                            Guest Session
                        @endif
                        @if($sessionInfo['ip'])
                            • IP: <code style="font-family: monospace; font-size: 11px;">{{ $sessionInfo['ip'] }}</code>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--wd-text-muted); margin-bottom: 4px;">
                        Started: {{ \Carbon\Carbon::parse($sessionInfo['first_activity'])->format('Y-m-d H:i:s') }}
                    </div>
                    <div style="font-size: 12px; color: var(--wd-text-muted);">
                        Duration: {{ \Carbon\Carbon::parse($sessionInfo['first_activity'])->diffForHumans($sessionInfo['last_activity'], true) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wd-card">
        <div class="wd-card-header">
            <span class="wd-card-title">Page Trail ({{ $entries->count() }} steps)</span>
        </div>
        <div class="wd-card-body" style="padding: 0;">
            <div style="position: relative; padding: 20px;">
                @foreach($entries as $index => $entry)
                    @php
                        $c = $entry->content ?? [];
                        $isFirst = $loop->first;
                        $isLast = $loop->last;
                    @endphp
                    <div style="display: flex; gap: 16px; position: relative; padding-bottom: 20px; {{ !$isLast ? 'border-left: 2px solid var(--wd-border); margin-left: 11px; padding-left: 24px;' : '' }}">
                        <div style="position: absolute; left: {{ $isLast ? '0' : '-6px' }}; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--wd-accent); border: 2px solid var(--wd-surface); display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 600; color: white;">
                            {{ $loop->iteration }}
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <span class="wd-method wd-method-{{ strtolower($c['action'] ?? 'get') }}" style="font-size: 11px;">{{ $c['action'] ?? 'GET' }}</span>
                                <span style="color: var(--wd-text-heading); font-weight: 500; font-size: 14px;">{{ $c['uri'] ?? 'N/A' }}</span>
                            </div>
                            @if($c['route_name'])
                                <div style="font-size: 12px; color: var(--wd-text-muted); margin-bottom: 4px;">
                                    Route: <code style="background: var(--wd-bg-elevated); padding: 2px 6px; border-radius: 4px;">{{ $c['route_name'] }}</code>
                                </div>
                            @endif
                            <div style="font-size: 11px; color: var(--wd-text-muted);">
                                {{ \Carbon\Carbon::parse($entry->created_at)->format('H:i:s') }}
                                @if($c['referer'] && !$isFirst)
                                    <span>•</span>
                                    <span style="font-style: italic;">from: {{ \Illuminate\Support\Str::limit($c['referer'], 50) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
