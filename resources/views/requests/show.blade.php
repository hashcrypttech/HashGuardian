@extends('hashguardian::layouts.app')

@section('title', 'Request Details')

@section('content')
    @php $c = $entry->content ?? []; @endphp
    <div class="wd-card">
        <div class="wd-card-header">
            <h3 class="wd-card-title">Request Details</h3>
        </div>
        <div class="wd-card-body">
            <table class="wd-table">
                <tr>
                    <th style="width: 140px;">Method</th>
                    <td><span class="wd-method wd-method-{{ strtolower($c['method'] ?? 'get') }}">{{ $c['method'] ?? 'GET' }}</span></td>
                </tr>
                <tr>
                    <th>URL</th>
                    <td><code class="wd-code-inline">{{ $c['uri'] ?? $c['url'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @php $status = $c['status'] ?? 200; @endphp
                        <span class="wd-badge {{ $status >= 500 ? 'wd-badge-danger' : ($status >= 400 ? 'wd-badge-warning' : 'wd-badge-success') }}">{{ $status }}</span>
                    </td>
                </tr>
                @if(!empty($c['headers']))
                    <tr>
                        <th>Headers</th>
                        <td><pre class="wd-code" style="max-height: 200px;">{{ json_encode($c['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                    </tr>
                @endif
                @if(!empty($c['ip']))
                    <tr>
                        <th>IP</th>
                        <td>{{ $c['ip'] }}</td>
                    </tr>
                @endif
                @if(!empty($c['user_id']) || !empty($c['user']))
                    <tr>
                        <th>User</th>
                        <td>
                            @php
                                $user = $c['user'] ?? $c['user_id'] ?? null;
                            @endphp
                            @if(is_array($user))
                                {{ $user['name'] ?? $user['email'] ?? $user['id'] ?? 'N/A' }}
                                @if(!empty($user['email']))
                                    <span style="color: var(--wd-text-muted); margin-left: 4px;">({{ $user['email'] }})</span>
                                @endif
                            @else
                                {{ $user ?? 'N/A' }}
                            @endif
                        </td>
                    </tr>
                @endif
                @if(!empty($c['controller_action']))
                    <tr>
                        <th>Controller</th>
                        <td><code class="wd-code-inline">{{ $c['controller_action'] }}</code></td>
                    </tr>
                @endif
                @if(!empty($c['middleware']))
                    <tr>
                        <th>Middleware</th>
                        <td>
                            @foreach((array)$c['middleware'] as $mw)
                                <span class="wd-badge wd-badge-muted" style="margin-right: 4px;">{{ $mw }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endif
                @if(!empty($c['payload']))
                    <tr>
                        <th>Payload</th>
                        <td><pre class="wd-code" style="max-height: 200px;">{{ is_array($c['payload']) ? json_encode($c['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $c['payload'] }}</pre></td>
                    </tr>
                @endif
                @if(!empty($c['user_agent']))
                    <tr>
                        <th>User Agent</th>
                        <td style="color: var(--wd-text-muted); font-size: 12px;">{{ $c['user_agent'] }}</td>
                    </tr>
                @endif
                @if(!empty($c['response_size']))
                    <tr>
                        <th>Response Size</th>
                        <td>{{ number_format($c['response_size']) }} bytes</td>
                    </tr>
                @endif
                @if(!empty($c['memory']))
                    <tr>
                        <th>Memory</th>
                        <td>{{ $c['memory'] }} MB</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <div class="wd-card" style="margin-top: 24px;">
        <div class="wd-card-header">
            <h3 class="wd-card-title">Batch Timeline</h3>
            <span style="color: var(--wd-text-muted); font-size: 13px;">{{ $batchEntries->count() }} events</span>
        </div>
        <div class="wd-card-body">
            <div class="wd-timeline">
                @foreach($batchEntries as $batchEntry)
                    <div class="wd-timeline-item {{ $batchEntry->type }}">
                        <div class="wd-timeline-type">
                            <span class="wd-badge wd-badge-{{ $batchEntry->type === 'exception' ? 'danger' : ($batchEntry->type === 'query' ? 'info' : ($batchEntry->type === 'job' ? 'warning' : 'primary')) }}">
                                {{ strtoupper($batchEntry->type) }}
                            </span>
                            @if($batchEntry->duration)
                                <span class="wd-duration {{ $batchEntry->duration > 1000 ? 'wd-duration-slow' : ($batchEntry->duration > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}" style="margin-left: 8px;">
                                    {{ number_format($batchEntry->duration, 2) }}ms
                                </span>
                            @endif
                        </div>
                        <div class="wd-timeline-content">
                            @php $bc = is_array($batchEntry->content) ? $batchEntry->content : json_decode($batchEntry->content, true); @endphp
                            @switch($batchEntry->type)
                                @case('request')
                                    <span class="wd-method wd-method-{{ strtolower($bc['method'] ?? 'get') }}">{{ $bc['method'] ?? 'GET' }}</span>
                                    {{ $bc['uri'] ?? $bc['url'] ?? 'N/A' }}
                                    @if(isset($bc['status']))
                                        <span class="wd-badge {{ ($bc['status'] ?? 200) >= 500 ? 'wd-badge-danger' : (($bc['status'] ?? 200) >= 400 ? 'wd-badge-warning' : 'wd-badge-success') }}" style="margin-left: 8px;">{{ $bc['status'] }}</span>
                                    @endif
                                    @break
                                @case('query')
                                    <code class="wd-code-inline">{{ \Illuminate\Support\Str::limit($bc['sql'] ?? 'N/A', 120) }}</code>
                                    @break
                                @case('exception')
                                    <span style="color: var(--wd-danger);">{{ $bc['class'] ?? 'Exception' }}</span>: {{ \Illuminate\Support\Str::limit($bc['message'] ?? '', 100) }}
                                    @break
                                @case('job')
                                    {{ $bc['name'] ?? $bc['job'] ?? 'N/A' }}
                                    @if(isset($bc['status']))
                                        <span class="wd-badge {{ $bc['status'] === 'failed' ? 'wd-badge-danger' : 'wd-badge-success' }}" style="margin-left: 8px;">{{ $bc['status'] }}</span>
                                    @endif
                                    @break
                                @case('cache')
                                    <span class="wd-badge wd-badge-{{ ($bc['type'] ?? '') === 'hit' ? 'success' : (($bc['type'] ?? '') === 'missed' ? 'warning' : 'info') }}">{{ strtoupper($bc['type'] ?? 'N/A') }}</span>
                                    {{ $bc['key'] ?? 'N/A' }}
                                    @break
                                @default
                                    {{ \Illuminate\Support\Str::limit(json_encode($bc), 120) }}
                            @endswitch
                        </div>
                        <div class="wd-timeline-meta">
                            {{ \Carbon\Carbon::parse($batchEntry->created_at)->format('H:i:s.u') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
