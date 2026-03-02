@extends('hashguardian::layouts.app')

@section('title', 'Requests')

@section('actions')
    <select id="wd-req-period" class="wd-date-input" style="width: auto;">
        <option value="1h">Last 1 Hour</option>
        <option value="3h" selected>Last 3 Hours</option>
        <option value="6h">Last 6 Hours</option>
        <option value="24h">Last 24 Hours</option>
        <option value="7d">Last 7 Days</option>
        <option value="30d">Last 30 Days</option>
    </select>
@endsection

@php
    $total = $stats['total'] ?? 0;
    $avgDur = $stats['avg_duration'] ?? 0;
    $maxDur = $stats['max_duration'] ?? 0;
@endphp

@section('content')
    {{-- Stat Cards --}}
    <div class="wd-stats-grid">
        <div class="wd-stat-card" data-accent="info">
            <div class="wd-stat-icon info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <div class="wd-stat-label">Total Requests</div>
            <div class="wd-stat-value">{{ number_format($total) }}</div>
        </div>
        <div class="wd-stat-card" data-accent="success">
            <div class="wd-stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="wd-stat-label">2xx Success</div>
            <div class="wd-stat-value success">{{ number_format($statusGroups['2xx']) }}</div>
            <div class="wd-stat-meta">{{ $total > 0 ? number_format(($statusGroups['2xx'] / $total) * 100, 1) : 0 }}%</div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
            <div class="wd-stat-label">3xx Redirect</div>
            <div class="wd-stat-value">{{ number_format($statusGroups['3xx']) }}</div>
            <div class="wd-stat-meta">{{ $total > 0 ? number_format(($statusGroups['3xx'] / $total) * 100, 1) : 0 }}%</div>
        </div>
        <div class="wd-stat-card" data-accent="warning">
            <div class="wd-stat-icon warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="wd-stat-label">4xx Client Error</div>
            <div class="wd-stat-value warning">{{ number_format($statusGroups['4xx']) }}</div>
            <div class="wd-stat-meta">{{ $total > 0 ? number_format(($statusGroups['4xx'] / $total) * 100, 1) : 0 }}%</div>
        </div>
        <div class="wd-stat-card" data-accent="danger">
            <div class="wd-stat-icon danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="wd-stat-label">5xx Server Error</div>
            <div class="wd-stat-value danger">{{ number_format($statusGroups['5xx']) }}</div>
            <div class="wd-stat-meta">{{ $total > 0 ? number_format(($statusGroups['5xx'] / $total) * 100, 1) : 0 }}%</div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="wd-stat-label">Avg Duration</div>
            <div class="wd-stat-value">{{ number_format($avgDur, 0) }}<span style="font-size: 14px; font-weight: 500; color: var(--wd-text-muted);">ms</span></div>
            <div class="wd-stat-meta">Max: {{ number_format($maxDur, 0) }}ms</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="wd-combined-chart-wrapper" style="margin-bottom: 24px;">
        <div class="wd-chart-panel" style="border-bottom: none; border-radius: 10px 10px 0 0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <h3 class="wd-chart-title" style="margin-bottom:0;">Request Volume</h3>
                <div id="wd-req-zoom-controls" style="display:flex;align-items:center;gap:6px;">
                    <span id="wd-req-zoom-label" style="font-size:11px;color:var(--wd-text-muted);margin-right:4px;">100%</span>
                    <button id="wd-req-zoom-in" title="Zoom In" style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-heading);cursor:pointer;font-size:16px;font-weight:600;line-height:1;transition:all .15s ease;">+</button>
                    <button id="wd-req-zoom-out" title="Zoom Out" style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-heading);cursor:pointer;font-size:16px;font-weight:600;line-height:1;transition:all .15s ease;">&minus;</button>
                    <button id="wd-req-zoom-reset" title="Reset Zoom" style="display:inline-flex;align-items:center;justify-content:center;height:28px;padding:0 10px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-muted);cursor:pointer;font-size:11px;font-weight:500;transition:all .15s ease;">Reset</button>
                </div>
            </div>
            <canvas id="wd-chart-req-volume" style="max-height:300px;" height="250"></canvas>
        </div>
        <div class="wd-chart-panel" style="border-top: none; border-radius: 0 0 10px 10px; padding-top: 0;">
            <h3 class="wd-chart-title" style="font-size: 12px; margin-bottom: 8px;">Avg Response Time</h3>
            <canvas id="wd-chart-req-duration" style="max-height:120px;" height="100"></canvas>
        </div>
        <div id="wd-req-tooltip" class="wd-chart-tooltip"></div>
    </div>

    {{-- Filters --}}
    <div class="wd-card" style="margin-bottom: 20px;">
        <div class="wd-card-body" style="padding: 14px 20px;">
            <form id="wd-req-filters" method="GET" action="{{ route('hashguardian.requests.index') }}" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                @if(request('since'))
                    <input type="hidden" name="since" value="{{ request('since') }}">
                @endif
                @if(request('until'))
                    <input type="hidden" name="until" value="{{ request('until') }}">
                @endif

                <label style="font-size: 12px; font-weight: 600; color: var(--wd-text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Filters</label>

                <select name="status_group" class="wd-date-input" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                    <option value="">All Status Codes</option>
                    <option value="200" {{ $currentStatusGroup == '200' ? 'selected' : '' }}>2xx Success</option>
                    <option value="300" {{ $currentStatusGroup == '300' ? 'selected' : '' }}>3xx Redirect</option>
                    <option value="400" {{ $currentStatusGroup == '400' ? 'selected' : '' }}>4xx Client Error</option>
                    <option value="500" {{ $currentStatusGroup == '500' ? 'selected' : '' }}>5xx Server Error</option>
                </select>

                <select name="min_duration" class="wd-date-input" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                    <option value="">All Durations</option>
                    <option value="100" {{ $currentMinDuration == '100' ? 'selected' : '' }}>&gt; 100ms</option>
                    <option value="250" {{ $currentMinDuration == '250' ? 'selected' : '' }}>&gt; 250ms</option>
                    <option value="500" {{ $currentMinDuration == '500' ? 'selected' : '' }}>&gt; 500ms</option>
                    <option value="1000" {{ $currentMinDuration == '1000' ? 'selected' : '' }}>&gt; 1 second</option>
                    <option value="2000" {{ $currentMinDuration == '2000' ? 'selected' : '' }}>&gt; 2 seconds</option>
                    <option value="5000" {{ $currentMinDuration == '5000' ? 'selected' : '' }}>&gt; 5 seconds</option>
                </select>

                @if($currentStatusGroup || $currentMinDuration)
                    <a href="{{ route('hashguardian.requests.index', array_filter(['since' => request('since'), 'until' => request('until')])) }}" class="wd-btn" style="padding: 6px 12px; font-size: 12px;">
                        Clear Filters
                    </a>
                @endif

                <span style="margin-left: auto; font-size: 12px; color: var(--wd-text-muted);">
                    Showing {{ $entries->count() }} entries
                </span>
            </form>
        </div>
    </div>

    {{-- Status Code Breakdown --}}
    @if(count($statusCodes) > 0)
        <div class="wd-card" style="margin-bottom: 20px;">
            <div class="wd-card-header">
                <span class="wd-card-title">Status Code Breakdown</span>
            </div>
            <div class="wd-card-body" style="padding: 16px 20px;">
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach(collect($statusCodes)->sortKeys() as $code => $count)
                        @php
                            $c = (int) $code;
                            $badgeClass = $c >= 500 ? 'wd-badge-danger' : ($c >= 400 ? 'wd-badge-warning' : ($c >= 300 ? 'wd-badge-muted' : 'wd-badge-success'));
                            $pct = $total > 0 ? ($count / $total) * 100 : 0;
                        @endphp
                        <a href="{{ route('hashguardian.requests.index', array_merge(request()->only(['since', 'until']), ['status_code' => $code])) }}"
                           style="text-decoration: none; display: flex; align-items: center; gap: 8px; background: var(--wd-bg-elevated); border: 1px solid var(--wd-border); border-radius: var(--wd-radius-sm); padding: 8px 14px; transition: all 0.2s; min-width: 100px;"
                           onmouseover="this.style.borderColor='var(--wd-accent)';this.style.transform='translateY(-1px)'"
                           onmouseout="this.style.borderColor='var(--wd-border)';this.style.transform='none'">
                            <span class="wd-badge {{ $badgeClass }}">{{ $code }}</span>
                            <span style="font-weight: 600; font-size: 14px; color: var(--wd-text-heading); font-variant-numeric: tabular-nums;">{{ number_format($count) }}</span>
                            <span style="font-size: 11px; color: var(--wd-text-muted);">{{ number_format($pct, 1) }}%</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Requests Table --}}
    <div class="wd-card">
        @if($entries->isEmpty())
            <div class="wd-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
                <div class="wd-empty-title">No requests found</div>
                <div class="wd-empty-text">There are no requests matching your filters.</div>
            </div>
        @else
            <table class="wd-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>URI</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php $c = $entry->content ?? []; @endphp
                        <tr>
                            <td>
                                <span class="wd-method wd-method-{{ strtolower($c['method'] ?? 'get') }}">{{ $c['method'] ?? 'GET' }}</span>
                            </td>
                            <td>
                                <a href="{{ route('hashguardian.requests.show', $entry->uuid) }}" style="color: var(--wd-primary); text-decoration: none;">
                                    {{ \Illuminate\Support\Str::limit($c['uri'] ?? $c['url'] ?? 'N/A', 80) }}
                                </a>
                            </td>
                            <td>
                                @php $status = $c['status'] ?? 200; @endphp
                                <span class="wd-badge {{ $status >= 500 ? 'wd-badge-danger' : ($status >= 400 ? 'wd-badge-warning' : 'wd-badge-success') }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td>
                                @php $dur = $entry->duration ?? 0; @endphp
                                <span class="wd-duration {{ $dur > 1000 ? 'wd-duration-slow' : ($dur > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                    {{ number_format($dur, 2) }}ms
                                </span>
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
                    <a href="?before={{ $entries->last()->id }}{{ $currentStatusGroup ? '&status_group=' . $currentStatusGroup : '' }}{{ $currentMinDuration ? '&min_duration=' . $currentMinDuration : '' }}{{ request('status_code') ? '&status_code=' . request('status_code') : '' }}" class="wd-btn">Load More</a>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function() {
    var chartDataUrl = '{{ route("hashguardian.requests.chart-data") }}';

    var chartState = {
        labels: [], data: null,
        pad: { top: 28, right: 20, bottom: 6, left: 52 },
        durPad: { top: 10, right: 20, bottom: 32, left: 52 },
        zoom: { level: 1, center: 0.5, minLevel: 1, maxLevel: 10 }
    };

    function init() {
        var sel = document.getElementById('wd-req-period');
        if (sel) sel.addEventListener('change', loadData);
        loadData();
        window.addEventListener('resize', function() {
            if (chartState.data) { drawVolumeChart(); drawDurationChart(); }
        });
        setupZoom();
    }

    function loadData() {
        var period = (document.getElementById('wd-req-period') || {}).value || '3h';
        fetch(chartDataUrl + '?period=' + period, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(raw) {
            var rows = Array.isArray(raw) ? raw : (raw.data || []);
            var labels = [], s2xx = [], s3xx = [], s4xx = [], s5xx = [], avgDur = [], total = [];
            rows.forEach(function(r) {
                labels.push(r.time);
                s2xx.push(r['2xx'] || 0);
                s3xx.push(r['3xx'] || 0);
                s4xx.push(r['4xx'] || 0);
                s5xx.push(r['5xx'] || 0);
                avgDur.push(r.avg_duration || 0);
                total.push(r.count || 0);
            });
            chartState.labels = labels;
            chartState.data = { s2xx: s2xx, s3xx: s3xx, s4xx: s4xx, s5xx: s5xx, avg_duration: avgDur, total: total };
            drawVolumeChart();
            drawDurationChart();
            setupHover();
        })
        .catch(function(e) { console.error('Chart load failed:', e); });
    }

    function getThemeColors() {
        var isLight = document.body.classList.contains('wd-light');
        return {
            text: isLight ? '#94a3b8' : '#475569',
            grid: isLight ? '#f1f5f9' : 'rgba(148,163,184,0.06)',
            crosshair: isLight ? 'rgba(0,0,0,0.12)' : 'rgba(129,140,248,0.25)',
            isLight: isLight,
            bg: isLight ? '#ffffff' : '#111827'
        };
    }

    function prepCanvas(id) {
        var canvas = document.getElementById(id);
        if (!canvas) return null;
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        return { canvas: canvas, ctx: ctx, w: rect.width, h: rect.height };
    }

    function getX(idx, len, left, cw) {
        return left + (idx / Math.max(1, len - 1)) * cw;
    }

    function getY(val, maxVal, top, ch) {
        return top + ch - (val / Math.max(1, maxVal)) * ch;
    }

    function drawSmoothLine(ctx, points) {
        if (points.length < 2) return;
        ctx.moveTo(points[0].x, points[0].y);
        if (points.length === 2) {
            ctx.lineTo(points[1].x, points[1].y);
            return;
        }
        for (var i = 0; i < points.length - 1; i++) {
            var p0 = points[Math.max(0, i - 1)];
            var p1 = points[i];
            var p2 = points[i + 1];
            var p3 = points[Math.min(points.length - 1, i + 2)];
            var tension = 0.3;
            var cp1x = p1.x + (p2.x - p0.x) * tension;
            var cp1y = p1.y + (p2.y - p0.y) * tension;
            var cp2x = p2.x - (p3.x - p1.x) * tension;
            var cp2y = p2.y - (p3.y - p1.y) * tension;
            ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, p2.x, p2.y);
        }
    }

    function roundedRect(ctx, x, y, w, h, r, topOnly) {
        ctx.beginPath();
        if (topOnly) {
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.lineTo(x + w, y + h);
            ctx.lineTo(x, y + h);
            ctx.arcTo(x, y, x + w, y, r);
        } else {
            ctx.roundRect(x, y, w, h, r);
        }
        ctx.closePath();
    }

    // ── Zoom helpers ──
    function getVisibleRange() {
        var z = chartState.zoom;
        var total = chartState.labels.length;
        if (total === 0) return { start: 0, end: 0, labels: [], slice: function() { return []; } };
        var windowSize = Math.max(4, Math.round(total / z.level));
        var maxStart = total - windowSize;
        var start = Math.round(z.center * total - windowSize / 2);
        start = Math.max(0, Math.min(start, maxStart));
        var end = Math.min(total, start + windowSize);
        var visLabels = chartState.labels.slice(start, end);
        return {
            start: start, end: end, labels: visLabels,
            slice: function(arr) { return arr ? arr.slice(start, end) : []; }
        };
    }

    function setupZoom() {
        var btnIn = document.getElementById('wd-req-zoom-in');
        var btnOut = document.getElementById('wd-req-zoom-out');
        var btnReset = document.getElementById('wd-req-zoom-reset');
        var wrapper = document.querySelector('.wd-combined-chart-wrapper');
        if (!btnIn || !btnOut || !btnReset) return;

        function updateZoom() {
            var z = chartState.zoom;
            var pct = Math.round(z.level * 100);
            var lbl = document.getElementById('wd-req-zoom-label');
            if (lbl) lbl.textContent = pct + '%';
            btnOut.style.opacity = z.level <= z.minLevel ? '0.3' : '1';
            btnOut.style.pointerEvents = z.level <= z.minLevel ? 'none' : 'auto';
            btnIn.style.opacity = z.level >= z.maxLevel ? '0.3' : '1';
            btnIn.style.pointerEvents = z.level >= z.maxLevel ? 'none' : 'auto';
            btnReset.style.opacity = z.level <= z.minLevel ? '0.3' : '1';
            drawVolumeChart();
            drawDurationChart();
        }

        btnIn.addEventListener('click', function() {
            chartState.zoom.level = Math.min(chartState.zoom.maxLevel, chartState.zoom.level * 1.5);
            updateZoom();
        });
        btnOut.addEventListener('click', function() {
            chartState.zoom.level = Math.max(chartState.zoom.minLevel, chartState.zoom.level / 1.5);
            updateZoom();
        });
        btnReset.addEventListener('click', function() {
            chartState.zoom.level = 1;
            chartState.zoom.center = 0.5;
            updateZoom();
        });

        if (wrapper) {
            wrapper.addEventListener('wheel', function(e) {
                if (!e.ctrlKey && !e.metaKey) return;
                e.preventDefault();
                var z = chartState.zoom;
                if (e.deltaY < 0) {
                    z.level = Math.min(z.maxLevel, z.level * 1.15);
                } else {
                    z.level = Math.max(z.minLevel, z.level / 1.15);
                }
                var rect = wrapper.getBoundingClientRect();
                var mouseRatio = (e.clientX - rect.left) / rect.width;
                z.center = Math.max(0, Math.min(1, mouseRatio));
                updateZoom();
            }, { passive: false });
        }

        var isPanning = false, panStartX = 0, panStartCenter = 0;
        if (wrapper) {
            wrapper.addEventListener('mousedown', function(e) {
                if (chartState.zoom.level <= 1) return;
                if (e.shiftKey || e.button === 1) {
                    isPanning = true;
                    panStartX = e.clientX;
                    panStartCenter = chartState.zoom.center;
                    e.preventDefault();
                }
            });
            window.addEventListener('mousemove', function(e) {
                if (!isPanning) return;
                var rect = wrapper.getBoundingClientRect();
                var dx = (e.clientX - panStartX) / rect.width;
                chartState.zoom.center = Math.max(0, Math.min(1, panStartCenter - dx));
                drawVolumeChart();
                drawDurationChart();
                var lbl = document.getElementById('wd-req-zoom-label');
                if (lbl) lbl.textContent = Math.round(chartState.zoom.level * 100) + '%';
            });
            window.addEventListener('mouseup', function() { isPanning = false; });
        }
    }

    // ── Volume Chart (stacked bars by status group) ──
    function drawVolumeChart(hoverIdx) {
        var c = prepCanvas('wd-chart-req-volume');
        if (!c) return;
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var p = chartState.pad;
        var cw = w - p.left - p.right, ch = h - p.top - p.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);

        if (!labels || labels.length === 0) {
            ctx.fillStyle = tc.text; ctx.font = '13px Inter,sans-serif'; ctx.textAlign = 'center';
            ctx.fillText('No data available', w / 2, h / 2);
            return;
        }

        var series = [
            { key: 's2xx', label: '2xx', color: '#34d399', data: vr.slice(data.s2xx) },
            { key: 's3xx', label: '3xx', color: '#818cf8', data: vr.slice(data.s3xx) },
            { key: 's4xx', label: '4xx', color: '#fbbf24', data: vr.slice(data.s4xx) },
            { key: 's5xx', label: '5xx', color: '#f87171', data: vr.slice(data.s5xx) }
        ];

        var maxStack = 1;
        for (var i = 0; i < labels.length; i++) {
            var s = 0;
            series.forEach(function(sr) { s += (sr.data[i]) || 0; });
            if (s > maxStack) maxStack = s;
        }
        maxStack = Math.ceil(maxStack * 1.15) || 1;

        // Grid
        var gridLines = 5;
        for (var g = 0; g <= gridLines; g++) {
            var gy = p.top + (ch / gridLines) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.left, gy); ctx.lineTo(p.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxStack - (maxStack / gridLines) * g), p.left - 10, gy + 3);
        }

        // Stacked bars
        var barW = Math.max(3, Math.min(18, (cw / Math.max(1, labels.length)) * 0.6));
        var barR = Math.min(2, barW / 2);

        for (var j = 0; j < labels.length; j++) {
            var bx = getX(j, labels.length, p.left, cw) - barW / 2;
            var base = p.top + ch;
            var isHover = typeof hoverIdx === 'number' && j === hoverIdx;

            for (var si = 0; si < series.length; si++) {
                var sr = series[si];
                var val = sr.data[j] || 0;
                if (val <= 0) continue;
                var segH = (val / maxStack) * ch;

                ctx.globalAlpha = isHover ? 1 : 0.65;
                ctx.fillStyle = sr.color;

                var isTopSegment = true;
                for (var k = si + 1; k < series.length; k++) {
                    if ((series[k].data[j]) > 0) { isTopSegment = false; break; }
                }

                if (isTopSegment && barR > 0) {
                    roundedRect(ctx, bx, base - segH, barW, segH, barR, true);
                    ctx.fill();
                } else {
                    ctx.fillRect(bx, base - segH, barW, segH);
                }
                base -= segH;
            }
            ctx.globalAlpha = 1;
        }

        // Legend
        ctx.font = '10px Inter,sans-serif';
        var lx = p.left;
        series.forEach(function(sr) {
            ctx.beginPath();
            ctx.arc(lx + 4, 10, 4, 0, Math.PI * 2);
            ctx.fillStyle = sr.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left';
            ctx.fillText(sr.label, lx + 12, 13);
            lx += ctx.measureText(sr.label).width + 26;
        });

        // Crosshair
        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, p.left, cw);
            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, p.top); ctx.lineTo(hx, p.top + ch); ctx.stroke();
            ctx.setLineDash([]);
        }
    }

    // ── Duration Chart (line with gradient) ──
    function drawDurationChart(hoverIdx) {
        var c = prepCanvas('wd-chart-req-duration');
        if (!c) return;
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var p = chartState.durPad;
        var cw = w - p.left - p.right, ch = h - p.top - p.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);

        if (!labels || labels.length === 0 || !data.avg_duration) return;

        var durations = vr.slice(data.avg_duration);
        var maxDur = Math.max.apply(null, durations) || 1;
        maxDur = Math.ceil(maxDur * 1.15) || 1;

        // Grid
        for (var g = 0; g <= 3; g++) {
            var gy = p.top + (ch / 3) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.left, gy); ctx.lineTo(p.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxDur - (maxDur / 3) * g) + 'ms', p.left - 10, gy + 3);
        }

        var points = [];
        for (var k = 0; k < durations.length; k++) {
            points.push({
                x: getX(k, durations.length, p.left, cw),
                y: getY(durations[k], maxDur, p.top, ch)
            });
        }

        var lineColor = '#818cf8';

        // Gradient fill
        ctx.beginPath();
        drawSmoothLine(ctx, points);
        var lastPt = points[points.length - 1];
        var firstPt = points[0];
        ctx.lineTo(lastPt.x, p.top + ch);
        ctx.lineTo(firstPt.x, p.top + ch);
        ctx.closePath();
        var grad = ctx.createLinearGradient(0, p.top, 0, p.top + ch);
        grad.addColorStop(0, lineColor + '18');
        grad.addColorStop(1, lineColor + '02');
        ctx.fillStyle = grad;
        ctx.fill();

        // Line stroke
        ctx.beginPath();
        drawSmoothLine(ctx, points);
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();

        // X-axis labels
        var labelStep = Math.max(1, Math.floor(labels.length / 8));
        ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
        for (var t = 0; t < labels.length; t += labelStep) {
            var tx = getX(t, labels.length, p.left, cw);
            var d = new Date(labels[t]);
            ctx.fillText(d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), tx, h - 4);
        }

        // Hover dot
        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, p.left, cw);
            var dy = getY(durations[hoverIdx], maxDur, p.top, ch);

            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, p.top); ctx.lineTo(hx, p.top + ch); ctx.stroke();
            ctx.setLineDash([]);

            ctx.beginPath(); ctx.arc(hx, dy, 8, 0, Math.PI * 2);
            ctx.fillStyle = lineColor + '20'; ctx.fill();
            ctx.beginPath(); ctx.arc(hx, dy, 4, 0, Math.PI * 2);
            ctx.fillStyle = lineColor; ctx.fill();
            ctx.strokeStyle = tc.bg; ctx.lineWidth = 2; ctx.stroke();
        }

        // Legend
        ctx.font = '10px Inter,sans-serif';
        var legendStart = w - ctx.measureText('Avg Duration').width - 20;
        ctx.beginPath(); ctx.arc(legendStart + 4, 6, 3, 0, Math.PI * 2);
        ctx.fillStyle = lineColor; ctx.fill();
        ctx.fillStyle = tc.text; ctx.textAlign = 'left';
        ctx.fillText('Avg Duration', legendStart + 10, 9);
    }

    // ── Hover + Tooltip ──
    function setupHover() {
        var volCanvas = document.getElementById('wd-chart-req-volume');
        var durCanvas = document.getElementById('wd-chart-req-duration');
        var tooltip = document.getElementById('wd-req-tooltip');
        if (!volCanvas || !durCanvas || !tooltip) return;

        var wrapper = volCanvas.closest('.wd-combined-chart-wrapper');

        function getIdx(e, canvas, pad) {
            var rect = canvas.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var cw = rect.width - pad.left - pad.right;
            var relX = (x - pad.left) / cw;
            var vr = getVisibleRange();
            if (relX < 0 || relX > 1 || !vr.labels.length) return -1;
            return Math.round(relX * (vr.labels.length - 1));
        }

        function onMove(e) {
            var canvas = e.currentTarget;
            var pad = (canvas.id === 'wd-chart-req-volume') ? chartState.pad : chartState.durPad;
            var idx = getIdx(e, canvas, pad);
            if (idx < 0) { hideTooltip(); return; }
            drawVolumeChart(idx);
            drawDurationChart(idx);
            showTooltip(idx, e, wrapper);
        }

        function hideTooltip() {
            tooltip.style.display = 'none';
            drawVolumeChart();
            drawDurationChart();
        }

        volCanvas.addEventListener('mousemove', onMove);
        durCanvas.addEventListener('mousemove', onMove);
        volCanvas.addEventListener('mouseleave', hideTooltip);
        durCanvas.addEventListener('mouseleave', hideTooltip);

        volCanvas.style.cursor = 'crosshair';
        durCanvas.style.cursor = 'crosshair';
    }

    function showTooltip(idx, event, wrapper) {
        var tip = document.getElementById('wd-req-tooltip');
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        if (!tip || !data || idx < 0 || idx >= labels.length) return;

        var gi = vr.start + idx;
        var dt = new Date(labels[idx]);
        var timeStr = dt.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ', ' +
                      dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        var total = (data.total && data.total[gi]) || 0;

        var html = '<div class="wd-tip-time">' + timeStr + '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow(null, 'Total', total);
        html += '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow('#34d399', '2xx', (data.s2xx && data.s2xx[gi]) || 0);
        html += tipRow('#818cf8', '3xx', (data.s3xx && data.s3xx[gi]) || 0);
        html += tipRow('#fbbf24', '4xx', (data.s4xx && data.s4xx[gi]) || 0);
        html += tipRow('#f87171', '5xx', (data.s5xx && data.s5xx[gi]) || 0);
        html += '</div>';
        html += '<div class="wd-tip-section wd-tip-activity">';
        html += tipRow('#818cf8', 'Avg Duration', ((data.avg_duration && data.avg_duration[gi]) || 0).toFixed(1) + 'ms');
        html += '</div>';

        tip.innerHTML = html;
        tip.style.display = 'block';

        var wrapRect = wrapper.getBoundingClientRect();
        var tipW = tip.offsetWidth;
        var tipH = tip.offsetHeight;
        var mx = event.clientX - wrapRect.left + 16;
        var my = event.clientY - wrapRect.top - tipH / 2;

        if (mx + tipW > wrapRect.width - 8) mx = event.clientX - wrapRect.left - tipW - 16;
        if (my < 4) my = 4;
        if (my + tipH > wrapRect.height - 4) my = wrapRect.height - tipH - 4;

        tip.style.left = mx + 'px';
        tip.style.top = my + 'px';
    }

    function tipRow(color, label, value) {
        var dot = color ? '<span class="wd-tip-dot" style="background:' + color + ';box-shadow:0 0 4px ' + color + '40"></span>' : '<span class="wd-tip-dot" style="background:transparent"></span>';
        return '<div class="wd-tip-row">' + dot + '<span class="wd-tip-label">' + label + '</span><span class="wd-tip-val">' + value + '</span></div>';
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endpush
