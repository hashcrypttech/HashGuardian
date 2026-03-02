@extends('hashguardian::layouts.app')

@section('title', 'Exception Details')

@section('content')
    @php $c = $entry->content ?? []; @endphp
    <div class="wd-card">
        <div class="wd-card-header">
            <h3 class="wd-card-title" style="color: var(--wd-danger);">{{ $c['class'] ?? 'Exception' }}</h3>
        </div>
        <div class="wd-card-body">
            <table class="wd-table">
                <tr>
                    <th style="width: 140px;">Class</th>
                    <td>{{ $c['class'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td>{{ $c['message'] ?? 'N/A' }}</td>
                </tr>
                @if(!empty($c['file']) || !empty($c['line']))
                    <tr>
                        <th>Location</th>
                        <td><code class="wd-code-inline">{{ ($c['file'] ?? '') . (isset($c['line']) ? ':' . $c['line'] : '') }}</code></td>
                    </tr>
                @endif
            </table>

            @if(!empty($c['trace']))
                <div style="margin-top: 20px;">
                    <h4 style="font-size: 13px; font-weight: 600; color: var(--wd-text-muted); margin-bottom: 12px; text-transform: uppercase;">Stack Trace</h4>
                    <pre class="wd-stack-trace">@php
                        $trace = $c['trace'];
                        $lines = is_array($trace) ? array_map(function ($f) {
                            return is_array($f) ? (($f['file'] ?? '') . '(' . ($f['line'] ?? '') . '): ' . (($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? ''))) : $f;
                        }, $trace) : explode("\n", $trace);
                    @endphp
@foreach($lines as $line)
<span class="wd-stack-line">{{ $line }}</span>
@endforeach</pre>
                </div>
            @endif
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
