@extends('hashguardian::layouts.app')

@section('title', 'Trends')

@section('actions')
    <select id="wd-trends-period" class="wd-date-input" style="width: auto;">
        <option value="24h" selected>Last 24 Hours</option>
        <option value="7d">Last 7 Days</option>
        <option value="30d">Last 30 Days</option>
        <option value="90d">Last 90 Days</option>
    </select>
    <button id="wd-trends-compare" class="wd-btn" style="padding: 6px 12px; font-size: 12px;">
        Compare
    </button>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="wd-stats-grid" id="wd-trends-summary">
        <div class="wd-stat-card" data-accent="info">
            <div class="wd-stat-label">Total Requests</div>
            <div class="wd-stat-value" id="wd-ts-requests">-</div>
            <div class="wd-stat-meta" id="wd-ts-requests-delta"></div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-label">Avg Response</div>
            <div class="wd-stat-value" id="wd-ts-avg-duration">-</div>
            <div class="wd-stat-meta" id="wd-ts-avg-duration-delta"></div>
        </div>
        <div class="wd-stat-card" data-accent="danger">
            <div class="wd-stat-label">Error Rate</div>
            <div class="wd-stat-value" id="wd-ts-error-rate">-</div>
            <div class="wd-stat-meta" id="wd-ts-error-rate-delta"></div>
        </div>
        <div class="wd-stat-card" data-accent="warning">
            <div class="wd-stat-label">Job Fail Rate</div>
            <div class="wd-stat-value" id="wd-ts-job-fail">-</div>
            <div class="wd-stat-meta" id="wd-ts-job-fail-delta"></div>
        </div>
        <div class="wd-stat-card" data-accent="success">
            <div class="wd-stat-label">Cache Hit Ratio</div>
            <div class="wd-stat-value" id="wd-ts-cache-hit">-</div>
            <div class="wd-stat-meta" id="wd-ts-cache-hit-delta"></div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-label">Exceptions</div>
            <div class="wd-stat-value danger" id="wd-ts-exceptions">-</div>
            <div class="wd-stat-meta" id="wd-ts-exceptions-delta"></div>
        </div>
    </div>

    {{-- Performance Timeline --}}
    <div class="wd-combined-chart-wrapper" style="margin-bottom: 24px;">
        <div class="wd-chart-panel" style="border-bottom: none; border-radius: 10px 10px 0 0;">
            <h3 class="wd-chart-title" style="margin-bottom: 8px;">Response Time</h3>
            <canvas id="wd-chart-response" style="max-height: 250px;" height="220"></canvas>
        </div>
        <div class="wd-chart-panel" style="border-top: none; border-radius: 0 0 10px 10px; padding-top: 0;">
            <h3 class="wd-chart-title" style="font-size: 12px; margin-bottom: 8px;">Request Volume & Errors</h3>
            <canvas id="wd-chart-volume" style="max-height: 150px;" height="130"></canvas>
        </div>
        <div id="wd-trends-tooltip" class="wd-chart-tooltip"></div>
    </div>

    {{-- System Metrics --}}
    <div class="wd-chart-panel" style="margin-bottom: 24px;">
        <h3 class="wd-chart-title" style="margin-bottom: 8px;">Server Resources</h3>
        <canvas id="wd-chart-system" style="max-height: 200px;" height="180"></canvas>
    </div>

    {{-- Slowest Endpoints --}}
    <div class="wd-card" style="margin-bottom: 24px;">
        <div class="wd-card-header">
            <h3 class="wd-card-title">Slowest Endpoints (P95)</h3>
        </div>
        <div id="wd-slowest-table">
            <div class="wd-empty" style="padding: 24px;">
                <div class="wd-empty-text">Loading...</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    var chartDataUrl = '{{ route("hashguardian.trends.chart-data") }}';
    var slowestUrl = '{{ route("hashguardian.trends.slowest") }}';
    var comparisonUrl = '{{ route("hashguardian.trends.comparison") }}';
    var chartState = { data: null };

    function init() {
        var sel = document.getElementById('wd-trends-period');
        if (sel) sel.addEventListener('change', loadAll);
        loadAll();
    }

    function loadAll() {
        loadChartData();
        loadSlowest();
    }

    function getPeriod() {
        return (document.getElementById('wd-trends-period') || {}).value || '24h';
    }

    function loadChartData() {
        fetch(chartDataUrl + '?period=' + getPeriod(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            chartState.data = data;
            updateSummaryCards(data.summary || {});
            drawResponseChart();
            drawVolumeChart();
            drawSystemChart();
            setupHover();
        })
        .catch(function(e) { console.error('Trends load failed:', e); });
    }

    function loadSlowest() {
        fetch(slowestUrl + '?period=' + getPeriod(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { renderSlowestTable(data); })
        .catch(function(e) { console.error('Slowest load failed:', e); });
    }

    function updateSummaryCards(s) {
        setCard('wd-ts-requests', s.request_count, '', true);
        setCard('wd-ts-avg-duration', s.request_avg_duration, 'ms');
        setCard('wd-ts-error-rate', s.request_error_rate, '%');
        setCard('wd-ts-job-fail', s.job_fail_rate, '%');
        setCard('wd-ts-cache-hit', s.cache_hit_ratio, '%');
        setCard('wd-ts-exceptions', s.exception_count, '', true);
    }

    function setCard(id, metric, suffix, isCount) {
        var el = document.getElementById(id);
        var deltaEl = document.getElementById(id + '-delta');
        if (!el || !metric) { if (el) el.textContent = '0'; return; }

        var val = metric.value || 0;
        el.textContent = isCount ? Math.round(val).toLocaleString() : val.toFixed(1) + (suffix || '');

        if (deltaEl && metric.delta !== undefined) {
            var d = metric.delta;
            var arrow = d > 0 ? '\u2191' : (d < 0 ? '\u2193' : '\u2192');
            var color = '';
            if (id.includes('error') || id.includes('fail') || id.includes('exception')) {
                color = d > 0 ? 'var(--wd-danger)' : (d < 0 ? 'var(--wd-success, #34d399)' : '');
            } else if (id.includes('cache-hit')) {
                color = d > 0 ? 'var(--wd-success, #34d399)' : (d < 0 ? 'var(--wd-danger)' : '');
            } else if (id.includes('avg-duration')) {
                color = d > 0 ? 'var(--wd-danger)' : (d < 0 ? 'var(--wd-success, #34d399)' : '');
            }
            deltaEl.innerHTML = '<span style="color:' + color + '">' + arrow + ' ' + Math.abs(d).toFixed(1) + '% vs prev</span>';
        }
    }

    function getThemeColors() {
        var isLight = document.body.classList.contains('wd-light');
        return {
            text: isLight ? '#94a3b8' : '#475569',
            grid: isLight ? '#f1f5f9' : 'rgba(148,163,184,0.06)',
            crosshair: isLight ? 'rgba(0,0,0,0.12)' : 'rgba(129,140,248,0.25)',
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

    function getX(idx, len, left, cw) { return left + (idx / Math.max(1, len - 1)) * cw; }
    function getY(val, maxVal, top, ch) { return top + ch - (val / Math.max(1, maxVal)) * ch; }

    function drawSmoothLine(ctx, points) {
        if (points.length < 2) return;
        ctx.moveTo(points[0].x, points[0].y);
        if (points.length === 2) { ctx.lineTo(points[1].x, points[1].y); return; }
        for (var i = 0; i < points.length - 1; i++) {
            var p0 = points[Math.max(0, i - 1)], p1 = points[i], p2 = points[i + 1], p3 = points[Math.min(points.length - 1, i + 2)];
            var t = 0.3;
            ctx.bezierCurveTo(p1.x + (p2.x - p0.x) * t, p1.y + (p2.y - p0.y) * t, p2.x - (p3.x - p1.x) * t, p2.y - (p3.y - p1.y) * t, p2.x, p2.y);
        }
    }

    function drawResponseChart(hoverIdx) {
        var c = prepCanvas('wd-chart-response');
        if (!c || !chartState.data) return;
        var d = chartState.data.datasets;
        var labels = chartState.data.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var pad = { top: 28, right: 20, bottom: 32, left: 56 };
        var cw = w - pad.left - pad.right, ch = h - pad.top - pad.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);
        if (!labels || labels.length === 0) {
            ctx.fillStyle = tc.text; ctx.font = '13px Inter,sans-serif'; ctx.textAlign = 'center';
            ctx.fillText('No aggregate data yet. Run: php artisan hashguardian:aggregate', w / 2, h / 2);
            return;
        }

        var series = [
            { label: 'Avg', data: d.request_avg_duration, color: '#818cf8' },
            { label: 'P95', data: d.request_p95_duration, color: '#f87171' }
        ];

        var maxVal = 1;
        series.forEach(function(s) { s.data.forEach(function(v) { if (v > maxVal) maxVal = v; }); });
        maxVal = Math.ceil(maxVal * 1.15) || 1;

        for (var g = 0; g <= 4; g++) {
            var gy = pad.top + (ch / 4) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.left, gy); ctx.lineTo(pad.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxVal - (maxVal / 4) * g) + 'ms', pad.left - 10, gy + 3);
        }

        series.forEach(function(s) {
            var pts = [];
            for (var k = 0; k < s.data.length; k++) { pts.push({ x: getX(k, s.data.length, pad.left, cw), y: getY(s.data[k], maxVal, pad.top, ch) }); }
            ctx.beginPath(); drawSmoothLine(ctx, pts);
            ctx.lineTo(pts[pts.length - 1].x, pad.top + ch); ctx.lineTo(pts[0].x, pad.top + ch); ctx.closePath();
            var grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
            grad.addColorStop(0, s.color + '18'); grad.addColorStop(1, s.color + '02');
            ctx.fillStyle = grad; ctx.fill();
            ctx.beginPath(); drawSmoothLine(ctx, pts);
            ctx.strokeStyle = s.color; ctx.lineWidth = 2; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.stroke();
        });

        var labelStep = Math.max(1, Math.floor(labels.length / 8));
        ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
        for (var t = 0; t < labels.length; t += labelStep) {
            var tx = getX(t, labels.length, pad.left, cw);
            var dt = new Date(labels[t]);
            ctx.fillText(dt.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), tx, h - 4);
        }

        ctx.font = '10px Inter,sans-serif'; var lx = pad.left;
        series.forEach(function(s) {
            ctx.beginPath(); ctx.arc(lx + 4, 10, 4, 0, Math.PI * 2); ctx.fillStyle = s.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left'; ctx.fillText(s.label, lx + 12, 13);
            lx += ctx.measureText(s.label).width + 26;
        });

        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, pad.left, cw);
            ctx.setLineDash([3, 3]); ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, pad.top); ctx.lineTo(hx, pad.top + ch); ctx.stroke(); ctx.setLineDash([]);
            series.forEach(function(s) {
                var dy = getY(s.data[hoverIdx], maxVal, pad.top, ch);
                ctx.beginPath(); ctx.arc(hx, dy, 6, 0, Math.PI * 2); ctx.fillStyle = s.color + '30'; ctx.fill();
                ctx.beginPath(); ctx.arc(hx, dy, 3, 0, Math.PI * 2); ctx.fillStyle = s.color; ctx.fill();
                ctx.strokeStyle = tc.bg; ctx.lineWidth = 2; ctx.stroke();
            });
        }
    }

    function drawVolumeChart(hoverIdx) {
        var c = prepCanvas('wd-chart-volume');
        if (!c || !chartState.data) return;
        var d = chartState.data.datasets;
        var labels = chartState.data.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var pad = { top: 10, right: 20, bottom: 32, left: 56 };
        var cw = w - pad.left - pad.right, ch = h - pad.top - pad.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);
        if (!labels || labels.length === 0) return;

        var maxVal = 1;
        d.request_count.forEach(function(v) { if (v > maxVal) maxVal = v; });
        maxVal = Math.ceil(maxVal * 1.15) || 1;

        for (var g = 0; g <= 3; g++) {
            var gy = pad.top + (ch / 3) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.left, gy); ctx.lineTo(pad.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(Math.round(maxVal - (maxVal / 3) * g), pad.left - 10, gy + 3);
        }

        var barW = Math.max(3, Math.min(18, (cw / Math.max(1, labels.length)) * 0.6));
        for (var j = 0; j < labels.length; j++) {
            var bx = getX(j, labels.length, pad.left, cw) - barW / 2;
            var val = d.request_count[j] || 0;
            var errRate = d.request_error_rate[j] || 0;
            var barH = (val / maxVal) * ch;
            var isHover = typeof hoverIdx === 'number' && j === hoverIdx;

            ctx.globalAlpha = isHover ? 1 : 0.65;
            ctx.fillStyle = errRate > 10 ? '#f87171' : (errRate > 5 ? '#fbbf24' : '#34d399');
            ctx.fillRect(bx, pad.top + ch - barH, barW, barH);
            ctx.globalAlpha = 1;
        }

        var labelStep = Math.max(1, Math.floor(labels.length / 8));
        ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
        for (var t = 0; t < labels.length; t += labelStep) {
            var tx = getX(t, labels.length, pad.left, cw);
            var dt = new Date(labels[t]);
            ctx.fillText(dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), tx, h - 4);
        }

        if (typeof hoverIdx === 'number' && hoverIdx >= 0 && hoverIdx < labels.length) {
            var hx = getX(hoverIdx, labels.length, pad.left, cw);
            ctx.setLineDash([3, 3]); ctx.strokeStyle = tc.crosshair; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(hx, pad.top); ctx.lineTo(hx, pad.top + ch); ctx.stroke(); ctx.setLineDash([]);
        }
    }

    function drawSystemChart(hoverIdx) {
        var c = prepCanvas('wd-chart-system');
        if (!c || !chartState.data) return;
        var d = chartState.data.datasets;
        var labels = chartState.data.labels;
        var ctx = c.ctx, w = c.w, h = c.h;
        var pad = { top: 28, right: 20, bottom: 32, left: 56 };
        var cw = w - pad.left - pad.right, ch = h - pad.top - pad.bottom;
        var tc = getThemeColors();

        ctx.clearRect(0, 0, w, h);
        if (!labels || labels.length === 0 || !d.server_avg_cpu) return;

        var series = [
            { label: 'CPU', data: d.server_avg_cpu, color: '#f87171' },
            { label: 'Memory', data: d.server_avg_memory, color: '#818cf8' }
        ];

        for (var g = 0; g <= 5; g++) {
            var gy = pad.top + (ch / 5) * g;
            ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.left, gy); ctx.lineTo(pad.left + cw, gy); ctx.stroke();
            ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
            ctx.fillText((100 - g * 20) + '%', pad.left - 10, gy + 3);
        }

        series.forEach(function(s) {
            var pts = [];
            for (var k = 0; k < s.data.length; k++) { pts.push({ x: getX(k, s.data.length, pad.left, cw), y: getY(s.data[k], 100, pad.top, ch) }); }
            ctx.beginPath(); drawSmoothLine(ctx, pts);
            ctx.lineTo(pts[pts.length - 1].x, pad.top + ch); ctx.lineTo(pts[0].x, pad.top + ch); ctx.closePath();
            var grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
            grad.addColorStop(0, s.color + '18'); grad.addColorStop(1, s.color + '02');
            ctx.fillStyle = grad; ctx.fill();
            ctx.beginPath(); drawSmoothLine(ctx, pts);
            ctx.strokeStyle = s.color; ctx.lineWidth = 2; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.stroke();
        });

        var labelStep = Math.max(1, Math.floor(labels.length / 8));
        ctx.fillStyle = tc.text; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
        for (var t = 0; t < labels.length; t += labelStep) {
            var tx = getX(t, labels.length, pad.left, cw);
            var dt = new Date(labels[t]);
            ctx.fillText(dt.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), tx, h - 4);
        }

        ctx.font = '10px Inter,sans-serif'; var lx = pad.left;
        series.forEach(function(s) {
            ctx.beginPath(); ctx.arc(lx + 4, 10, 4, 0, Math.PI * 2); ctx.fillStyle = s.color; ctx.fill();
            ctx.fillStyle = tc.text; ctx.textAlign = 'left'; ctx.fillText(s.label, lx + 12, 13);
            lx += ctx.measureText(s.label).width + 26;
        });
    }

    function renderSlowestTable(data) {
        var box = document.getElementById('wd-slowest-table');
        if (!box) return;

        if (!data || data.length === 0) {
            box.innerHTML = '<div class="wd-empty" style="padding:24px;"><div class="wd-empty-title">No data</div><div class="wd-empty-text">No endpoint performance data available. Run <code>php artisan hashguardian:aggregate</code>.</div></div>';
            return;
        }

        var html = '<table class="wd-table"><thead><tr><th>#</th><th>Endpoint</th><th>P95 Duration</th><th>Samples</th></tr></thead><tbody>';
        data.forEach(function(row, i) {
            var dur = row.p95_duration || 0;
            var cls = dur > 1000 ? 'wd-duration-slow' : (dur > 250 ? 'wd-duration-medium' : 'wd-duration-fast');
            html += '<tr>';
            html += '<td style="color:var(--wd-text-muted);font-size:12px;">' + (i + 1) + '</td>';
            html += '<td style="font-weight:500;font-size:13px;">' + escHtml(row.endpoint || 'N/A') + '</td>';
            html += '<td><span class="wd-duration ' + cls + '">' + dur.toFixed(1) + 'ms</span></td>';
            html += '<td style="color:var(--wd-text-muted);font-size:12px;">' + (row.sample_count || 0).toLocaleString() + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        box.innerHTML = html;
    }

    function setupHover() {
        var canvases = ['wd-chart-response', 'wd-chart-volume'];
        var tooltip = document.getElementById('wd-trends-tooltip');
        var wrapper = document.querySelector('.wd-combined-chart-wrapper');
        if (!tooltip || !wrapper) return;

        canvases.forEach(function(id) {
            var cv = document.getElementById(id);
            if (!cv) return;
            cv.style.cursor = 'crosshair';
            cv.addEventListener('mousemove', function(e) {
                var rect = cv.getBoundingClientRect();
                var pad = id === 'wd-chart-response' ? { left: 56, right: 20 } : { left: 56, right: 20 };
                var cw = rect.width - pad.left - pad.right;
                var relX = (e.clientX - rect.left - pad.left) / cw;
                if (relX < 0 || relX > 1) { hideTooltip(); return; }
                var labels = chartState.data ? chartState.data.labels : [];
                var idx = Math.round(relX * (labels.length - 1));
                drawResponseChart(idx);
                drawVolumeChart(idx);
                showTooltip(idx, e, wrapper);
            });
            cv.addEventListener('mouseleave', function() { hideTooltip(); });
        });

        function hideTooltip() {
            tooltip.style.display = 'none';
            drawResponseChart();
            drawVolumeChart();
        }
    }

    function showTooltip(idx, event, wrapper) {
        var tip = document.getElementById('wd-trends-tooltip');
        var data = chartState.data;
        if (!tip || !data || idx < 0 || idx >= data.labels.length) return;

        var dt = new Date(data.labels[idx]);
        var timeStr = dt.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ', ' + dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        var d = data.datasets;

        var html = '<div class="wd-tip-time">' + timeStr + '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow('#34d399', 'Requests', d.request_count[idx] || 0);
        html += tipRow('#818cf8', 'Avg Duration', (d.request_avg_duration[idx] || 0).toFixed(1) + 'ms');
        html += tipRow('#f87171', 'P95 Duration', (d.request_p95_duration[idx] || 0).toFixed(1) + 'ms');
        html += tipRow(null, 'Error Rate', (d.request_error_rate[idx] || 0).toFixed(1) + '%');
        html += '</div>';
        html += '<div class="wd-tip-section">';
        html += tipRow(null, 'Queries', d.query_count[idx] || 0);
        html += tipRow(null, 'Slow Queries', d.query_slow_count[idx] || 0);
        html += tipRow(null, 'Jobs', d.job_count[idx] || 0);
        html += tipRow(null, 'Exceptions', d.exception_count[idx] || 0);
        html += '</div>';

        tip.innerHTML = html;
        tip.style.display = 'block';

        var wrapRect = wrapper.getBoundingClientRect();
        var mx = event.clientX - wrapRect.left + 16;
        var my = event.clientY - wrapRect.top - tip.offsetHeight / 2;
        if (mx + tip.offsetWidth > wrapRect.width - 8) mx = event.clientX - wrapRect.left - tip.offsetWidth - 16;
        if (my < 4) my = 4;
        tip.style.left = mx + 'px';
        tip.style.top = my + 'px';
    }

    function tipRow(color, label, value) {
        var dot = color ? '<span class="wd-tip-dot" style="background:' + color + ';box-shadow:0 0 4px ' + color + '40"></span>' : '<span class="wd-tip-dot" style="background:transparent"></span>';
        return '<div class="wd-tip-row">' + dot + '<span class="wd-tip-label">' + label + '</span><span class="wd-tip-val">' + value + '</span></div>';
    }

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    window.addEventListener('resize', function() { if (chartState.data) { drawResponseChart(); drawVolumeChart(); drawSystemChart(); } });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
@endpush
