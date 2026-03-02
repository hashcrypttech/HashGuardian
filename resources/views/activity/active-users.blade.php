@extends('hashguardian::layouts.app')

@section('title', 'Active Users')

@section('content')
    <div class="wd-card">
        @if(empty($activeUsers))
            <div class="wd-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                <div class="wd-empty-title">No active users</div>
                <div class="wd-empty-text">There are no users with activity in the last 5 minutes.</div>
            </div>
        @else
            <div class="wd-card-header">
                <span class="wd-card-title">Active Users (Last 5 Minutes)</span>
                <span style="font-size: 12px; color: var(--wd-text-muted); font-weight: normal;">
                    {{ count($activeUsers) }} {{ count($activeUsers) === 1 ? 'user' : 'users' }}
                </span>
            </div>
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Activity Count</th>
                        <th>Sessions</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeUsers as $userData)
                        @php
                            $user = $userData['user'];
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--wd-accent); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                        {{ strtoupper(substr($user['name'] ?? $user['email'] ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: var(--wd-text-heading);">
                                            {{ $user['name'] ?? 'Unknown' }}
                                        </div>
                                        <div style="font-size: 12px; color: var(--wd-text-muted);">
                                            {{ $user['email'] ?? 'No email' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="wd-badge wd-badge-info">{{ $userData['activity_count'] }}</span>
                            </td>
                            <td>
                                <span class="wd-badge">{{ count($userData['sessions']) }}</span>
                            </td>
                            <td style="white-space: nowrap; color: var(--wd-text-muted); font-size: 13px;">
                                {{ \Carbon\Carbon::parse($userData['last_activity'])->diffForHumans() }}
                            </td>
                            <td>
                                <a href="{{ route('hashguardian.activity.user', $user['id']) }}" class="wd-btn" style="padding: 4px 12px; font-size: 12px;">
                                    View Timeline
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
