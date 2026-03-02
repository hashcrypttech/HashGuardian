@extends('hashguardian::layouts.app')

@section('title', 'Job Details')

@section('content')
    @php
        $c = $entry->content ?? [];
        $status = $entry->status ?? $c['status'] ?? 'processed';
        $isQueued = $status === 'queued';
        $displayEntry = $executionEntry ?? $entry;
        $dc = is_array($displayEntry->content ?? null) ? $displayEntry->content : (isset($displayEntry->content) ? json_decode($displayEntry->content, true) : $c);
    @endphp
    <div class="wd-card">
        <div class="wd-card-header">
            <h3 class="wd-card-title">Job Details</h3>
        </div>
        <div class="wd-card-body">
            <table class="wd-table">
                <tr>
                    <th style="width: 140px;">Job</th>
                    <td>{{ $c['name'] ?? $c['job'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Queue</th>
                    <td>{{ $dc['queue'] ?? $c['queue'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Connection</th>
                    <td>{{ $dc['connection'] ?? $c['connection'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @php $displayStatus = $executionEntry ? ($executionEntry->status ?? $dc['status'] ?? $status) : $status; @endphp
                        <span class="wd-badge {{ $displayStatus === 'failed' ? 'wd-badge-danger' : ($displayStatus === 'queued' ? 'wd-badge-warning' : ($displayStatus === 'processing' ? 'wd-badge-info' : 'wd-badge-success')) }}">
                            {{ $displayStatus }}
                        </span>
                        @if($isQueued && $executionEntry)
                            <span style="color: var(--wd-text-muted); font-size: 12px; margin-left: 8px;">(dispatched as queued)</span>
                        @endif
                    </td>
                </tr>
                @if(isset($dc['attempts']))
                    <tr>
                        <th>Attempts</th>
                        <td>{{ $dc['attempts'] }}</td>
                    </tr>
                @endif
                @php $dur = $executionEntry->duration ?? $entry->duration ?? 0; @endphp
                @if($dur > 0)
                    <tr>
                        <th>Duration</th>
                        <td>
                            <span class="wd-duration {{ $dur > 1000 ? 'wd-duration-slow' : ($dur > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                {{ number_format($dur, 2) }}ms
                            </span>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    @if($isQueued && !$executionEntry && $queries->isEmpty() && $outgoingRequests->isEmpty())
        <div class="wd-card" style="margin-top: 24px;">
            <div class="wd-card-body" style="text-align: center; padding: 40px 24px;">
                <div style="font-size: 40px; margin-bottom: 12px; opacity: 0.3;">&#9203;</div>
                <p style="color: var(--wd-text-muted); font-size: 15px; margin: 0;">This job is queued and has not been processed yet.</p>
                <p style="color: var(--wd-text-muted); font-size: 13px; margin-top: 8px;">Execution details (queries, third-party requests) will appear once the job is processed by a worker.</p>
            </div>
        </div>
    @else
        {{-- Database Queries Section --}}
        <div class="wd-card" style="margin-top: 24px;">
            <div class="wd-card-header">
                <h3 class="wd-card-title">Database Queries</h3>
                <span style="color: var(--wd-text-muted); font-size: 13px;">
                    {{ $queries->count() }} {{ Str::plural('query', $queries->count()) }}
                    @if($totalQueryTime > 0)
                        &middot; Total: <span class="wd-duration {{ $totalQueryTime > 1000 ? 'wd-duration-slow' : ($totalQueryTime > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">{{ number_format($totalQueryTime, 2) }}ms</span>
                    @endif
                </span>
            </div>
            <div class="wd-card-body">
                @if($queries->isEmpty())
                    <p style="color: var(--wd-text-muted); text-align: center; padding: 24px 0;">No database queries recorded for this job.</p>
                @else
                    <table class="wd-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>SQL Query</th>
                                <th style="width: 120px; text-align: right;">Duration</th>
                                <th style="width: 140px; text-align: right;">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($queries as $index => $q)
                                @php $qc = is_array($q->content) ? $q->content : json_decode($q->content, true); @endphp
                                <tr>
                                    <td style="color: var(--wd-text-muted);">{{ $index + 1 }}</td>
                                    <td>
                                        <code class="wd-code-inline" style="word-break: break-all; white-space: pre-wrap;">{{ $qc['sql'] ?? 'N/A' }}</code>
                                        @if(!empty($qc['connection']))
                                            <span class="wd-badge wd-badge-muted" style="margin-left: 6px; font-size: 11px;">{{ $qc['connection'] }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        @if($q->duration)
                                            <span class="wd-duration {{ $q->duration > 1000 ? 'wd-duration-slow' : ($q->duration > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                                {{ number_format($q->duration, 2) }}ms
                                            </span>
                                        @else
                                            <span style="color: var(--wd-text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; color: var(--wd-text-muted); font-size: 12px;">
                                        {{ \Carbon\Carbon::parse($q->created_at)->format('H:i:s.u') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Outgoing (Third-Party) Requests Section --}}
        <div class="wd-card" style="margin-top: 24px;">
            <div class="wd-card-header">
                <h3 class="wd-card-title">Third-Party Requests</h3>
                <span style="color: var(--wd-text-muted); font-size: 13px;">{{ $outgoingRequests->count() }} {{ Str::plural('request', $outgoingRequests->count()) }}</span>
            </div>
            <div class="wd-card-body">
                @if($outgoingRequests->isEmpty())
                    <p style="color: var(--wd-text-muted); text-align: center; padding: 24px 0;">No third-party requests recorded for this job.</p>
                @else
                    <table class="wd-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 80px;">Method</th>
                                <th>URL</th>
                                <th style="width: 90px; text-align: center;">Status</th>
                                <th style="width: 120px; text-align: right;">Duration</th>
                                <th style="width: 140px; text-align: right;">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($outgoingRequests as $index => $req)
                                @php $rc = is_array($req->content) ? $req->content : json_decode($req->content, true); @endphp
                                <tr>
                                    <td style="color: var(--wd-text-muted);">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="wd-method wd-method-{{ strtolower($rc['method'] ?? 'get') }}">{{ $rc['method'] ?? 'GET' }}</span>
                                    </td>
                                    <td>
                                        <code class="wd-code-inline" style="word-break: break-all;">{{ $rc['url'] ?? 'N/A' }}</code>
                                    </td>
                                    <td style="text-align: center;">
                                        @php $httpStatus = $rc['status'] ?? null; @endphp
                                        @if($httpStatus)
                                            <span class="wd-badge {{ (is_numeric($httpStatus) && $httpStatus >= 500) ? 'wd-badge-danger' : ((is_numeric($httpStatus) && $httpStatus >= 400) ? 'wd-badge-warning' : (($httpStatus === 'connection_failed') ? 'wd-badge-danger' : 'wd-badge-success')) }}">
                                                {{ $httpStatus }}
                                            </span>
                                        @else
                                            <span style="color: var(--wd-text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        @if($req->duration)
                                            <span class="wd-duration {{ $req->duration > 1000 ? 'wd-duration-slow' : ($req->duration > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                                {{ number_format($req->duration, 2) }}ms
                                            </span>
                                        @else
                                            <span style="color: var(--wd-text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; color: var(--wd-text-muted); font-size: 12px;">
                                        {{ \Carbon\Carbon::parse($req->created_at)->format('H:i:s.u') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Exceptions Section --}}
        @if($exceptions->isNotEmpty())
            <div class="wd-card" style="margin-top: 24px;">
                <div class="wd-card-header">
                    <h3 class="wd-card-title" style="color: var(--wd-danger);">Exceptions</h3>
                    <span style="color: var(--wd-text-muted); font-size: 13px;">{{ $exceptions->count() }} {{ Str::plural('exception', $exceptions->count()) }}</span>
                </div>
                <div class="wd-card-body">
                    @foreach($exceptions as $ex)
                        @php $ec = is_array($ex->content) ? $ex->content : json_decode($ex->content, true); @endphp
                        <div style="padding: 12px; margin-bottom: 12px; border: 1px solid var(--wd-danger); border-radius: 6px; background: rgba(239, 68, 68, 0.05);">
                            <div style="font-weight: 600; color: var(--wd-danger); margin-bottom: 4px;">
                                {{ $ec['class'] ?? 'Exception' }}
                            </div>
                            <div style="margin-bottom: 8px;">{{ $ec['message'] ?? '' }}</div>
                            @if(!empty($ec['file']))
                                <div style="font-size: 12px; color: var(--wd-text-muted);">
                                    {{ $ec['file'] }}@if(!empty($ec['line'])):{{ $ec['line'] }}@endif
                                </div>
                            @endif
                            <div style="font-size: 12px; color: var(--wd-text-muted); margin-top: 4px;">
                                {{ \Carbon\Carbon::parse($ex->created_at)->format('H:i:s.u') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
@endsection
