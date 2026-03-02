@extends('hashguardian::layouts.app')

@section('title', 'Timeline')

@section('content')
    <div class="wd-card">
        <div class="wd-card-header">
            <h3 class="wd-card-title">Batch: <code class="wd-code-inline">{{ $batchId }}</code></h3>
            <span style="color: var(--wd-text-muted); font-size: 13px;">{{ $entries->count() }} events</span>
        </div>
        <div class="wd-card-body">
            <div class="wd-timeline">
                @foreach($entries as $entry)
                    <div class="wd-timeline-item {{ $entry->type }}">
                        <div class="wd-timeline-type">
                            <span class="wd-badge wd-badge-{{ $entry->type === 'exception' ? 'danger' : ($entry->type === 'query' ? 'info' : ($entry->type === 'job' ? 'warning' : 'primary')) }}">
                                {{ strtoupper($entry->type) }}
                            </span>
                            @if($entry->duration)
                                <span class="wd-duration {{ $entry->duration > 1000 ? 'wd-duration-slow' : ($entry->duration > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}" style="margin-left: 8px;">
                                    {{ number_format($entry->duration, 2) }}ms
                                </span>
                            @endif
                        </div>
                        <div class="wd-timeline-content">
                            @php $content = is_array($entry->content) ? $entry->content : json_decode($entry->content, true); @endphp
                            @switch($entry->type)
                                @case('request')
                                    <span class="wd-method wd-method-{{ strtolower($content['method'] ?? 'get') }}">{{ $content['method'] ?? 'GET' }}</span>
                                    {{ $content['uri'] ?? $content['url'] ?? 'N/A' }}
                                    @if(isset($content['status']))
                                        <span class="wd-badge {{ ($content['status'] ?? 200) >= 500 ? 'wd-badge-danger' : (($content['status'] ?? 200) >= 400 ? 'wd-badge-warning' : 'wd-badge-success') }}" style="margin-left: 8px;">
                                            {{ $content['status'] }}
                                        </span>
                                    @endif
                                    @break
                                @case('query')
                                    <code class="wd-code-inline">{{ \Illuminate\Support\Str::limit($content['sql'] ?? 'N/A', 120) }}</code>
                                    @break
                                @case('exception')
                                    <span style="color: var(--wd-danger);">{{ $content['class'] ?? 'Exception' }}</span>: {{ \Illuminate\Support\Str::limit($content['message'] ?? '', 100) }}
                                    @break
                                @case('job')
                                    {{ $content['name'] ?? $content['job'] ?? 'N/A' }}
                                    @if(isset($content['status']))
                                        <span class="wd-badge {{ $content['status'] === 'failed' ? 'wd-badge-danger' : 'wd-badge-success' }}" style="margin-left: 8px;">{{ $content['status'] }}</span>
                                    @endif
                                    @break
                                @case('cache')
                                    <span class="wd-badge wd-badge-{{ ($content['type'] ?? '') === 'hit' ? 'success' : (($content['type'] ?? '') === 'missed' ? 'warning' : 'info') }}">{{ strtoupper($content['type'] ?? 'N/A') }}</span>
                                    {{ $content['key'] ?? 'N/A' }}
                                    @break
                                @default
                                    {{ \Illuminate\Support\Str::limit(json_encode($content), 120) }}
                            @endswitch
                        </div>
                        <div class="wd-timeline-meta">
                            {{ \Carbon\Carbon::parse($entry->created_at)->format('H:i:s.u') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
