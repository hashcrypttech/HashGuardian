@extends('hashguardian::layouts.app')

@section('title', 'Activity')
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
                <div class="wd-empty-text">There are no activity entries to display.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>URI</th>
                        <th>IP</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $c = $entry->content ?? [];
                            $user = $c['user'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                @if($user)
                                    <a href="{{ route('hashguardian.activity.user', $user['id']) }}" style="color: var(--wd-primary); text-decoration: none;">
                                        {{ $user['name'] ?? $user['email'] ?? 'User #' . $user['id'] }}
                                    </a>
                                @else
                                    <span style="color: var(--wd-text-muted);">Guest</span>
                                @endif
                            </td>
                            <td>
                                <span class="wd-method wd-method-{{ strtolower($c['action'] ?? 'get') }}">{{ $c['action'] ?? 'GET' }}</span>
                            </td>
                            <td>
                                @if($c['route_name'])
                                    <span style="color: var(--wd-text-muted); font-size: 12px;">{{ $c['route_name'] }}</span><br>
                                @endif
                                {{ \Illuminate\Support\Str::limit($c['uri'] ?? 'N/A', 60) }}
                            </td>
                            <td>
                                <a href="{{ route('hashguardian.activity.index', ['ip' => $c['ip'] ?? '']) }}" style="color: var(--wd-text-muted); text-decoration: none; font-family: monospace; font-size: 12px;">
                                    {{ $c['ip'] ?? 'N/A' }}
                                </a>
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
