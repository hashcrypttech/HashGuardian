@extends('hashguardian::layouts.app')

@section('title', 'Server Monitoring')

@section('actions')
    <select id="wd-server-period" class="wd-date-input" style="width: auto;">
        <option value="1h">Last 1 Hour</option>
        <option value="3h" selected>Last 3 Hours</option>
        <option value="6h">Last 6 Hours</option>
        <option value="24h">Last 24 Hours</option>
        <option value="7d">Last 7 Days</option>
        <option value="30d">Last 30 Days</option>
    </select>
@endsection

@php
    $fmtBytes = function($bytes) {
        if ($bytes <= 0) return '0 B';
        if ($bytes == -1) return 'Unlimited';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log(max(1, $bytes), 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
    };
@endphp

@section('content')
    @if(! $monitoringEnabled)
        <div style="background: var(--wd-badge-warning-bg, #f59e0b22); border: 1px solid #f59e0b44; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
            <strong style="color: #f59e0b;">Server monitoring is disabled.</strong>
            <p style="margin: 8px 0 0; color: var(--wd-text-muted);">
                Set <code>HASHGUARDIAN_SERVER_MONITORING=true</code> in your <code>.env</code> file and run
                <code>php artisan hashguardian:monitor</code> to start collecting metrics.
            </p>
        </div>
    @endif

    {{-- Overview Cards --}}
    <div class="wd-stats-grid">
        @if($latest)
            <div class="wd-stat-card">
                <div class="wd-stat-label">CPU Usage</div>
                <div class="wd-stat-value">{{ number_format($latest->cpu_percent, 1) }}%</div>
                <div class="wd-stat-meta">Load: {{ number_format($latest->cpu_load_1m, 2) }} / {{ number_format($latest->cpu_load_5m, 2) }} / {{ number_format($latest->cpu_load_15m, 2) }}</div>
                <div class="wd-stat-meta">{{ $latest->cpu_cores }} cores</div>
            </div>
            <div class="wd-stat-card">
                <div class="wd-stat-label">RAM Usage</div>
                <div class="wd-stat-value">{{ number_format($latest->ram_percent, 1) }}%</div>
                <div class="wd-stat-meta">{{ $fmtBytes($latest->ram_used) }} / {{ $fmtBytes($latest->ram_total) }}</div>
                <div class="wd-stat-meta">Available: {{ $fmtBytes($latest->ram_available) }}</div>
            </div>
            <div class="wd-stat-card">
                <div class="wd-stat-label">SWAP</div>
                <div class="wd-stat-value">{{ number_format($latest->swap_percent, 1) }}%</div>
                <div class="wd-stat-meta">{{ $fmtBytes($latest->swap_used) }} / {{ $fmtBytes($latest->swap_total) }}</div>
            </div>
            <div class="wd-stat-card">
                <div class="wd-stat-label">Disk ({{ $latest->disk_mount }})</div>
                <div class="wd-stat-value">{{ number_format($latest->disk_percent, 1) }}%</div>
                <div class="wd-stat-meta">{{ $fmtBytes($latest->disk_used) }} / {{ $fmtBytes($latest->disk_total) }}</div>
                <div class="wd-stat-meta">Free: {{ $fmtBytes($latest->disk_free) }}</div>
            </div>
            <div class="wd-stat-card">
                <div class="wd-stat-label">Processes</div>
                <div class="wd-stat-value">{{ number_format($latest->process_count) }}</div>
                @if($latest->php_fpm_active !== null)
                    <div class="wd-stat-meta">FPM Active: {{ $latest->php_fpm_active }} / Idle: {{ $latest->php_fpm_idle }}</div>
                @endif
            </div>
            <div class="wd-stat-card">
                <div class="wd-stat-label">PHP Memory Limit</div>
                <div class="wd-stat-value">{{ $latest->php_memory_limit ? $fmtBytes($latest->php_memory_limit) : 'N/A' }}</div>
                @if($latest->opcache_used !== null)
                    <div class="wd-stat-meta">OPcache: {{ number_format($latest->opcache_used, 1) }}%</div>
                @endif
            </div>
        @else
            <div class="wd-stat-card" style="grid-column: 1 / -1;">
                <div class="wd-stat-label">No Data</div>
                <div class="wd-stat-meta">No server metrics recorded yet. Run <code>php artisan hashguardian:monitor</code></div>
            </div>
        @endif
    </div>

    {{-- Combined Chart --}}
    <div class="wd-combined-chart-wrapper">
        <div class="wd-chart-panel" style="border-bottom: none; border-radius: 10px 10px 0 0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <h3 class="wd-chart-title" style="margin-bottom:0;">Server Resources</h3>
                <div id="wd-zoom-controls" style="display:flex;align-items:center;gap:6px;">
                    <span id="wd-zoom-label" style="font-size:11px;color:var(--wd-text-muted);margin-right:4px;">100%</span>
                    <button id="wd-zoom-in" title="Zoom In" style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-heading);cursor:pointer;font-size:16px;font-weight:600;line-height:1;transition:all .15s ease;">+</button>
                    <button id="wd-zoom-out" title="Zoom Out" style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-heading);cursor:pointer;font-size:16px;font-weight:600;line-height:1;transition:all .15s ease;">&minus;</button>
                    <button id="wd-zoom-reset" title="Reset Zoom" style="display:inline-flex;align-items:center;justify-content:center;height:28px;padding:0 10px;border-radius:6px;border:1px solid var(--wd-border-subtle);background:var(--wd-surface);color:var(--wd-text-muted);cursor:pointer;font-size:11px;font-weight:500;transition:all .15s ease;">Reset</button>
                </div>
            </div>
            <canvas id="wd-chart-resources" style="max-height:300px;" height="250"></canvas>
        </div>
        <div class="wd-chart-panel" style="border-top: none; border-radius: 0; padding-top: 0;">
            <h3 class="wd-chart-title" style="font-size: 12px; margin-bottom: 8px;">Application Activity</h3>
            <canvas id="wd-chart-activity" style="max-height:120px;" height="100"></canvas>
        </div>
        <div class="wd-chart-panel" style="border-top: none; border-radius: 0 0 10px 10px; padding-top: 0;">
            <h3 class="wd-chart-title" style="font-size: 12px; margin-bottom: 8px;">Query Counts</h3>
            <canvas id="wd-chart-queries" style="max-height:120px;" height="100"></canvas>
        </div>
        {{-- Shared tooltip --}}
        <div id="wd-chart-tooltip" class="wd-chart-tooltip"></div>
    </div>

    {{-- Spike Investigation --}}
    <div class="wd-chart-panel" style="margin-top: 24px;">
        <h3 class="wd-chart-title">
            Spike Investigation
            <span style="font-weight: 400; font-size: 13px; color: var(--wd-text-muted);"> &mdash; Click on chart to investigate</span>
        </h3>
        <div id="wd-correlate-results" class="wd-correlate-container">
            <p style="color: var(--wd-text-muted); text-align: center; padding: 20px;">
                Click on the chart above to see requests, jobs, and commands active during that time window.
            </p>
        </div>
    </div>

    @if($latest)
        <p style="text-align: right; color: var(--wd-text-muted); font-size: 12px; margin-top: 16px;">
            Last metric: {{ \Carbon\Carbon::parse($latest->created_at)->diffForHumans() }} (interval: {{ $interval }}s)
        </p>
    @endif
@endsection

@push('scripts')
<script>
(function() {
    var chartDataUrl = '{{ route("hashguardian.server.chart-data") }}';
    var correlateUrl = '{{ route("hashguardian.server.correlate") }}';
    var hashguardianPath = '{{ config("hashguardian.path", "hashguardian") }}';

    var chartState = {
        labels: [], data: null,
        pad: { top: 28, right: 20, bottom: 6, left: 52 },
        actPad: { top: 10, right: 20, bottom: 6, left: 52 },
        qryPad: { top: 10, right: 20, bottom: 32, left: 52 },
        zoom: { level: 1, center: 0.5, minLevel: 1, maxLevel: 10 }
    };

    function init() {
        var sel = document.getElementById('wd-server-period');
        if (sel) sel.addEventListener('change', loadData);
        loadData();
        window.addEventListener('resize', function() {
            if (chartState.data) { drawResourceChart(); drawActivityChart(); drawQueryChart(); }
        });
        setupZoom();
    }

    function loadData() {
        var period = (document.getElementById('wd-server-period') || {}).value || '3h';
        fetch(chartDataUrl + '?period=' + period, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            chartState.data = data;
            chartState.labels = data.labels;
            drawResourceChart();
            drawActivityChart();
            drawQueryChart();
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
        return top + ch - (val / maxVal) * ch;
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

    // ── Zoom helpers ──
    function getVisibleRange() {
        var z = chartState.zoom;
        var total = chartState.labels.length;
        if (total === 0) return { start: 0, end: 0, labels: [], slice: function(a) { return []; } };
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
        var btnIn = document.getElementById('wd-zoom-in');
        var btnOut = document.getElementById('wd-zoom-out');
        var btnReset = document.getElementById('wd-zoom-reset');
        var wrapper = document.querySelector('.wd-combined-chart-wrapper');
        if (!btnIn || !btnOut || !btnReset) return;

        function updateZoom() {
            var z = chartState.zoom;
            var pct = Math.round(z.level * 100);
            var lbl = document.getElementById('wd-zoom-label');
            if (lbl) lbl.textContent = pct + '%';
            btnOut.style.opacity = z.level <= z.minLevel ? '0.3' : '1';
            btnOut.style.pointerEvents = z.level <= z.minLevel ? 'none' : 'auto';
            btnIn.style.opacity = z.level >= z.maxLevel ? '0.3' : '1';
            btnIn.style.pointerEvents = z.level >= z.maxLevel ? 'none' : 'auto';
            btnReset.style.opacity = z.level <= z.minLevel ? '0.3' : '1';
            drawResourceChart();
            drawActivityChart();
            drawQueryChart();
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
                z.center = mouseRatio;
                z.center = Math.max(0, Math.min(1, z.center));
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
                drawResourceChart();
                drawActivityChart();
                drawQueryChart();
                var lbl = document.getElementById('wd-zoom-label');
                if (lbl) lbl.textContent = Math.round(chartState.zoom.level * 100) + '%';
            });
            window.addEventListener('mouseup', function() { isPanning = false; });
        }
    }

    // ── Resource Chart (top) ──
    function drawResourceChart(hoverIdx) {
        var c = prepCanvas('wd-chart-resources');
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

        var datasets = [
            { label: 'CPU',  data: vr.slice(data.cpu),  color: '#f87171', colorFill: 'rgba(248,113,113,0.08)' },
            { label: 'RAM',  data: vr.slice(data.ram),  color: '#818cf8', colorFill: 'rgba(129,140,248,0.08)' },
            { label: 'SWAP', data: vr.slice(data.swap), color: '#fbbf24', colorFill: 'rgba(251,191,36,0.05)' },
            { label: 'Disk', data: vr.slice(data.disk), color: '#64748b', colorFill: 'rgba(100,116,139,0.04)' }
        ];

        for (var i = 0; i <= 5; i++) {
            var y = p.top + (ch / 5) * i;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.left, y); ctx.lineTo(p.left + cw, y); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText((100 - i * 20) + '%', p.left - 10, y + 3);
        }

        datasets.forEach(function(ds) {
            if (!ds.data || ds.data.length === 0) return;

            var points = [];
            for (var k = 0; k < ds.data.length; k++) {
                points.push({
                    x: getX(k, ds.data.length, p.left, cw),
                    y: getY(ds.data[k], 100, p.top, ch)
                });
            }

            ctx.beginPath();
            drawSmoothLine(ctx, points);
            var lastPt = points[points.length - 1];
            var firstPt = points[0];
            ctx.lineTo(lastPt.x, p.top + ch);
            ctx.lineTo(firstPt.x, p.top + ch);
            ctx.closePath();

            var grad = ctx.createLinearGradient(0, p.top, 0, p.top + ch);
            grad.addColorStop(0, ds.color + '18');
            grad.addColorStop(1, ds.color + '02');
            ctx.fillStyle = grad;
            ctx.fill();

            ctx.beginPath();
            drawSmoothLine(ctx, points);
            ctx.strokeStyle = ds.color;
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.stroke();
        });

        var lx = p.left;
        ctx.font = '10px Inter,sans-serif';
        datasets.forEach(function(ds) {
            ctx.beginPath();
            ctx.arc(lx + 4, 10, 4, 0, Math.PI * 2);
            ctx.fillStyle = ds.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left';
            ctx.fillText(ds.label, lx + 12, 13);
            lx += ctx.measureText(ds.label).width + 26;
        });

        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, p.left, cw);

            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, p.top); ctx.lineTo(hx, p.top + ch); ctx.stroke();
            ctx.setLineDash([]);

            datasets.forEach(function(ds) {
                if (!ds.data || !ds.data[hoverIdx] && ds.data[hoverIdx] !== 0) return;
                var dy = getY(ds.data[hoverIdx], 100, p.top, ch);

                ctx.beginPath(); ctx.arc(hx, dy, 8, 0, Math.PI * 2);
                ctx.fillStyle = ds.color + '20'; ctx.fill();

                ctx.beginPath(); ctx.arc(hx, dy, 4, 0, Math.PI * 2);
                ctx.fillStyle = ds.color; ctx.fill();
                ctx.strokeStyle = tc.bg; ctx.lineWidth = 2; ctx.stroke();
            });
        }
    }

    // ── Activity Chart (middle) ──
    function drawActivityChart(hoverIdx) {
        var c = prepCanvas('wd-chart-activity');
        if (!c) return;
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var p = chartState.actPad;
        var cw = w - p.left - p.right, ch = h - p.top - p.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);

        if (!labels || labels.length === 0 || !data.activity) return;

        var act = {
            requests: vr.slice(data.activity.requests),
            jobs: vr.slice(data.activity.jobs),
            commands: vr.slice(data.activity.commands)
        };
        var maxStack = 1;
        for (var i = 0; i < labels.length; i++) {
            var s = (act.requests[i] || 0) + (act.jobs[i] || 0) + (act.commands[i] || 0);
            if (s > maxStack) maxStack = s;
        }
        maxStack = Math.ceil(maxStack * 1.2) || 1;

        for (var g = 0; g <= 3; g++) {
            var gy = p.top + (ch / 3) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.left, gy); ctx.lineTo(p.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxStack - (maxStack / 3) * g), p.left - 10, gy + 3);
        }

        var barW = Math.max(2, Math.min(12, (cw / Math.max(1, labels.length)) * 0.55));
        var barR = Math.min(2, barW / 2);
        var colors = { requests: '#818cf8', jobs: '#34d399', commands: '#fbbf24' };

        for (var j = 0; j < labels.length; j++) {
            var bx = getX(j, labels.length, p.left, cw) - barW / 2;
            var base = p.top + ch;
            var isHover = typeof hoverIdx === 'number' && j === hoverIdx;
            var segments = [
                { key: 'requests', val: act.requests[j] || 0 },
                { key: 'jobs', val: act.jobs[j] || 0 },
                { key: 'commands', val: act.commands[j] || 0 }
            ];
            var totalH = 0;
            segments.forEach(function(seg) { totalH += seg.val; });
            var isLast = true;

            for (var si = segments.length - 1; si >= 0; si--) {
                var seg = segments[si];
                if (seg.val <= 0) continue;
                var segH = (seg.val / maxStack) * ch;
                ctx.globalAlpha = isHover ? 1 : 0.6;
                ctx.fillStyle = colors[seg.key];

                if (isLast && barR > 0) {
                    roundedRect(ctx, bx, base - segH, barW, segH, barR, true);
                    ctx.fill();
                    isLast = false;
                } else {
                    ctx.fillRect(bx, base - segH, barW, segH);
                }
                base -= segH;
            }
            ctx.globalAlpha = 1;
        }

        ctx.font = '10px Inter,sans-serif';
        var legendItems = [
            { label: 'Requests', color: colors.requests },
            { label: 'Jobs', color: colors.jobs },
            { label: 'Commands', color: colors.commands }
        ];
        var totalLegendW = 0;
        legendItems.forEach(function(li) { totalLegendW += ctx.measureText(li.label).width + 22; });
        var legendStart = w - totalLegendW - 8;
        legendItems.forEach(function(li) {
            ctx.beginPath(); ctx.arc(legendStart + 4, 6, 3, 0, Math.PI * 2);
            ctx.fillStyle = li.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left';
            ctx.fillText(li.label, legendStart + 10, 9);
            legendStart += ctx.measureText(li.label).width + 22;
        });

        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, p.left, cw);
            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, p.top); ctx.lineTo(hx, p.top + ch); ctx.stroke();
            ctx.setLineDash([]);
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

    // ── Query Chart (bottom) ──
    function drawQueryChart(hoverIdx) {
        var c = prepCanvas('wd-chart-queries');
        if (!c) return;
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var p = chartState.qryPad;
        var cw = w - p.left - p.right, ch = h - p.top - p.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);

        if (!labels || labels.length === 0 || !data.queries) return;

        var qry = {
            select: vr.slice(data.queries.select),
            insert: vr.slice(data.queries.insert),
            update: vr.slice(data.queries.update),
            delete_q: vr.slice(data.queries['delete'])
        };
        var maxStack = 1;
        for (var i = 0; i < labels.length; i++) {
            var s = (qry.select[i] || 0) + (qry.insert[i] || 0) + (qry.update[i] || 0) + (qry.delete_q[i] || 0);
            if (s > maxStack) maxStack = s;
        }
        maxStack = Math.ceil(maxStack * 1.2) || 1;

        for (var g = 0; g <= 3; g++) {
            var gy = p.top + (ch / 3) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.left, gy); ctx.lineTo(p.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxStack - (maxStack / 3) * g), p.left - 10, gy + 3);
        }

        var barW = Math.max(2, Math.min(12, (cw / Math.max(1, labels.length)) * 0.55));
        var barR = Math.min(2, barW / 2);
        var colors = { select: '#60a5fa', insert: '#34d399', update: '#fbbf24', delete_q: '#f87171' };

        for (var j = 0; j < labels.length; j++) {
            var bx = getX(j, labels.length, p.left, cw) - barW / 2;
            var base = p.top + ch;
            var isHover = typeof hoverIdx === 'number' && j === hoverIdx;
            var segments = [
                { key: 'select', val: qry.select[j] || 0 },
                { key: 'insert', val: qry.insert[j] || 0 },
                { key: 'update', val: qry.update[j] || 0 },
                { key: 'delete_q', val: qry.delete_q[j] || 0 }
            ];
            var isLast = true;

            for (var si = segments.length - 1; si >= 0; si--) {
                var seg = segments[si];
                if (seg.val <= 0) continue;
                var segH = (seg.val / maxStack) * ch;
                ctx.globalAlpha = isHover ? 1 : 0.6;
                ctx.fillStyle = colors[seg.key];

                if (isLast && barR > 0) {
                    roundedRect(ctx, bx, base - segH, barW, segH, barR, true);
                    ctx.fill();
                    isLast = false;
                } else {
                    ctx.fillRect(bx, base - segH, barW, segH);
                }
                base -= segH;
            }
            ctx.globalAlpha = 1;
        }

        var labelStep = Math.max(1, Math.floor(labels.length / 8));
        ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
        for (var t = 0; t < labels.length; t += labelStep) {
            var tx = getX(t, labels.length, p.left, cw);
            var d = new Date(labels[t]);
            ctx.fillText(d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), tx, h - 4);
        }

        ctx.font = '10px Inter,sans-serif';
        var legendItems = [
            { label: 'SELECT', color: colors.select },
            { label: 'INSERT', color: colors.insert },
            { label: 'UPDATE', color: colors.update },
            { label: 'DELETE', color: colors.delete_q }
        ];
        var totalLegendW = 0;
        legendItems.forEach(function(li) { totalLegendW += ctx.measureText(li.label).width + 22; });
        var legendStart = w - totalLegendW - 8;
        legendItems.forEach(function(li) {
            ctx.beginPath(); ctx.arc(legendStart + 4, 6, 3, 0, Math.PI * 2);
            ctx.fillStyle = li.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left';
            ctx.fillText(li.label, legendStart + 10, 9);
            legendStart += ctx.measureText(li.label).width + 22;
        });

        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, p.left, cw);
            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, p.top); ctx.lineTo(hx, p.top + ch); ctx.stroke();
            ctx.setLineDash([]);
        }
    }

    // ── Hover + Tooltip ──
    function getPadForCanvas(canvas) {
        if (canvas.id === 'wd-chart-resources') return chartState.pad;
        if (canvas.id === 'wd-chart-queries') return chartState.qryPad;
        return chartState.actPad;
    }

    function setupHover() {
        var resCanvas = document.getElementById('wd-chart-resources');
        var actCanvas = document.getElementById('wd-chart-activity');
        var qryCanvas = document.getElementById('wd-chart-queries');
        var tooltip = document.getElementById('wd-chart-tooltip');
        if (!resCanvas || !actCanvas || !tooltip) return;

        var wrapper = resCanvas.closest('.wd-combined-chart-wrapper');
        var allCanvases = [resCanvas, actCanvas];
        if (qryCanvas) allCanvases.push(qryCanvas);

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
            var pad = getPadForCanvas(canvas);
            var idx = getIdx(e, canvas, pad);
            if (idx < 0) { hideTooltip(); return; }
            drawResourceChart(idx);
            drawActivityChart(idx);
            drawQueryChart(idx);
            showTooltip(idx, e, wrapper);
        }

        function hideTooltip() {
            tooltip.style.display = 'none';
            drawResourceChart();
            drawActivityChart();
            drawQueryChart();
        }

        allCanvases.forEach(function(cv) {
            cv.addEventListener('mousemove', onMove);
            cv.addEventListener('mouseleave', hideTooltip);
            cv.style.cursor = 'crosshair';
        });

        function onClick(e) {
            var canvas = e.currentTarget;
            var pad = getPadForCanvas(canvas);
            var idx = getIdx(e, canvas, pad);
            var vr = getVisibleRange();
            if (idx < 0 || !vr.labels[idx]) return;
            var ts = new Date(vr.labels[idx]).getTime();
            loadCorrelation(
                fmtLocalDateTime(new Date(ts - 2 * 60 * 1000)),
                fmtLocalDateTime(new Date(ts + 2 * 60 * 1000))
            );
        }

        allCanvases.forEach(function(cv) {
            cv.addEventListener('click', onClick);
        });
    }

    function showTooltip(idx, event, wrapper) {
        var tip = document.getElementById('wd-chart-tooltip');
        var data = chartState.data;
        var vr = getVisibleRange();
        var labels = vr.labels;
        if (!tip || !data || idx < 0 || idx >= labels.length) return;

        var gi = vr.start + idx;
        var dt = new Date(labels[idx]);
        var timeStr = dt.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ', ' +
                      dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        var act = data.activity || {};
        var netIn = data.net_in[gi] || 0;
        var netOut = data.net_out[gi] || 0;

        var html = '<div class="wd-tip-time">' + timeStr + '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow('#f87171', 'CPU', fmtPct(data.cpu[gi]));
        html += tipRow('#818cf8', 'RAM', fmtPct(data.ram[gi]));
        html += tipRow('#fbbf24', 'SWAP', fmtPct(data.swap[gi]));
        html += tipRow('#64748b', 'Disk', fmtPct(data.disk[gi]));
        html += '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow(null, 'Load', data.load_1m[gi]);
        html += tipRow(null, 'Net In', fmtKB(netIn));
        html += tipRow(null, 'Net Out', fmtKB(netOut));
        html += tipRow(null, 'Processes', data.processes[gi]);
        html += '</div>';
        html += '<div class="wd-tip-section wd-tip-activity">';
        html += tipRow('#818cf8', 'Requests', (act.requests && act.requests[gi]) || 0);
        html += tipRow('#34d399', 'Jobs', (act.jobs && act.jobs[gi]) || 0);
        html += tipRow('#fbbf24', 'Commands', (act.commands && act.commands[gi]) || 0);
        html += '</div>';

        var qry = data.queries || {};
        if (qry.total && qry.total[gi] !== undefined) {
            html += '<div class="wd-tip-section">';
            html += tipRow(null, 'Queries', qry.total[gi] || 0);
            html += tipRow('#60a5fa', 'SELECT', (qry.select && qry.select[gi]) || 0);
            html += tipRow('#34d399', 'INSERT', (qry.insert && qry.insert[gi]) || 0);
            html += tipRow('#fbbf24', 'UPDATE', (qry.update && qry.update[gi]) || 0);
            html += tipRow('#f87171', 'DELETE', (qry['delete'] && qry['delete'][gi]) || 0);
            html += '</div>';
        }

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

    function fmtPct(v) { return (v !== undefined && v !== null) ? v.toFixed ? v.toFixed(1) + '%' : v + '%' : '–'; }

    function fmtLocalDateTime(d) {
        var pad = function(n) { return n < 10 ? '0' + n : n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' +
               pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function fmtKB(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // ── Correlation ──
    function loadCorrelation(since, until) {
        var box = document.getElementById('wd-correlate-results');
        if (!box) return;
        box.innerHTML = '<p style="color:var(--wd-text-muted);text-align:center;padding:20px;">Loading...</p>';

        fetch(correlateUrl + '?since=' + encodeURIComponent(since) + '&until=' + encodeURIComponent(until) + '&sort=duration', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var c = data.counts || {};
            var total = (c.requests || 0) + (c.jobs || 0) + (c.commands || 0);

            if (total === 0) {
                box.innerHTML = '<p style="color:var(--wd-text-muted);text-align:center;padding:20px;">No requests, jobs, or commands found in this time window.</p>';
                return;
            }

            var html = '';

            html += '<div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--wd-border-subtle);flex-wrap:wrap;">';
            html += '<span style="font-size:12px;color:var(--wd-text-muted);">' + since + ' &mdash; ' + until + '</span>';
            html += '<div style="display:flex;gap:10px;margin-left:auto;">';
            html += corrCountBadge('#34d399', 'Jobs', c.jobs || 0);
            html += corrCountBadge('#fbbf24', 'Commands', c.commands || 0);
            html += corrCountBadge('#818cf8', 'Requests', c.requests || 0);
            html += '</div></div>';

            html += corrSection('Jobs', '#34d399', 'wd-badge-warning', data.jobs || [], 'jobs');
            html += corrSection('Commands', '#fbbf24', 'wd-badge-muted', data.commands || [], 'commands');
            html += corrSection('Requests', '#818cf8', 'wd-badge-info', data.requests || [], 'requests');

            box.innerHTML = html;
        })
        .catch(function(err) {
            console.error('Correlate error:', err);
            box.innerHTML = '<p style="color:var(--wd-danger);text-align:center;padding:20px;">Failed to load correlation data.</p>';
        });
    }

    function corrCountBadge(color, label, count) {
        return '<span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;">' +
               '<span style="width:8px;height:8px;border-radius:50%;background:' + color + ';box-shadow:0 0 4px ' + color + '40;"></span>' +
               '<span style="color:var(--wd-text-muted);">' + label + '</span>' +
               '<span style="color:var(--wd-text-heading);font-variant-numeric:tabular-nums;">' + count + '</span></span>';
    }

    function corrSection(title, accentColor, badgeClass, entries, routeKey) {
        var sectionStyle = 'margin-bottom:20px;';
        var headerStyle = 'display:flex;align-items:center;gap:8px;margin-bottom:8px;';
        var dotStyle = 'width:10px;height:10px;border-radius:50%;background:' + accentColor + ';box-shadow:0 0 6px ' + accentColor + '40;';

        var html = '<div style="' + sectionStyle + '">';
        html += '<div style="' + headerStyle + '">';
        html += '<span style="' + dotStyle + '"></span>';
        html += '<span style="font-size:13px;font-weight:600;color:var(--wd-text-heading);">' + title + '</span>';
        html += '<span style="font-size:11px;color:var(--wd-text-muted);margin-left:4px;">(' + entries.length + ')</span>';
        html += '</div>';

        if (entries.length === 0) {
            html += '<p style="color:var(--wd-text-muted);font-size:12px;padding:8px 0 0 18px;">None during this window</p>';
        } else {
            html += '<table class="wd-table"><thead><tr>';
            html += '<th>Name</th><th>Duration</th><th>Status</th><th>Time</th>';
            html += '</tr></thead><tbody>';

            entries.forEach(function(entry) {
                var dur = parseFloat(entry.duration);
                var durStr = isNaN(dur) ? '–' : Math.round(dur) + 'ms';
                var durClass = dur > 1000 ? 'wd-duration-slow' : dur > 100 ? 'wd-duration-medium' : 'wd-duration-fast';

                var sc = 'wd-badge-success';
                var st = entry.status || '';
                if (st === 'failed' || parseInt(st) >= 500) sc = 'wd-badge-danger';
                else if (parseInt(st) >= 400) sc = 'wd-badge-warning';

                var linkPath = routeKey === 'requests' ? 'requests' : routeKey === 'jobs' ? 'jobs' : 'commands';

                html += '<tr>';
                html += '<td><a href="/' + hashguardianPath + '/' + linkPath + '/' + entry.uuid + '" style="color:var(--wd-accent);text-decoration:none;font-weight:500;font-size:13px;">' + escHtml(entry.name) + '</a></td>';
                html += '<td><span class="wd-duration ' + durClass + '" style="font-size:12px;">' + durStr + '</span></td>';
                html += '<td>' + (st ? '<span class="wd-badge ' + sc + '">' + st + '</span>' : '<span style="color:var(--wd-text-muted);">–</span>') + '</td>';
                html += '<td style="font-size:12px;color:var(--wd-text-muted);white-space:nowrap;">' + new Date(entry.created_at).toLocaleTimeString() + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
        }

        html += '</div>';
        return html;
    }

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endpush
